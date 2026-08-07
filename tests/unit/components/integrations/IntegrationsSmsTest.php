<?php

namespace tests\unit\components\integrations;

use app\components\integrations\AttributeActionsWidget;
use app\components\integrations\IntegrationProvider;
use app\components\integrations\IntegrationsRegistry;
use app\components\integrations\PanelsWidget;
use app\components\integrations\providers\SmsProvider;
use app\components\integrations\providers\SmsSendForm;
use app\models\IntegrationsLog;
use app\models\Techs;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты механизма интеграций (plans/integrations-contract.md) на эталонном
 * провайдере SMS: построение реестра из params, применимость и prefill,
 * выполнение действия с журналированием (шлюз имитируется data://-URL,
 * в сеть тесты не ходят), доступ при выключенном RBAC.
 */
class IntegrationsSmsTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var \yii\db\Transaction */
	private $transaction;

	/** @var array исходные params для восстановления */
	private $originalIntegrations;

	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
		$this->originalIntegrations = Yii::$app->params['integrations'] ?? [];
		IntegrationsRegistry::reset();
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
		Yii::$app->params['integrations'] = $this->originalIntegrations;
		IntegrationsRegistry::reset();
	}

	/** Настраивает реестр на единственный sms-провайдер с заданным url */
	private function setupSms(string $url): void
	{
		Yii::$app->params['integrations'] = [
			'sms' => ['class' => SmsProvider::class, 'url' => $url],
		];
		IntegrationsRegistry::reset();
	}

	/**
	 * Реестр строится из params: корректный провайдер попадает,
	 * ненастроенный (без url) и с мусорным классом — молча выпадают.
	 */
	public function testRegistryBuild()
	{
		Yii::$app->params['integrations'] = [
			'sms' => ['class' => SmsProvider::class, 'url' => 'data://text/plain,OK'],
			'unconfigured' => ['class' => SmsProvider::class],
			'garbage' => ['class' => 'app\\components\\NoSuchClass'],
			'no-class' => ['url' => 'x'],
		];
		IntegrationsRegistry::reset();

		$providers = IntegrationsRegistry::providers();
		$this->assertSame(['sms'], array_keys($providers));
		$this->assertSame('sms', $providers['sms']->id);
		$this->assertNull(IntegrationsRegistry::provider('unconfigured'));
	}

	/**
	 * Применимость: пользователь с телефоном — да, без телефонов и
	 * не-Users — нет.
	 */
	public function testAppliesTo()
	{
		$this->setupSms('data://text/plain,OK');
		$provider = IntegrationsRegistry::provider('sms');

		$this->assertTrue($provider->appliesTo(new Users(['Mobile' => '79991234567'])));
		$this->assertTrue($provider->appliesTo(new Users(['private_phone' => '79991234567'])));
		$this->assertFalse($provider->appliesTo(new Users()));
		$this->assertFalse($provider->appliesTo(new Techs()));
	}

	/**
	 * Действия у атрибута: телефонные атрибуты дают действие send с
	 * prefill конкретного значения, прочие атрибуты — ничего.
	 */
	public function testAttributeActionsPrefill()
	{
		$this->setupSms('data://text/plain,OK');
		$provider = IntegrationsRegistry::provider('sms');
		$user = new Users(['Mobile' => '79991234567, 79997654321']);

		$actions = $provider->attributeActions($user, 'Mobile', '79997654321');
		$this->assertArrayHasKey('send', $actions);
		$this->assertSame(['phone' => '79997654321'], $actions['send']['prefill']);

		$this->assertSame([], $provider->attributeActions($user, 'Email'));
		$this->assertSame([], $provider->attributeActions(new Users(), 'Mobile'));
	}

	/** Подстановка и кодирование параметров в URL-шаблон шлюза */
	public function testBuildUrl()
	{
		$this->setupSms('https://gw.local/send?phone={phone}&text={text}');
		/** @var SmsProvider $provider */
		$provider = IntegrationsRegistry::provider('sms');

		$this->assertSame(
			'https://gw.local/send?phone=79991234567&text=%D0%BF%D1%80%D0%B8%D0%B2%D0%B5%D1%82+%26+%D0%BF%D0%BE%D0%BA%D0%B0',
			$provider->buildUrl('79991234567', 'привет & пока')
		);
	}

	/**
	 * Успешная отправка через реестр: результат ok, ответ шлюза в message,
	 * запись в журнале с номером но БЕЗ текста сообщения (контракт §6:
	 * текст может содержать секреты при композиции).
	 */
	public function testRunActionSuccessAndJournal()
	{
		$this->setupSms('data://text/plain,SENT-42');

		$result = IntegrationsRegistry::runAction('sms', 'send', null, [
			'phone' => '79991234567',
			'text' => 'секретный текст',
		]);

		$this->assertTrue($result->ok);
		$this->assertSame('SENT-42', $result->message);
		$this->assertNotNull($result->logId);

		$log = IntegrationsLog::findOne($result->logId);
		$this->assertSame('sms', $log->provider);
		$this->assertSame('send', $log->action);
		$this->assertSame('ok', $log->result);
		$this->assertNull($log->class);
		$this->assertStringContainsString('79991234567', $log->params);
		$this->assertStringNotContainsString('секретный', $log->params);
		$this->assertStringNotContainsString('секретный', (string)$log->message);
	}

	/** Пустой ответ шлюза = ошибка, журналируется с result=error */
	public function testRunActionGatewayUnavailable()
	{
		$this->setupSms('data://text/plain,');

		$result = IntegrationsRegistry::runAction('sms', 'send', null, [
			'phone' => '79991234567',
			'text' => 'test',
		]);

		$this->assertFalse($result->ok);
		$this->assertSame('error', IntegrationsLog::findOne($result->logId)->result);
	}

	/**
	 * Невалидные параметры не доходят до шлюза: ошибка валидации,
	 * попытка тоже журналируется
	 */
	public function testRunActionInvalidParams()
	{
		$this->setupSms('data://text/plain,OK');

		$result = IntegrationsRegistry::runAction('sms', 'send', null, [
			'phone' => '123',
			'text' => 'test',
		]);

		$this->assertFalse($result->ok);
		$this->assertStringContainsString('валидацию', $result->message);
		$this->assertSame('error', IntegrationsLog::findOne($result->logId)->result);
	}

	/** Неизвестный провайдер/действие — ошибка, а не исключение */
	public function testRunActionUnknown()
	{
		$this->setupSms('data://text/plain,OK');

		$this->assertFalse(IntegrationsRegistry::runAction('no-such', 'send', null, [])->ok);
		$this->assertFalse(IntegrationsRegistry::runAction('sms', 'no-such', null, [])->ok);
	}

	/**
	 * Вложенный вызов (композиция §2.2): parent_id связывает запись
	 * шага с записью инициатора
	 */
	public function testNestedCallParentId()
	{
		$this->setupSms('data://text/plain,OK');

		$parent = IntegrationsRegistry::runAction('sms', 'send', null,
			['phone' => '79991234567', 'text' => 'step 1']);
		$child = IntegrationsRegistry::runAction('sms', 'send', null,
			['phone' => '79991234567', 'text' => 'step 2'], null, $parent->logId);

		$this->assertSame($parent->logId, IntegrationsLog::findOne($child->logId)->parent_id);
	}

	/**
	 * Доступ при выключенном RBAC (контракт §4): панели — по правилам
	 * просмотра инстанса (у тестового инстанса открыт всем), действия —
	 * только авторизованным (гостю запрещено)
	 */
	public function testAccessWithoutRbac()
	{
		$this->setupSms('data://text/plain,OK');
		$provider = IntegrationsRegistry::provider('sms');

		$this->assertFalse((bool)(Yii::$app->params['useRBAC'] ?? false), 'тест рассчитан на useRBAC=false');
		$this->assertTrue(Yii::$app->user->isGuest, 'тест рассчитан на гостя');

		$this->assertTrue(IntegrationsRegistry::userCanView($provider));
		$this->assertFalse(IntegrationsRegistry::userCanRun($provider, 'send'));
	}

	/** Валидация формы: нормализация номера и ограничения длины */
	public function testSendFormValidation()
	{
		$form = new SmsSendForm(['phone' => ' +7 (999) 123-45-67 ', 'text' => 'ok']);
		$this->assertTrue($form->validate());
		$this->assertSame('79991234567', $form->phone);

		$form = new SmsSendForm(['phone' => '123', 'text' => 'ok']);
		$this->assertFalse($form->validate());

		$form = new SmsSendForm(['phone' => '79991234567', 'text' => str_repeat('x', 129)]);
		$this->assertFalse($form->validate());

		$form = new SmsSendForm(['phone' => '', 'text' => '']);
		$this->assertFalse($form->validate());
	}

	/**
	 * Рендер иконки у атрибута авторизованным пользователем: ссылка на
	 * /integrations/action с prefill номера в имени формы; гостю иконка
	 * не рендерится (RBAC выключен => действия только авторизованным)
	 */
	public function testAttributeActionsWidgetRender()
	{
		$this->setupSms('data://text/plain,OK');

		$user = new Users(['Ename' => 'Тест Виджет', 'Mobile' => '79991234567']);
		$this->assertTrue($user->save(false));

		//гость - иконки нет
		$this->assertSame('', AttributeActionsWidget::widget([
			'model' => $user, 'attribute' => 'Mobile', 'value' => '79991234567',
		]));

		Yii::$app->user->login($user);
		try {
			$html = AttributeActionsWidget::widget([
				'model' => $user, 'attribute' => 'Mobile', 'value' => '79991234567',
			]);
		} finally {
			Yii::$app->user->logout();
		}

		$this->assertStringContainsString('integrations%2Faction', urlencode($html));
		$this->assertStringContainsString('provider=sms', $html);
		$this->assertStringContainsString('open-in-modal-form', $html);
		//prefill номера в имени формы действия
		$this->assertStringContainsString('SmsSendForm%5Bphone%5D=79991234567', $html);
	}

	/**
	 * Блок панелей у SMS-провайдера пуст (панелей нет, кнопка действия
	 * скрыта showInPanel=false) - слот не занимает место в карточке
	 */
	public function testPanelsWidgetEmptyForSms()
	{
		$this->setupSms('data://text/plain,OK');

		$user = new Users(['Ename' => 'Тест Панели', 'Mobile' => '79991234567']);
		$this->assertTrue($user->save(false));

		Yii::$app->user->login($user);
		try {
			$this->assertSame('', PanelsWidget::widget(['model' => $user]));
		} finally {
			Yii::$app->user->logout();
		}
	}

	/** Дефолты дескриптора действия и таймаут провайдера */
	public function testActionDescriptor()
	{
		$this->setupSms('data://text/plain,OK');
		$provider = IntegrationsRegistry::provider('sms');

		$descriptor = $provider->actions(null)['send'];
		$this->assertSame(IntegrationProvider::LEVEL_NORMAL, $descriptor['level']);
		$this->assertTrue($descriptor['standalone']);
		$this->assertSame(SmsSendForm::class, $descriptor['form']);
		$this->assertSame(IntegrationProvider::DEFAULT_TIMEOUT, $provider->timeout());
	}
}
