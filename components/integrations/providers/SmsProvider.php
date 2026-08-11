<?php

namespace app\components\integrations\providers;

use app\components\integrations\ActionResult;
use app\components\integrations\IntegrationProvider;
use app\models\base\ArmsModel;
use app\models\Users;
use yii\base\Model;

/**
 * Отправка SMS через шлюз с URL-шаблоном — эталонная интеграция
 * механизма (docs/dev/integrations.md, перенос вшитого
 * SmsController/SmsForm).
 *
 * Конфиг (params-local.php):
 * ```php
 * 'integrations' => [
 *     'sms' => [
 *         'class' => \app\components\integrations\providers\SmsProvider::class,
 *         'url' => 'https://sms-gw.local/send?phone={phone}&text={text}',
 *     ],
 * ],
 * ```
 * Наличие url = интеграция включена (бывший params['sms.enable']).
 *
 * Панелей нет; единственное действие send (L2, standalone) появляется:
 * - иконкой у телефонных атрибутов пользователя (AttributeActionsWidget)
 *   с предзаполнением конкретного номера;
 * - по прямому URL /integrations/action?provider=sms&action=send
 *   с произвольным номером (бывший /sms/send).
 *
 * В журнал пишется только номер (без текста сообщения): при композиции
 * (сброс пароля AD шлёт пароль по SMS, контракт §2.2) текст содержит
 * секрет.
 */
class SmsProvider extends IntegrationProvider
{
	/** телефонные атрибуты Users, у которых доступна иконка отправки */
	const PHONE_ATTRIBUTES = ['Mobile', 'private_phone'];

	public function getTitle(): string
	{
		return 'SMS';
	}

	public function isConfigured(): bool
	{
		return !empty($this->config['url']);
	}

	public function appliesTo(ArmsModel $model): bool
	{
		if (!$model instanceof Users) return false;
		foreach (static::PHONE_ATTRIBUTES as $attribute) {
			if (!empty($model->$attribute)) return true;
		}
		return false;
	}

	public function binding(ArmsModel $model): ?string
	{
		//панелей и кэша нет; ключом привязки считаем основной мобильный
		/** @var Users $model */
		return empty($model->Mobile) ? null : (string)$model->Mobile;
	}

	public function actions(?ArmsModel $model): array
	{
		return [
			'send' => [
				'title' => 'Отправить SMS',
				'icon' => 'fas fa-comment-dots',
				'level' => static::LEVEL_NORMAL,
				'form' => SmsSendForm::class,
				'standalone' => true,
				//кнопка в блоке интеграций не нужна: у телефонов уже есть иконки
				'showInPanel' => false,
			],
		];
	}

	public function attributeActions(ArmsModel $model, string $attribute, $value = null): array
	{
		if (!in_array($attribute, static::PHONE_ATTRIBUTES, true)) return [];
		$phone = $value ?? $model->$attribute;
		if (empty($phone)) return [];

		$actions = $this->actions($model);
		$actions['send']['prefill'] = ['phone' => $phone];
		return $actions;
	}

	/**
	 * @param SmsSendForm|Model $form
	 */
	public function runAction(string $actionId, ?ArmsModel $model, Model $form, ?array $credentials): ActionResult
	{
		$response = $this->request($this->buildUrl($form->phone, $form->text));

		//текст сообщения в журнал не пишем (может содержать секреты
		//при вложенных вызовах), только номер
		$logParams = ['phone' => $form->phone];

		if (is_null($response) || $response === '') {
			return ActionResult::error('SMS-шлюз недоступен', $logParams);
		}
		return ActionResult::success($response, $logParams);
	}

	public function renderActionForm(string $actionId, Model $form, $activeForm): string
	{
		return $this->renderView('send-form', ['form' => $form, 'activeForm' => $activeForm]);
	}

	/** URL запроса к шлюзу из шаблона конфига */
	public function buildUrl(string $phone, string $text): string
	{
		$url = $this->config['url'];
		$url = str_replace('{phone}', urlencode($phone), $url);
		return str_replace('{text}', urlencode($text), $url);
	}

	/**
	 * Запрос к шлюзу (перенос SmsForm::send() с добавлением таймаута)
	 * @return string|null тело ответа либо null при ошибке
	 */
	protected function request(string $url): ?string
	{
		$context = stream_context_create([
			'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
			'http' => ['timeout' => $this->timeout()],
		]);
		$response = @file_get_contents($url, false, $context);
		return $response === false ? null : $response;
	}
}
