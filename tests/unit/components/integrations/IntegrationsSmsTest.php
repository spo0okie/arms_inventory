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
 * Тесты механизма интеграций (docs/dev/integrations.md) на эталонном
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
		//в сообщении виден ответ шлюза (нужно для разбора «почему не дошло»)
		$this->assertStringContainsString('SENT-42', $result->message);
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

	/**
	 * Шлюз отвечает 200 и на отказ (NO_MESSAGE_GIVEN, EXECUTION_ERROR...) —
	 * это ОШИБКА, а не успех: иначе «SMS отправлено», а сообщение не дошло.
	 */
	public function testGatewayErrorAnswerIsFailure()
	{
		foreach (['NO_MESSAGE_GIVEN', 'EXECUTION_ERROR: no device', 'BINARY_NOT_FOUND'] as $answer) {
			$this->setupSms('data://text/plain,'.rawurlencode($answer));

			$result = IntegrationsRegistry::runAction('sms', 'send', null, [
				'phone' => '79991234567', 'text' => 'test',
			]);

			$this->assertFalse($result->ok, "ответ '$answer' должен считаться ошибкой");
			$this->assertStringContainsString($answer, $result->message, 'ответ шлюза виден в отчёте');
		}
	}

	/** Формат ответа шлюза настраивается: successPattern из конфига */
	public function testGatewaySuccessPatternConfigurable()
	{
		Yii::$app->params['integrations'] = ['sms' => [
			'class' => SmsProvider::class,
			'url' => 'data://text/plain,OK%3A%20sent',
			'successPattern' => '/^OK\b/i',
		]];
		IntegrationsRegistry::reset();

		$this->assertTrue(IntegrationsRegistry::runAction('sms', 'send', null,
			['phone' => '79991234567', 'text' => 'test'])->ok);

		//ответ не совпал с шаблоном успеха => ошибка
		Yii::$app->params['integrations']['sms']['url'] = 'data://text/plain,QUEUED';
		IntegrationsRegistry::reset();
		$this->assertFalse(IntegrationsRegistry::runAction('sms', 'send', null,
			['phone' => '79991234567', 'text' => 'test'])->ok);
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
	 * Доступ следует модели авторизации ядра (docs/help/admin/setup.md).
	 * Тестовое окружение — полностью открытый режим (useRBAC=false,
	 * authorizedView=false): и просмотр панелей, и действия доступны всем,
	 * включая гостя — ровно как обычные view/edit в этом режиме.
	 */
	public function testAccessFollowsCoreModel()
	{
		$this->setupSms('data://text/plain,OK');
		$provider = IntegrationsRegistry::provider('sms');

		$this->assertFalse((bool)(Yii::$app->params['useRBAC'] ?? false), 'тест рассчитан на useRBAC=false');
		$this->assertFalse((bool)(Yii::$app->params['authorizedView'] ?? false), 'тест рассчитан на authorizedView=false');
		$this->assertTrue(Yii::$app->user->isGuest, 'тест рассчитан на гостя');

		//полностью открытый режим: всё доступно всем (как «всем можно всё»)
		$this->assertTrue(IntegrationsRegistry::userCanView($provider));
		$this->assertTrue(IntegrationsRegistry::userCanRun($provider, 'send'));
	}

	/**
	 * Доступ к интеграциям следует таблице setup.md по всем комбинациям
	 * authorizedView/useRBAC. Панель = «просмотр», действие = «изменение».
	 * (RBAC-права провайдеру не выданы, поэтому под useRBAC=true проверки
	 * по праву дают false — как у обычной операции без назначенного права.)
	 */
	public function testAccessMatrixAllModes()
	{
		$this->setupSms('data://text/plain,OK');
		$provider = IntegrationsRegistry::provider('sms');

		$origRbac = Yii::$app->params['useRBAC'] ?? false;
		$origView = Yii::$app->params['authorizedView'] ?? false;

		$user = new Users(['Ename' => 'Матрица', 'Login' => 'matrix.user']);
		$this->assertTrue($user->save(false));

		//проверка (view, run) для гостя и для вошедшего в заданном режиме
		$probe = function () use ($provider) {
			return [
				'view' => IntegrationsRegistry::userCanView($provider),
				'run' => IntegrationsRegistry::userCanRun($provider, 'send'),
			];
		};

		try {
			// authorizedView=false, useRBAC=false — всем всё
			Yii::$app->params['useRBAC'] = false;
			Yii::$app->params['authorizedView'] = false;
			$this->assertSame(['view' => true, 'run' => true], $probe(), 'открытый режим, гость');

			// authorizedView=true, useRBAC=false — только вошедшим
			Yii::$app->params['authorizedView'] = true;
			$this->assertSame(['view' => false, 'run' => false], $probe(), 'нужна аутентификация, гость');
			Yii::$app->user->login($user);
			$this->assertSame(['view' => true, 'run' => true], $probe(), 'нужна аутентификация, вошедший');
			Yii::$app->user->logout();

			// authorizedView=false, useRBAC=true — просмотр всем, действие по праву
			Yii::$app->params['useRBAC'] = true;
			Yii::$app->params['authorizedView'] = false;
			$this->assertSame(['view' => true, 'run' => false], $probe(), 'RBAC, просмотр открыт, гость');

			// authorizedView=true, useRBAC=true — всё по праву (не выдано => false)
			Yii::$app->params['authorizedView'] = true;
			Yii::$app->user->login($user);
			$this->assertSame(['view' => false, 'run' => false], $probe(), 'RBAC полный, без прав');
			Yii::$app->user->logout();
		} finally {
			Yii::$app->params['useRBAC'] = $origRbac;
			Yii::$app->params['authorizedView'] = $origView;
			if (!Yii::$app->user->isGuest) Yii::$app->user->logout();
		}
	}

	/**
	 * Под useRBAC=true доступ даёт адресное право ЛИБО глобальный зонтик
	 * view/edit — как у обычных операций ядра. Пользователю достаточно
	 * глобального edit для действий и view для панелей; отдельно
	 * integration-права раздавать не нужно.
	 */
	public function testGlobalUmbrellaGrantsUnderRbac()
	{
		$this->setupSms('data://text/plain,OK');
		$provider = IntegrationsRegistry::provider('sms');

		$origRbac = Yii::$app->params['useRBAC'] ?? false;
		$origView = Yii::$app->params['authorizedView'] ?? false;
		$origUser = Yii::$app->get('user');

		//заглушка компонента user с управляемым набором прав
		$stub = new class(['identityClass' => Users::class]) extends \yii\web\User {
			public array $granted = [];
			public function getIsGuest($checkSession = true) { return false; }
			public function can($permissionName, $params = [], $allowCaching = true) {
				return in_array($permissionName, $this->granted, true);
			}
		};

		try {
			//полный RBAC: и панель, и действие идут по праву (не по открытости)
			Yii::$app->params['useRBAC'] = true;
			Yii::$app->params['authorizedView'] = true;
			Yii::$app->set('user', $stub);

			//только глобальный edit — действия доступны, панели нет
			$stub->granted = ['edit'];
			$this->assertTrue(IntegrationsRegistry::userCanRun($provider, 'send'), 'глобальный edit -> действие');
			$this->assertFalse(IntegrationsRegistry::userCanView($provider), 'edit не даёт панель');

			//только глобальный view — панели доступны, действия нет
			$stub->granted = ['view'];
			$this->assertTrue(IntegrationsRegistry::userCanView($provider), 'глобальный view -> панель');
			$this->assertFalse(IntegrationsRegistry::userCanRun($provider, 'send'), 'view не даёт действие');

			//оба глобальных — и панель, и действие (сценарий с прода)
			$stub->granted = ['view', 'edit'];
			$this->assertTrue(IntegrationsRegistry::userCanView($provider));
			$this->assertTrue(IntegrationsRegistry::userCanRun($provider, 'send'));

			//адресное право по-прежнему работает без глобальных
			$stub->granted = ['edit-integration-sms-send'];
			$this->assertTrue(IntegrationsRegistry::userCanRun($provider, 'send'), 'адресное право');
			$this->assertFalse(IntegrationsRegistry::userCanView($provider), 'адресное на действие не даёт панель');
		} finally {
			Yii::$app->set('user', $origUser);
			Yii::$app->params['useRBAC'] = $origRbac;
			Yii::$app->params['authorizedView'] = $origView;
		}
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
	 * Рендер иконки у атрибута: ссылка на /integrations/action с prefill
	 * номера в имени формы. В тестовом (полностью открытом) режиме
	 * действие доступно, поэтому иконка рендерится.
	 */
	public function testAttributeActionsWidgetRender()
	{
		$this->setupSms('data://text/plain,OK');

		$user = new Users(['Ename' => 'Тест Виджет', 'Mobile' => '79991234567']);
		$this->assertTrue($user->save(false));

		$html = AttributeActionsWidget::widget([
			'model' => $user, 'attribute' => 'Mobile', 'value' => '79991234567',
		]);

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
