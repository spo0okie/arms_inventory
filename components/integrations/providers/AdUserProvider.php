<?php

namespace app\components\integrations\providers;

use app\components\integrations\ActionResult;
use app\components\integrations\IntegrationProvider;
use app\components\integrations\IntegrationsRegistry;
use app\components\PronounceablePasswordGenerator;
use app\helpers\ArrayHelper;
use app\models\base\ArmsModel;
use app\models\Users;
use Yii;
use yii\base\Model;
use yii\helpers\Url;

/**
 * Интеграция с учёткой сотрудника в ActiveDirectory (docs/dev/integrations.md):
 * панель-справка + именной сброс пароля.
 *
 * Панель (L1) в карточке сотрудника — OU, статус учётки
 * (активна/отключена/заблокирована), смена/истечение пароля, last logon.
 *
 * Действие «сброс пароля» (L2+) появляется при настроенном SMS-провайдере
 * (зависимость конфигом, §5 композиция). Цель — снять с админов рутину
 * «я не могу войти, помогите» без похода в PowerShell: пароль генерируется
 * автоматически (по умолчанию «произносимый» — проще продиктовать),
 * соответствует парольной политике, отправляется пользователю по SMS и НЕ
 * показывается администратору (поэтому и «требовать смену» не нужно —
 * пароль знает только пользователь). Опционально — разблокировка учётки.
 *
 * Именное действие (L2+): выполняется от имени ЛИЧНОЙ учётки исполнителя,
 * введённой в форме действия (ядро запрашивает её само); у учётки должно
 * быть делегированное право Reset Password. Сервисной учёткой пароли не
 * сбрасываются — ИБ видит в логах AD живого исполнителя.
 *
 * Кнопка сброса рендерится внутри панели (showInPanel=false) — только
 * когда учётка найдена в AD: несуществующей учётке сброс не предлагается.
 * Кнопка — L0-ссылка, одинаковая для всех (кэш панелей общий на инстанс);
 * доступ к самому действию проверяет сервер при открытии формы.
 *
 * Композиция с SMS (§5 контракта), порядок принципиален:
 *  0. НЕДЕСТРУКТИВНО проверить креды исполнителя и его право на сброс
 *     (bind + чтение allowedAttributesEffective, без записи в AD) —
 *     чтобы не отправить SMS впустую при опечатке в пароле или учётке
 *     без прав;
 *  1. сгенерировать пароль;
 *  2. отправить его пользователю через SMS-провайдера;
 *     неудача = останов, пароль в AD не меняется — пользователь не
 *     останется с паролем, который ему не доставлен;
 *  3. записать пароль в AD от имени исполнителя (+ опц. разблокировка).
 * Пароль не попадает ни в журнал, ни в ответ исполнителю.
 *
 * Транспорт — НЕ HTTP: LDAP-компонент приложения
 * (`Yii::$app->ldap`, app\components\ldap\LdapService поверх ldaprecord),
 * та же сервисная учётка, что и у аутентификации.
 *
 * Конфиг (params-local.php):
 * ```php
 * 'integrations' => [
 *     'ad' => [
 *         'class' => \app\components\integrations\providers\AdUserProvider::class,
 *         //'cacheTtl' => 0,       //сек; 0 = запрашивать AD при каждом открытии
 *         //сброс пароля (появляется при включённом SMS-провайдере):
 *         //'sms' => 'sms',        //id SMS-провайдера для отправки пароля
 *         //'defaultLength' => 12, //длина пароля по умолчанию в форме
 *         //'smsText' => 'Ваш новый пароль: {password}',
 *     ],
 * ],
 * ```
 */
class AdUserProvider extends IntegrationProvider
{
	/** id единственной панели */
	const PANEL = 'account';

	/** id действия сброса пароля */
	const ACTION = 'reset-password';

