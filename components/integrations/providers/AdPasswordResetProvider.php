<?php

namespace app\components\integrations\providers;

use app\components\integrations\ActionResult;
use app\components\integrations\IntegrationProvider;
use app\components\integrations\IntegrationsRegistry;
use app\helpers\ArrayHelper;
use app\models\base\ArmsModel;
use app\models\Users;
use Yii;
use yii\base\Model;

/**
 * Сброс пароля пользователя в AD (docs/dev/integrations.md).
 *
 * Именное действие (L2+): выполняется от имени ЛИЧНОЙ учётки исполнителя,
 * введённой в форме действия (ядро запрашивает её само, §3.3 контракта);
 * у учётки должно быть делегированное право Reset Password. Сервисной
 * учёткой пароли не сбрасываются — ИБ видит в логах AD живого исполнителя.
 *
 * Композиция с SMS (§2.2 контракта), порядок принципиален:
 *  1. сгенерировать (или взять из формы) пароль;
 *  2. отправить его пользователю через SMS-провайдера;
 *     неудача = останов, пароль в AD не меняется — пользователь не
 *     останется с паролем, который ему не доставлен;
 *  3. записать пароль в AD от имени исполнителя.
 * Пароль не попадает ни в журнал, ни в ответ исполнителю.
 *
 * Конфиг (params-local.php):
 * ```php
 * 'integrations' => [
 *     'ad-reset' => [
 *         'class' => \app\components\integrations\providers\AdPasswordResetProvider::class,
 *         //'sms' => 'sms',        //id SMS-провайдера для отправки пароля
 *         //'provider' => 'default', //имя провайдера в компоненте ldap
 *         //'smsText' => 'Ваш новый пароль: {password}',
 *     ],
 * ],
 * ```
 */
class AdPasswordResetProvider extends IntegrationProvider
{
	const ACTION = 'reset-password';

	/** символы генератора паролей (без визуально неоднозначных 0O1lI) */
	const GEN_UPPER = 'ABCDEFGHJKMNPQRSTUVWXYZ';
	const GEN_LOWER = 'abcdefghjkmnpqrstuvwxyz';
	const GEN_DIGIT = '23456789';
	const GEN_LENGTH = 12;

	public function getTitle(): string
	{
		return $this->config['title'] ?? 'Сброс пароля AD';
	}

	public function isConfigured(): bool
	{
		//зависимость от SMS-провайдера объявлена конфигом (§2.2);
		//проверяем по сырым params, не через реестр - реестр в момент
		//isConfigured() ещё строится
		$smsConfig = Yii::$app->params['integrations'][$this->smsProviderId()] ?? [];
		return Yii::$app->has('ldap') && !empty($smsConfig);
	}

	public function appliesTo(ArmsModel $model): bool
	{
		return $model instanceof Users && !empty($model->Login);
	}

	public function binding(ArmsModel $model): ?string
	{
		/** @var Users $model */
		return empty($model->Login) ? null : mb_strtolower(trim($model->Login));
	}

	public function actions(?ArmsModel $model): array
	{
		return [
			static::ACTION => [
				'title' => 'Сбросить пароль в AD',
				'icon' => 'fas fa-key',
				'level' => static::LEVEL_PERSONAL,
				'form' => AdPasswordResetForm::class,
			],
		];
	}

	/**
	 * @param AdPasswordResetForm|Model $form
	 */
	public function runAction(string $actionId, ?ArmsModel $model, Model $form, ?array $credentials): ActionResult
	{
		/** @var Users|null $model */
		if (!is_object($model) || !$this->appliesTo($model)) {
			return ActionResult::error('Действие применимо только к сотруднику с логином AD');
		}
		//L2+ гарантируется контроллером, но серверный вызов мог прийти без кредов
		if (empty($credentials['login']) || empty($credentials['password'])) {
			return ActionResult::error('Требуются учетные данные исполнителя в AD');
		}

		$targetLogin = $this->binding($model);
		$logParams = ['login' => $targetLogin, 'mustChange' => (bool)$form->mustChange];

		//куда доставлять пароль: первый мобильный номер сотрудника
		$phone = trim(ArrayHelper::explode(',', $model->Mobile ?? '')[0] ?? '');
		if ($phone === '') {
			return ActionResult::error('У сотрудника не заполнен мобильный номер - пароль некуда отправить', $logParams);
		}

		$password = trim((string)$form->password);
		if ($password === '') $password = $this->generatePassword();

		//шаг 1: SMS с паролем ДО записи в AD; неудача = останов
		$smsText = str_replace(
			['{password}', '{login}'],
			[$password, $targetLogin],
			$this->config['smsText'] ?? 'Ваш новый пароль: {password}'
		);
		$sms = IntegrationsRegistry::runAction($this->smsProviderId(), 'send', $model,
			['phone' => $phone, 'text' => $smsText],
			null, $this->activeLogId);
		if (!$sms->ok) {
			return ActionResult::error('Пароль НЕ изменён: не удалось отправить SMS ('.$sms->message.')', $logParams);
		}

		//шаг 2: запись пароля в AD от имени исполнителя
		try {
			$this->ldapResetPassword($targetLogin, $password, (bool)$form->mustChange, $credentials);
		} catch (\Throwable $e) {
			Yii::warning("AD password reset for $targetLogin failed: ".$e->getMessage(), __METHOD__);
			return ActionResult::error(
				'SMS отправлено, но пароль в AD НЕ изменён: '.$e->getMessage()
				.'. Повторите сброс - пользователю придет новое SMS.',
				$logParams
			);
		}

		return ActionResult::success(
			"Пароль сброшен и отправлен SMS на $phone"
			.($form->mustChange ? ', при входе потребуется смена' : ''),
			$logParams
		);
	}

	/** id SMS-провайдера для отправки пароля (зависимость, §2.2) */
	protected function smsProviderId(): string
	{
		return $this->config['sms'] ?? 'sms';
	}

	/**
	 * Генерация пароля: длина GEN_LENGTH, гарантированно есть верхний
	 * и нижний регистр и цифра (AD complexity), без неоднозначных символов
	 */
	public function generatePassword(): string
	{
		$sets = [static::GEN_UPPER, static::GEN_LOWER, static::GEN_DIGIT];
		$all = implode('', $sets);

		$chars = [];
		foreach ($sets as $set) { //по одному из каждого класса
			$chars[] = $set[random_int(0, strlen($set) - 1)];
		}
		while (count($chars) < static::GEN_LENGTH) {
			$chars[] = $all[random_int(0, strlen($all) - 1)];
		}
		shuffle($chars);
		return implode('', $chars);
	}

	/**
	 * Запись пароля в AD от имени исполнителя. Делегирует LdapService,
	 * который открывает отдельное соединение под личными кредами
	 * исполнителя (общее соединение приложения под сервисной учёткой не
	 * трогается) и меняет пароль (требуется LDAPS/TLS — наш конфиг такой).
	 *
	 * Вынесено в отдельный метод: тесты подменяют его, не трогая LDAP.
	 *
	 * @throws \Throwable при ошибке бинда/записи (нет прав, политика паролей...)
	 */
	protected function ldapResetPassword(string $targetLogin, string $password,
		bool $mustChange, array $credentials): void
	{
		Yii::$app->ldap->resetPassword(
			$targetLogin,
			$password,
			$mustChange,
			$credentials['login'],
			$credentials['password']
		);
	}
}