	/** символы случайного генератора (без визуально неоднозначных 0O1lI) */
	const RND_UPPER = 'ABCDEFGHJKMNPQRSTUVWXYZ';
	const RND_LOWER = 'abcdefghijkmnpqrstuvwxyz';
	const RND_DIGIT = '23456789';
	const RND_SPECIAL = '!@#$%*()-_+=?';

	public function getTitle(): string
	{
		return $this->config['title'] ?? 'Active Directory';
	}

	public function isConfigured(): bool
	{
		return Yii::$app->has('ldap');
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

	public function panels(ArmsModel $model): array
	{
		return [
			static::PANEL => [
				'title' => $this->getTitle(),
				//0 = обновлять при каждом открытии карточки: AD рядом и
				//отвечает быстро, а показывать устаревший статус учётки
				//(особенно сразу после сброса пароля) нельзя. Кэш-файл
				//остаётся - он нужен для мгновенной отрисовки и как
				//запасной вариант, если контроллер домена недоступен
				'ttl' => $this->config['cacheTtl'] ?? 0,
			],
		];
	}

	public function renderPanel(string $panelId, ArmsModel $model): string
	{
		$account = $this->fetchAccount($this->binding($model));
		$action = $this->actions($model)[static::ACTION] ?? null;
		return $this->renderView('account', [
			'account' => $account,
			'model' => $model,
			//кнопка сброса внутри панели: L0-ссылка, одинаковая для всех
			//(кэш общий на инстанс), доступ к действию проверяет сервер;
			//null = сброс недоступен (нет SMS-провайдера)
			'resetUrl' => $action
				? Url::to(IntegrationsRegistry::actionUrl($this, static::ACTION, $action, $model))
				: null,
		]);
	}

	public function actions(?ArmsModel $model): array
	{
		//зависимость сброса от SMS-провайдера объявлена конфигом (§5);
		//проверяем по сырым params, не через реестр - реестр в момент
		//вызова ещё может строиться. Нет SMS = панель без действия
		$smsConfig = Yii::$app->params['integrations'][$this->smsProviderId()] ?? [];
		if (empty($smsConfig)) return [];
		return [
			static::ACTION => [
				'title' => 'Сбросить пароль в AD',
				'icon' => 'fas fa-key',
				'level' => static::LEVEL_PERSONAL,
				'form' => AdPasswordResetForm::class,
				//длина по умолчанию из конфига инстанса (если задана)
				'prefill' => ['length' => (int)($this->config['defaultLength'] ?? AdPasswordResetForm::MIN_LENGTH)],
				//кнопка живёт внутри панели (только у найденной в AD учётки),
				//в общем блоке кнопок интеграций не дублируется
				'showInPanel' => false,
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
		$pronounceable = (bool)$form->pronounceable;
		$length = max(AdPasswordResetForm::MIN_LENGTH, (int)$form->length);
		$unlock = (bool)$form->unlock;
		//пароль в журнал НЕ пишем - только характеристики действия
		$logParams = ['login' => $targetLogin, 'pronounceable' => $pronounceable,
			'length' => $length, 'unlock' => $unlock];

		//куда доставлять пароль: первый заполненный телефон сотрудника,
		//те же поля, что SMS-провайдер считает мобильными (Mobile, затем
		//private_phone) - единый источник правды
		$phone = '';
		foreach (SmsProvider::PHONE_ATTRIBUTES as $attribute) {
			$candidate = trim(ArrayHelper::explode(',', $model->$attribute ?? '')[0] ?? '');
			if ($candidate !== '') { $phone = $candidate; break; }
		}
		if ($phone === '') {
			return ActionResult::error('У сотрудника не заполнен мобильный/личный телефон - пароль некуда отправить', $logParams);
		}

		//шаг 0: НЕДЕСТРУКТИВНАЯ предпроверка ДО SMS - валидны ли креды
		//исполнителя и есть ли у него право сброса. Снимает сценарии
		//«SMS ушло, а пароль не сменился». Без записи в AD (bind + чтение).
		try {
			$this->ldapVerify($targetLogin, $credentials);
		} catch (\Throwable $e) {
			return ActionResult::error(
				'Сброс не выполнен (SMS не отправлено): '.$e->getMessage(),
				$logParams
			);
		}

		$password = $pronounceable
			? (new PronounceablePasswordGenerator($length))->generate()
			: $this->randomPassword($length);

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

		//шаг 2: запись пароля в AD от имени исполнителя (+ опц. разблокировка)
		try {
			$adInfo = $this->ldapResetPassword($targetLogin, $password, $unlock, $credentials);
		} catch (\Throwable $e) {
			Yii::warning("AD password reset for $targetLogin failed: ".$e->getMessage(), __METHOD__);
			$result = ActionResult::error(
				'SMS отправлено, но пароль в AD НЕ изменён: '.$e->getMessage()
				.'. Повторите сброс - пользователю придет новое SMS.',
				$logParams
			);
			$result->html = $this->renderReport($model, $phone, $targetLogin, $credentials['login'],
				$sms, null, $e->getMessage(), $unlock);
			return $result;
		}

		$result = ActionResult::success(
			"Пароль сброшен и отправлен по SMS на $phone"
			.($unlock ? ', учётка разблокирована' : '')
			.'. Пароль знает только пользователь.',
			$logParams
		);
		$result->html = $this->renderReport($model, $phone, $targetLogin, $credentials['login'],
			$sms, $adInfo, null, $unlock);
		return $result;
	}

	/**
	 * Развёрнутый отчёт о выполнении для модалки: что сделано на каждом
	 * шаге, что ответил SMS-шлюз и чем подтверждена смена пароля в AD.
	 * Пароль в отчёт не попадает.
	 *
	 * @param ActionResult $sms итог шага отправки SMS
	 * @param array|null $adInfo итог записи в AD (null = шаг не выполнен)
	 * @param string|null $adError текст ошибки AD (если шаг провалился)
	 */
	protected function renderReport(?ArmsModel $model, string $phone, string $targetLogin,
		string $execLogin, ActionResult $sms, ?array $adInfo, ?string $adError, bool $unlock): string
	{
		$e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
		$time = static fn($ts) => $ts ? Yii::$app->formatter->asDatetime($ts, 'php:d.m.Y H:i:s') : '—';
		$ok = static fn(bool $good) => $good
			? '<span class="badge bg-success">выполнено</span>'
			: '<span class="badge bg-danger">не выполнено</span>';

		$rows = [];
		$rows[] = ['Учётная запись', $e($targetLogin).($adInfo['dn'] ?? null ? ' <small class="text-secondary">'.$e($adInfo['dn']).'</small>' : '')];
		$rows[] = ['Исполнитель в AD', $e($execLogin)];
		$rows[] = ['Проверка прав', $ok(true).' <small class="text-secondary">креды верны, право на сброс есть</small>'];
		$rows[] = ['Отправка SMS', $ok($sms->ok).' на '.$e($phone)
			.'<br><small class="text-secondary">'.$e($sms->message).'</small>'];

		if ($adInfo) {
			$rows[] = ['Смена пароля в AD', $ok(true)
				.'<br><small class="text-secondary">отметка смены пароля (pwdLastSet): '
				.$e($time($adInfo['pwd_last_set_before'])).' → <b>'.$e($time($adInfo['pwd_last_set_after'])).'</b></small>'];
			if ($unlock) $rows[] = ['Разблокировка', $ok(true)];
		} else {
			$rows[] = ['Смена пароля в AD', $ok(false)
				.'<br><small class="text-danger">'.$e($adError).'</small>'];
		}

		$html = '<table class="table table-sm w-auto">';
		foreach ($rows as [$label, $value]) {
			$html .= '<tr><td class="text-secondary pe-3 align-top">'.$label.'</td><td>'.$value.'</td></tr>';
		}
		$html .= '</table>';

		$html .= '<p class="text-secondary mb-0"><small>Пароль отправлен только пользователю по SMS '
			.'и нигде не сохраняется. Если SMS не дошло — повторите сброс, придёт новый пароль. '
			.'Панель AD в карточке покажет новое состояние после обновления страницы.</small></p>';

		return $html;
	}

	/**
	 * Поля формы: тип пароля (произносимый/случайный), длина, разблокировка.
	 * Ручного ввода пароля нет - он генерируется и не показывается админу.
	 */
	public function renderActionForm(string $actionId, Model $form, $activeForm): string
	{
	    $html = '<div class="row">';
		$html .= '<div class="col-6">';
		$html .= (string)$activeForm->field($form, 'length')->textInput([
			'type' => 'number',
			'min' => AdPasswordResetForm::MIN_LENGTH,
			'max' => AdPasswordResetForm::MAX_LENGTH,
		]);
		$html .= '</div>';
		$html .= '<div class="col-6">';
		$html .= (string)$activeForm->field($form, 'pronounceable')->checkbox();
		$html .= (string)$activeForm->field($form, 'unlock')->checkbox();
		$html .= '</div>';
		$html .= '</div>';
		$html .= '<p class="text-secondary">Будет сгенерирован пароль и отправлен пользователю '
			.'по SMS. Администратор пароль не видит.</p>';
		return $html;
	}

	/** id SMS-провайдера для отправки пароля (зависимость, §5) */
	protected function smsProviderId(): string
	{
		return $this->config['sms'] ?? 'sms';
	}

	/**
	 * Полностью случайный пароль заданной длины: гарантированно есть все
	 * классы (верхний/нижний регистр, цифра, спецсимвол — AD complexity),
	 * без визуально неоднозначных символов
	 */
	public function randomPassword(int $length): string
	{
		$length = max(AdPasswordResetForm::MIN_LENGTH, $length);
		$sets = [static::RND_UPPER, static::RND_LOWER, static::RND_DIGIT, static::RND_SPECIAL];
		$all = implode('', $sets);

		$chars = [];
		foreach ($sets as $set) { //по одному из каждого класса
			$chars[] = $set[random_int(0, strlen($set) - 1)];
		}
		while (count($chars) < $length) {
			$chars[] = $all[random_int(0, strlen($all) - 1)];
		}
		//перемешиваем криптостойко (shuffle не CSPRNG)
		for ($i = count($chars) - 1; $i > 0; $i--) {
			$j = random_int(0, $i);
			[$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
		}
		return implode('', $chars);
	}

	/**
	 * Атрибуты учётки из AD, нормализованные для рендера. Делегирует
	 * LdapService (единственная точка, знающая о LDAP-библиотеке).
	 * Вынесено в отдельный метод: тесты подменяют его, не трогая LDAP.
	 *
	 * @return array|null null = учётка не найдена; состав — см.
	 *   {@see \app\components\ldap\LdapService::accountInfo()}
	 * @throws \Throwable при недоступности LDAP (ловит ядро)
	 */
	protected function fetchAccount(string $login): ?array
	{
		return Yii::$app->ldap->accountInfo($login);
	}

	/**
	 * НЕДЕСТРУКТИВНАЯ предпроверка кредов и прав исполнителя (шаг 0).
	 * Делегирует LdapService. Вынесено в отдельный метод: тесты подменяют
	 * его, не трогая LDAP.
	 * @throws \Throwable неверные креды / нет прав / цель не найдена / DC недоступен
	 */
	protected function ldapVerify(string $targetLogin, array $credentials): void
	{
		Yii::$app->ldap->verifyResetPermission(
			$targetLogin,
			$credentials['login'],
			$credentials['password']
		);
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
		bool $unlock, array $credentials): array
	{
		return Yii::$app->ldap->resetPassword(
			$targetLogin,
			$password,
			$unlock,
			$credentials['login'],
			$credentials['password']
		);
	}
}
