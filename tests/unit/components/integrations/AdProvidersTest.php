<?php

namespace tests\unit\components\integrations;

use app\components\integrations\IntegrationsRegistry;
use app\components\integrations\providers\AdComputerProvider;
use app\components\integrations\providers\AdCreateAccountForm;
use app\components\integrations\providers\AdPasswordResetForm;
use app\components\integrations\providers\AdRestoreAccountForm;
use app\components\integrations\providers\AdUserProvider;
use app\components\integrations\providers\SmsProvider;
use app\models\Comps;
use app\models\IntegrationsLog;
use app\models\Techs;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты интеграций ActiveDirectory (docs/dev/integrations.md):
 * панель-справка о учётке с кнопкой сброса и именной сброс пароля с
 * композицией через SMS (всё - один провайдер AdUserProvider).
 * LDAP подменяется подклассами (fetchAccount/ldapResetPassword), SMS -
 * настоящий провайдер с data://-шлюзом, журнал - настоящая БД
 * (транзакция с откатом). В сеть и в AD тесты не ходят.
 */
class AdProvidersTest extends Unit
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
		//реестр: настоящий SMS-провайдер для вложенных вызовов сброса
		Yii::$app->params['integrations'] = [
			'sms' => ['class' => SmsProvider::class, 'url' => 'data://text/plain,SMS-SENT'],
		];
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

	/** Провайдер справки с подменённым LDAP-запросом */
	private function makeAdProvider(?array $account): AdUserProvider
	{
		$provider = new class($account) extends AdUserProvider {
			public ?array $mockAccount;
			public array $fetchedLogins = [];

			public function __construct(?array $account)
			{
				$this->mockAccount = $account;
			}

			protected function fetchAccount(string $login): ?array
			{
				$this->fetchedLogins[] = $login;
				return $this->mockAccount;
			}
		};
		$provider->id = 'ad';
		$provider->config = [];
		return $provider;
	}

	/**
	 * Провайдер с подменёнными LDAP-операциями сброса (сеть не трогаем).
	 * @param bool $ldapFails имитировать отказ записи в AD (шаг 2)
	 * @param bool $verifyFails имитировать провал предпроверки (шаг 0)
	 */
	private function makeResetProvider(bool $ldapFails = false, bool $verifyFails = false): AdUserProvider
	{
		$provider = new class($ldapFails, $verifyFails) extends AdUserProvider {
			public bool $ldapFails;
			public bool $verifyFails;
			public array $verifyCalls = [];
			public array $resetCalls = [];

			public function __construct(bool $ldapFails, bool $verifyFails)
			{
				$this->ldapFails = $ldapFails;
				$this->verifyFails = $verifyFails;
			}

			protected function ldapVerify(string $targetLogin, array $credentials): void
			{
				$this->verifyCalls[] = compact('targetLogin', 'credentials');
				if ($this->verifyFails) throw new \RuntimeException('нет прав на сброс пароля этого пользователя');
			}

			protected function ldapResetPassword(string $targetLogin, string $password,
				bool $unlock, array $credentials): array
			{
				if ($this->ldapFails) throw new \RuntimeException('нет прав на сброс');
				$this->resetCalls[] = compact('targetLogin', 'password', 'unlock', 'credentials');
				//как настоящий LdapService: подтверждение смены отметки пароля
				return [
					'dn' => 'CN=Test,DC=corp,DC=local',
					'pwd_last_set_before' => 1700000000,
					'pwd_last_set_after' => 1800000000,
					'unlocked' => $unlock,
				];
			}
		};
		$provider->id = 'ad';
		$provider->config = [];
		return $provider;
	}

	private function makeUser(array $attributes = []): Users
	{
		$user = new Users(array_merge([
			'Ename' => 'Тест АД',
			'Login' => 'test.ad',
			'Mobile' => '79991234567',
		], $attributes));
		$this->assertTrue($user->save(false));
		return $user;
	}

	// ==================== AdUserProvider (панель-справка) ====================

	/** Применимость и привязка: сотрудник с логином AD, логин нормализуется */
	public function testAdAppliesToAndBinding()
	{
		$provider = $this->makeAdProvider(null);

		$user = new Users(['Login' => ' Ivanov ']);
		$this->assertTrue($provider->appliesTo($user));
		$this->assertSame('ivanov', $provider->binding($user));

		$this->assertFalse($provider->appliesTo(new Users()));
		$this->assertFalse($provider->appliesTo(new Techs()));
	}

	/** Рендер панели: статус, смена/истечение пароля, путь в дереве, last logon */
	public function testAdPanelRender()
	{
		$provider = $this->makeAdProvider([
			'dn' => 'CN=Тест АД,OU=IT,DC=corp,DC=local',
			'path' => ['IT'],
			'domain' => 'corp.local',
			'enabled' => true,
			'locked' => false,
			'password_last_set' => mktime(12, 0, 0, 1, 15, 2026),
			'password_expires' => mktime(12, 0, 0, 4, 15, 2026),
			'must_change_password' => false,
			'last_logon' => mktime(9, 30, 0, 2, 1, 2026),
			'account_expires' => 'never',
		]);

		$html = $provider->renderPanel(AdUserProvider::PANEL, new Users(['Login' => 'test.ad']));

		$this->assertStringContainsString('активна', $html);
		//путь по дереву - тот же рендер, что и в панели компьютера;
		//порядок «домен › контейнеры» проверяем по видимому пути: strpos по
		//всему HTML ловил бы OU= внутри DN-подсказки (title стоит раньше)
		$this->assertStringContainsString('corp.local &rsaquo; IT', $html);
		$this->assertStringContainsString('CN=Тест АД,OU=IT,DC=corp,DC=local', $html); //полный DN в подсказке
		$this->assertStringContainsString('15.01.2026', $html);
		$this->assertStringContainsString('15.04.2026', $html);
		$this->assertStringContainsString('01.02.2026', $html);
		$this->assertStringNotContainsString('учётка истекает', $html, 'never скрывает срок учётки');
		$this->assertSame(['test.ad'], $provider->fetchedLogins);
	}

	/** Заблокированная учётка с требованием смены пароля */
	public function testAdPanelRenderLocked()
	{
		$provider = $this->makeAdProvider([
			'dn' => 'CN=X,DC=corp,DC=local',
			'path' => [],
			'domain' => 'corp.local',
			'enabled' => true,
			'locked' => true,
			'password_last_set' => null,
			'password_expires' => 'never',
			'must_change_password' => true,
			'last_logon' => null,
			'account_expires' => 'never',
		]);

		$html = $provider->renderPanel(AdUserProvider::PANEL, new Users(['Login' => 'x']));
		$this->assertStringContainsString('заблокирована', $html);
		$this->assertStringContainsString('требуется смена пароля', $html);
		$this->assertStringContainsString('никогда', $html);
	}

	/** Учётка не найдена в AD */
	public function testAdPanelRenderNotFound()
	{
		$html = $this->makeAdProvider(null)
			->renderPanel(AdUserProvider::PANEL, new Users(['Login' => 'ghost']));
		$this->assertStringContainsString('не найдена', $html);
	}

	/**
	 * Кнопка сброса пароля живёт внутри панели: есть только у найденной
	 * в AD учётки (несуществующей сброс не предлагается), одинакова для
	 * всех пользователей (кэш панелей общий, доступ проверяет сервер);
	 * в компактном режиме и без SMS-провайдера кнопки нет
	 */
	public function testAdPanelResetButton()
	{
		$account = [
			'dn' => 'CN=X,DC=corp,DC=local', 'path' => [], 'domain' => 'corp.local',
			'enabled' => true, 'locked' => false, 'password_last_set' => null,
			'password_expires' => 'never', 'must_change_password' => false,
			'last_logon' => null, 'account_expires' => 'never',
		];
		$model = new Users(['Login' => 'test.ad']);

		//учётка найдена + SMS настроен (в _before) = кнопка есть
		$html = $this->makeAdProvider($account)->renderPanel(AdUserProvider::PANEL, $model);
		$this->assertStringContainsString('Сбросить пароль', $html);
		$this->assertStringContainsString('open-in-modal-form', $html, 'открывается в модалке');

		//учётка не найдена - сброс не предлагается (исходный симптом слияния)
		$html = $this->makeAdProvider(null)->renderPanel(AdUserProvider::PANEL, $model);
		$this->assertStringNotContainsString('Сбросить пароль', $html);

		//компактный режим (вложенные списки) - без кнопок действий
		$provider = $this->makeAdProvider($account);
		$provider->compact = true;
		$this->assertStringNotContainsString('Сбросить пароль',
			$provider->renderPanel(AdUserProvider::PANEL, $model));

		//без SMS-провайдера действия нет - нет и кнопки
		Yii::$app->params['integrations'] = [];
		$html = $this->makeAdProvider($account)->renderPanel(AdUserProvider::PANEL, $model);
		$this->assertStringNotContainsString('Сбросить пароль', $html);
	}

	// ==================== AdComputerProvider (справка о компьютере) ====================

	/** Провайдер справки о компьютере с подменённым LDAP-запросом */
	private function makeAdCompProvider(?array $computer): AdComputerProvider
	{
		$provider = new class($computer) extends AdComputerProvider {
			public ?array $mockComputer;
			public array $fetchedNames = [];

			public function __construct(?array $computer)
			{
				$this->mockComputer = $computer;
			}

			protected function fetchComputer(string $name): ?array
			{
				$this->fetchedNames[] = $name;
				return $this->mockComputer;
			}
		};
		$provider->id = 'ad-comp';
		$provider->config = [];
		return $provider;
	}

	/**
	 * Применимость: ОС с именем, по умолчанию только Windows (иначе каждая
	 * карточка Linux-машины давала бы напрасный запрос в AD)
	 */
	public function testAdCompAppliesToAndBinding()
	{
		$provider = $this->makeAdCompProvider(null);

		$windows = new Comps(['name' => ' PC-01 ', 'os' => 'Windows 10 Pro']);
		$this->assertTrue($provider->appliesTo($windows));
		$this->assertSame('pc-01', $provider->binding($windows));

		$this->assertFalse($provider->appliesTo(new Comps(['name' => 'srv', 'os' => 'Debian 12'])));
		$this->assertFalse($provider->appliesTo(new Comps(['os' => 'Windows 10'])));
		$this->assertFalse($provider->appliesTo(new Users(['Login' => 'x'])));

		//настройкой можно опрашивать и не-Windows
		$provider->config = ['windowsOnly' => false];
		$this->assertTrue($provider->appliesTo(new Comps(['name' => 'srv', 'os' => 'Debian 12'])));
	}

	/** Рендер панели: путь в дереве сверху вниз и группы */
	public function testAdCompPanelRender()
	{
		$provider = $this->makeAdCompProvider([
			'dn' => 'CN=PC-01,OU=Бухгалтерия,OU=Офис,DC=corp,DC=local',
			'path' => ['Офис', 'Бухгалтерия'],
			'domain' => 'corp.local',
			'groups' => [
				['name' => 'GPO-Office', 'dn' => 'CN=GPO-Office,OU=Groups,DC=corp,DC=local'],
				['name' => 'SCCM-Clients', 'dn' => 'CN=SCCM-Clients,OU=Groups,DC=corp,DC=local'],
			],
			'enabled' => true,
			'os' => 'Windows 10 Pro 10.0 (19045)',
			'dns_name' => 'pc-01.corp.local',
			'last_logon' => mktime(9, 30, 0, 2, 1, 2026),
			'description' => null,
		]);

		$html = $provider->renderPanel(AdComputerProvider::PANEL, new Comps(['name' => 'PC-01', 'os' => 'Windows 10']));

		//путь: домен, затем контейнеры сверху вниз - по видимому пути
		//(strpos по всему HTML ловил бы OU= внутри DN-подсказки)
		$this->assertStringContainsString('corp.local &rsaquo; Офис &rsaquo; Бухгалтерия', $html);
		$this->assertStringContainsString('GPO-Office', $html);
		$this->assertStringContainsString('SCCM-Clients', $html);
		//полный DN - в подсказке
		$this->assertStringContainsString('CN=PC-01,OU=', $html);
		$this->assertStringNotContainsString('отключена', $html);
		$this->assertSame(['pc-01'], $provider->fetchedNames);
	}

	/** Отключённая учётка компьютера и отсутствие групп */
	public function testAdCompPanelRenderDisabledWithoutGroups()
	{
		$provider = $this->makeAdCompProvider([
			'dn' => 'CN=OLD,CN=Computers,DC=corp,DC=local',
			'path' => ['Computers'],
			'domain' => 'corp.local',
			'groups' => [],
			'enabled' => false,
			'os' => '',
			'dns_name' => null,
			'last_logon' => null,
			'description' => null,
		]);

		$html = $provider->renderPanel(AdComputerProvider::PANEL, new Comps(['name' => 'OLD', 'os' => 'Windows 7']));
		$this->assertStringContainsString('учётка отключена', $html);
		$this->assertStringContainsString('групп нет', $html);
	}

	/** Компьютер не найден в AD */
	public function testAdCompPanelRenderNotFound()
	{
		$html = $this->makeAdCompProvider(null)
			->renderPanel(AdComputerProvider::PANEL, new Comps(['name' => 'ghost', 'os' => 'Windows 10']));
		$this->assertStringContainsString('не найдена в AD', $html);
	}

	/** Компактный режим доезжает до view панели компьютера */
	public function testAdCompPanelCompact()
	{
		$computer = [
			'dn' => 'CN=PC-01,OU=Офис,DC=corp,DC=local',
			'path' => ['Офис'], 'domain' => 'corp.local', 'groups' => [],
			'enabled' => true, 'os' => '', 'dns_name' => null,
			'last_logon' => null, 'description' => null,
		];
		$model = new Comps(['name' => 'PC-01', 'os' => 'Windows 10']);

		$provider = $this->makeAdCompProvider($computer);
		$this->assertStringContainsString('mt-1', $provider->renderPanel(AdComputerProvider::PANEL, $model));

		$provider = $this->makeAdCompProvider($computer);
		$provider->compact = true;
		$html = $provider->renderPanel(AdComputerProvider::PANEL, $model);
		$this->assertStringContainsString('mt-0', $html);
		$this->assertStringNotContainsString('mt-1', $html);
	}

	/**
	 * Один и тот же DN в панелях сотрудника и компьютера рисуется
	 * одинаково (общий шаблон ad-common/dn-path)
	 */
	public function testDnRenderedSameForUserAndComputer()
	{
		$dn = 'CN=X,OU=Челябинск,DC=corp,DC=local';
		$placement = ['dn' => $dn, 'path' => ['Челябинск'], 'domain' => 'corp.local'];

		$userHtml = $this->makeAdProvider($placement + [
			'enabled' => true, 'locked' => false, 'password_last_set' => null,
			'password_expires' => 'never', 'must_change_password' => false,
			'last_logon' => null, 'account_expires' => 'never',
		])->renderPanel(AdUserProvider::PANEL, new Users(['Login' => 'x']));

		$compHtml = $this->makeAdCompProvider($placement + [
			'groups' => [], 'enabled' => true, 'os' => '', 'dns_name' => null,
			'last_logon' => null, 'description' => null,
		])->renderPanel(AdComputerProvider::PANEL, new Comps(['name' => 'X', 'os' => 'Windows 10']));

		$path = '<small class="text-secondary" title="distinguishedName: '.$dn.'">';
		$this->assertStringContainsString($path, $userHtml);
		$this->assertStringContainsString($path, $compHtml);
		$this->assertStringContainsString('corp.local &rsaquo; Челябинск', $userHtml);
		$this->assertStringContainsString('corp.local &rsaquo; Челябинск', $compHtml);
	}

	// ==================== Сброс пароля (действие reset-password) ====================

	/**
	 * Полный успешный сценарий через реестр: SMS отправлено ДО записи в AD,
	 * пароль сгенерирован и передан в LDAP, журнал связал шаги parent_id,
	 * пароль не попал ни в одну запись журнала
	 */
	public function testResetSuccessFlow()
	{
		$user = $this->makeUser();
		$provider = $this->makeResetProvider();
		$form = new AdPasswordResetForm(['pronounceable' => true, 'length' => 14, 'unlock' => true]);
		$this->assertTrue($form->validate());

		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION,
			$user, $form, ['login' => 'executor', 'password' => 'executor-secret']);

		$this->assertTrue($result->ok, $result->message);
		$this->assertStringContainsString('79991234567', $result->message);
		$this->assertStringContainsString('разблокирована', $result->message);

		//предпроверка (шаг 0) вызвана перед записью
		$this->assertCount(1, $provider->verifyCalls);
		$this->assertSame('test.ad', $provider->verifyCalls[0]['targetLogin']);

		//LDAP вызван со сгенерированным паролем заданной длины, unlock передан
		$this->assertCount(1, $provider->resetCalls);
		$call = $provider->resetCalls[0];
		$this->assertSame('test.ad', $call['targetLogin']);
		$this->assertTrue($call['unlock']);
		$this->assertSame('executor', $call['credentials']['login']);
		$password = $call['password'];
		$this->assertSame(14, strlen($password), 'пароль запрошенной длины');

		//журнал: запись сброса + связанная запись SMS
		$parent = IntegrationsLog::findOne($result->logId);
		$this->assertSame('ok', $parent->result);
		$this->assertSame('executor', $parent->ext_login);
		$child = IntegrationsLog::findOne(['parent_id' => $parent->id]);
		$this->assertNotNull($child, 'SMS-шаг связан с инициатором');
		$this->assertSame('sms', $child->provider);
		$this->assertSame('ok', $child->result);

		//пароль не попал ни в одну запись журнала
		foreach (IntegrationsLog::find()->all() as $log) {
			foreach (['params', 'message'] as $field) {
				$this->assertStringNotContainsString($password, (string)$log->$field,
					"пароль в журнале ($log->provider/$log->action.$field)");
			}
		}
	}

	/**
	 * Провал предпроверки (шаг 0: неверные креды исполнителя или нет прав)
	 * = останов ДО SMS: пароль не отправляется и в AD не пишется
	 */
	public function testResetStopsWhenVerifyFails()
	{
		$user = $this->makeUser();
		$provider = $this->makeResetProvider(false, true); //verifyFails

		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION,
			$user, new AdPasswordResetForm(), ['login' => 'executor', 'password' => 'wrong']);

		$this->assertFalse($result->ok);
		$this->assertStringContainsString('SMS не отправлено', $result->message);
		$this->assertCount(1, $provider->verifyCalls, 'предпроверка вызвана');
		$this->assertCount(0, $provider->resetCalls, 'записи в AD нет');
		//SMS не отправлялось - связанной записи журнала нет
		$this->assertNull(IntegrationsLog::findOne(['parent_id' => $result->logId]), 'SMS не отправлялось');
	}

	/** Неудача SMS = останов: запись в AD не выполняется */
	public function testResetStopsWhenSmsFails()
	{
		//пустой ответ шлюза = ошибка отправки
		Yii::$app->params['integrations']['sms']['url'] = 'data://text/plain,';
		IntegrationsRegistry::reset();

		$user = $this->makeUser();
		$provider = $this->makeResetProvider();

		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION,
			$user, new AdPasswordResetForm(), ['login' => 'executor', 'password' => 'x']);

		$this->assertFalse($result->ok);
		$this->assertStringContainsString('НЕ изменён', $result->message);
		$this->assertCount(0, $provider->resetCalls, 'LDAP не должен вызываться');
	}

	/** Отказ AD после успешного SMS: честное сообщение, статус error */
	public function testResetLdapFailureAfterSms()
	{
		$user = $this->makeUser();
		$provider = $this->makeResetProvider(true);

		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION,
			$user, new AdPasswordResetForm(), ['login' => 'executor', 'password' => 'x']);

		$this->assertFalse($result->ok);
		$this->assertStringContainsString('SMS отправлено, но пароль в AD НЕ изменён', $result->message);
		//SMS-шаг при этом в журнале успешен
		$child = IntegrationsLog::findOne(['parent_id' => $result->logId]);
		$this->assertSame('ok', $child->result);
	}

	/** Ни один телефон не заполнен - сброс не выполняется (некуда доставить) */
	public function testResetRequiresPhone()
	{
		$user = $this->makeUser(['Mobile' => '', 'private_phone' => '']);
		$provider = $this->makeResetProvider();

		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION,
			$user, new AdPasswordResetForm(), ['login' => 'executor', 'password' => 'x']);

		$this->assertFalse($result->ok);
		$this->assertStringContainsString('телефон', $result->message);
		$this->assertCount(0, $provider->resetCalls);
		$this->assertNull(IntegrationsLog::findOne(['parent_id' => $result->logId]), 'SMS не отправлялось');
	}

	/**
	 * Мобильный пуст, но заполнен личный телефон - пароль доставляется на
	 * него (те же поля, что у SMS-провайдера: Mobile -> private_phone)
	 */
	public function testResetFallsBackToPrivatePhone()
	{
		$user = $this->makeUser(['Mobile' => '', 'private_phone' => '79997654321']);
		$provider = $this->makeResetProvider();

		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION,
			$user, new AdPasswordResetForm(), ['login' => 'executor', 'password' => 'x']);

		$this->assertTrue($result->ok, $result->message);
		$this->assertStringContainsString('79997654321', $result->message, 'доставка на личный телефон');
		$this->assertCount(1, $provider->resetCalls);
	}

	/** Серверный вызов без кредов исполнителя отклоняется (L2+) */
	public function testResetRequiresCredentials()
	{
		$user = $this->makeUser();
		$provider = $this->makeResetProvider();

		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION,
			$user, new AdPasswordResetForm(), null);

		$this->assertFalse($result->ok);
		$this->assertStringContainsString('учетные данные исполнителя', $result->message);
		$this->assertCount(0, $provider->resetCalls);
	}

	/**
	 * Полностью случайный пароль (снята галка «произносимый»): все классы
	 * символов, без неоднозначных, запрошенной длины
	 */
	public function testRandomPassword()
	{
		$provider = new AdUserProvider();
		foreach ([12, 20, 32] as $length) {
			$password = $provider->randomPassword($length);
			$this->assertSame($length, strlen($password));
			$this->assertMatchesRegularExpression('/[A-Z]/', $password);
			$this->assertMatchesRegularExpression('/[a-z]/', $password);
			$this->assertMatchesRegularExpression('/[0-9]/', $password);
			$this->assertMatchesRegularExpression('/[!@#$%*()\-_+=?]/', $password, 'спецсимвол');
			$this->assertDoesNotMatchRegularExpression('/[0O1lI]/', $password);
		}
	}

	/**
	 * Выбор типа пароля через форму: pronounceable=false даёт случайный
	 * (в нём есть спецсимвол; произносимый по политике тоже, но проверяем
	 * что рендерится именно случайный тип)
	 */
	public function testRandomPasswordViaForm()
	{
		$user = $this->makeUser();
		$provider = $this->makeResetProvider();
		$form = new AdPasswordResetForm(['pronounceable' => false, 'length' => 16, 'unlock' => false]);

		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION,
			$user, $form, ['login' => 'executor', 'password' => 'x']);

		$this->assertTrue($result->ok, $result->message);
		$this->assertStringNotContainsString('разблокирована', $result->message);
		$this->assertSame(16, strlen($provider->resetCalls[0]['password']));
		$this->assertFalse($provider->resetCalls[0]['unlock']);
	}

	/**
	 * Дескриптор действия: именной уровень (L2+), не standalone; кнопка
	 * живёт внутри панели (showInPanel=false - общий блок не дублирует)
	 */
	public function testResetActionDescriptor()
	{
		$descriptor = (new AdUserProvider())->actions(null)[AdUserProvider::ACTION];
		$this->assertSame(AdUserProvider::LEVEL_PERSONAL, $descriptor['level']);
		$this->assertArrayNotHasKey('standalone', $descriptor);
		$this->assertSame(AdPasswordResetForm::class, $descriptor['form']);
		$this->assertFalse($descriptor['showInPanel']);
	}

	/**
	 * Без настроенного SMS-провайдера действия сброса нет (панель при этом
	 * работает - зависимость от SMS переехала из isConfigured в actions)
	 */
	public function testResetActionRequiresSms()
	{
		Yii::$app->params['integrations'] = []; //нет sms
		$this->assertSame([], (new AdUserProvider())->actions(null));
	}

	/** Форма: длина не короче политики; дефолты pronounceable=on, unlock=off */
	public function testResetFormValidation()
	{
		$form = new AdPasswordResetForm();
		$this->assertTrue($form->validate());
		$this->assertTrue((bool)$form->pronounceable, 'по умолчанию произносимый');
		$this->assertFalse((bool)$form->unlock, 'по умолчанию без разблокировки');
		$this->assertSame(AdPasswordResetForm::MIN_LENGTH, $form->length);

		$this->assertTrue((new AdPasswordResetForm(['length' => 20]))->validate());
		$this->assertFalse((new AdPasswordResetForm(['length' => 8]))->validate(), 'короче политики');
		$this->assertFalse((new AdPasswordResetForm(['length' => 999]))->validate(), 'длиннее максимума');
	}

	// ==================== создание и восстановление учётки ====================

	const USERS_OU = 'OU=Staff,DC=corp,DC=local';
	const FIRED_OU = 'OU=Fired,DC=corp,DC=local';

	/**
	 * Провайдер с подменёнными LDAP-операциями создания/восстановления.
	 * $mock: account (для fetchAccount), verifyFails, loginBusy,
	 * createFails, restoreFails
	 */
	private function makeManageProvider(array $mock = [], ?array $config = null): AdUserProvider
	{
		$provider = new class($mock) extends AdUserProvider {
			public array $mock;
			public array $calls = [];

			public function __construct(array $mock)
			{
				$this->mock = $mock;
			}

			protected function fetchAccount(string $login): ?array
			{
				$this->calls['fetch'][] = $login;
				return $this->mock['account'] ?? null;
			}

			protected function ldapVerifyCreate(string $ouDn, array $groupDns, array $credentials): void
			{
				$this->calls['verifyCreate'][] = compact('ouDn', 'groupDns');
				if (!empty($this->mock['verifyFails'])) throw new \RuntimeException('нет права создавать пользователей');
			}

			protected function ldapLoginIsFree(string $login): bool
			{
				$this->calls['loginFree'][] = $login;
				if (isset($this->mock['busyLogins']))
					return !in_array($login, $this->mock['busyLogins'], true);
				return empty($this->mock['loginBusy']);
			}

			/** доступ к protected pickFreeLogin для тестов регламента коллизий */
			public function pickFree(string $login): ?string
			{
				return $this->pickFreeLogin($login);
			}

			protected function ldapCreateAccount(array $attrs, string $ouDn, array $groupDns,
				string $password, array $credentials): array
			{
				if (!empty($this->mock['createFails'])) throw new \RuntimeException('отказ создания');
				$this->calls['create'][] = compact('attrs', 'ouDn', 'groupDns', 'password');
				return ['dn' => 'CN='.$attrs['cn'].','.$ouDn, 'enabled' => true,
					'enable_error' => null, 'groups' => ['G1'], 'group_errors' => []];
			}

			protected function ldapVerifyManage(string $targetLogin, array $credentials): void
			{
				$this->calls['verifyManage'][] = $targetLogin;
				if (!empty($this->mock['verifyFails'])) throw new \RuntimeException('нет права включать учётку');
			}

			protected function ldapRestoreAccount(string $targetLogin, string $newParentDn,
				string $password, bool $unlock, array $credentials): array
			{
				if (!empty($this->mock['restoreFails'])) throw new \RuntimeException('отказ восстановления');
				$this->calls['restore'][] = compact('targetLogin', 'newParentDn', 'password', 'unlock');
				return ['dn_before' => 'CN=X,OU=IT,'.AdProvidersTest::FIRED_OU,
					'dn_after' => 'CN=X,'.$newParentDn,
					'pwd_last_set_before' => 1700000000, 'pwd_last_set_after' => 1800000000,
					'enabled' => true, 'unlocked' => $unlock, 'move_error' => null];
			}
		};
		$provider->id = 'ad';
		$provider->config = $config ?? [
			'usersOu' => static::USERS_OU,
			'dismissedOu' => static::FIRED_OU,
		];
		return $provider;
	}

	/** учётка в контейнере уволенных: отключена, лежит под dismissedOu */
	private function firedAccount(): array
	{
		return [
			'dn' => 'CN=Тест АД,OU=IT,'.static::FIRED_OU,
			'path' => ['Fired', 'IT'], 'domain' => 'corp.local',
			'enabled' => false, 'locked' => false, 'password_expired' => false,
			'password_last_set' => null, 'password_expires' => 'never',
			'must_change_password' => false, 'last_logon' => null, 'account_expires' => 'never',
		];
	}

	/**
	 * Дескрипторы действий: создание появляется при sms+usersOu,
	 * восстановление - при sms+usersOu+dismissedOu; оба именные (L2+),
	 * кнопки живут в панели
	 */
	public function testManageActionsDescriptors()
	{
		//без usersOu - только сброс
		$provider = new AdUserProvider();
		$this->assertArrayNotHasKey(AdUserProvider::ACTION_CREATE, $provider->actions(null));

		//usersOu без dismissedOu - создание есть, восстановления нет
		$provider = new AdUserProvider();
		$provider->config = ['usersOu' => static::USERS_OU];
		$actions = $provider->actions(null);
		$this->assertArrayHasKey(AdUserProvider::ACTION_CREATE, $actions);
		$this->assertArrayNotHasKey(AdUserProvider::ACTION_RESTORE, $actions);

		//полный конфиг - все три действия
		$provider = $this->makeManageProvider();
		$actions = $provider->actions(null);
		foreach ([AdUserProvider::ACTION_CREATE, AdUserProvider::ACTION_RESTORE] as $actionId) {
			$this->assertSame(AdUserProvider::LEVEL_PERSONAL, $actions[$actionId]['level']);
			$this->assertFalse($actions[$actionId]['showInPanel']);
		}
		$this->assertSame(AdCreateAccountForm::class, $actions[AdUserProvider::ACTION_CREATE]['form']);
		$this->assertSame(AdRestoreAccountForm::class, $actions[AdUserProvider::ACTION_RESTORE]['form']);

		//без SMS-провайдера действий нет вовсе
		Yii::$app->params['integrations'] = [];
		$this->assertSame([], $this->makeManageProvider()->actions(null));
	}

	/**
	 * Предложение логина по регламенту сквозных учёток: «фамилия.и»
	 * транслитом, не более 12 знаков (SAP); не влезло - обрезка с конца,
	 * разделительная точка обрезается обязательно
	 */
	public function testSuggestLogin()
	{
		$this->assertSame('ivanov.i', AdUserProvider::suggestLogin(new Users(['Ename' => 'Иванов Иван Иванович'])));
		$this->assertSame('schukin.yu', AdUserProvider::suggestLogin(new Users(['Ename' => 'Щукин Юрий Ефимович'])));
		$this->assertSame('petrov', AdUserProvider::suggestLogin(new Users(['Ename' => 'Петров'])), 'без имени - только фамилия');
		$this->assertSame('', AdUserProvider::suggestLogin(new Users(['Ename' => ''])));
		//существующий логин возвращается как есть (нормализованный)
		$this->assertSame('custom.login', AdUserProvider::suggestLogin(new Users(['Login' => ' Custom.Login ', 'Ename' => 'Иванов Иван'])));

		//примеры из регламента: обрезка до 12 с обязательным срезом точки
		$this->assertSame('popandopolo',
			AdUserProvider::suggestLogin(new Users(['Ename' => 'Попандополо Евстафий'])),
			'13 симв «popandopolo.e» -> срез до 12 + обязательный срез точки = 11');
		$this->assertSame('cherezzaborn',
			AdUserProvider::suggestLogin(new Users(['Ename' => 'Череззаборногузадерищенко Иван'])),
			'фамилия длиннее лимита - просто срез до 12');
	}

	/**
	 * Коллизии логинов по регламенту: «фамилия.и#», где # - номер
	 * однофамильца (с 2); суффикс влезает в лимит 12 за счёт ужатия базы
	 */
	public function testPickFreeLogin()
	{
		//smirnov.a занят -> второй однофамилец smirnov.a2
		$provider = $this->makeManageProvider(['busyLogins' => ['smirnov.a']]);
		$this->assertSame('smirnov.a2', $provider->pickFree('smirnov.a'));

		//popandopolo и однофамильцы 2..6 заняты -> popandopolo7 (ровно 12)
		$busy = ['popandopolo'];
		for ($n = 2; $n <= 6; $n++) $busy[] = 'popandopolo'.$n;
		$provider = $this->makeManageProvider(['busyLogins' => $busy]);
		$this->assertSame('popandopolo7', $provider->pickFree('popandopolo'));

		//cherezzaborn: под двузначный номер база ужимается до 10 -> cherezzabo15
		$busy = ['cherezzaborn'];
		for ($n = 2; $n <= 9; $n++) $busy[] = 'cherezzabor'.$n;   //однозначные: база 11
		for ($n = 10; $n <= 14; $n++) $busy[] = 'cherezzabo'.$n;  //двузначные: база 10
		$provider = $this->makeManageProvider(['busyLogins' => $busy]);
		$this->assertSame('cherezzabo15', $provider->pickFree('cherezzaborn'));

		//крайняя точка после ужатия базы обрезается («и» уходит вместе с ней)
		$provider = $this->makeManageProvider(['busyLogins' => ['abcdefghij.k']]);
		$this->assertSame('abcdefghij2', $provider->pickFree('abcdefghij.k'));
	}

	/**
	 * Применимость расширяется на сотрудника БЕЗ логина, только когда
	 * настроено создание и сотрудник не уволен
	 */
	public function testAppliesToWithoutLogin()
	{
		//создание настроено (sms из _before + usersOu)
		$provider = $this->makeManageProvider();
		$this->assertTrue($provider->appliesTo(new Users()));
		$this->assertFalse($provider->appliesTo(new Users(['Uvolen' => 1])), 'уволенному без логина панель не нужна');
		$this->assertTrue($provider->appliesTo(new Users(['Uvolen' => 1, 'Login' => 'x'])), 'с логином - всегда (справка)');

		//создание не настроено - как раньше: только с логином
		$provider = $this->makeAdProvider(null);
		$this->assertFalse($provider->appliesTo(new Users()));
	}

	/**
	 * Полный успешный сценарий создания: недеструктивная предпроверка,
	 * SMS ДО записи, создание с атрибутами из карточки, логин записан в
	 * карточку, пароль не попал в журнал
	 */
	public function testCreateSuccessFlow()
	{
		$user = $this->makeUser(['Login' => '', 'Ename' => 'Иванов Иван Иванович', 'Doljnost' => 'Инженер',
			'employee_id' => '00123', 'uid' => '744912345678', 'Email' => 'ivanov.i@corp.local']);
		$provider = $this->makeManageProvider();
		$form = new AdCreateAccountForm(['login' => 'ivanov.i', 'ou' => 'OU=IT,'.static::USERS_OU,
			'groups' => ['CN=G1,OU=Groups,DC=corp,DC=local'], 'length' => 14]);
		$this->assertTrue($form->validate(), implode('; ', $form->getErrorSummary(true)));

		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION_CREATE,
			$user, $form, ['login' => 'executor', 'password' => 'executor-secret']);

		$this->assertTrue($result->ok, $result->message);
		$this->assertStringContainsString('создана и включена', $result->message);

		//предпроверка вызвана с выбранными OU и группами
		$this->assertCount(1, $provider->calls['verifyCreate']);
		$this->assertSame('OU=IT,'.static::USERS_OU, $provider->calls['verifyCreate'][0]['ouDn']);

		//создание: атрибуты из карточки, пароль запрошенной длины
		$call = $provider->calls['create'][0];
		$this->assertSame('ivanov.i', $call['attrs']['samaccountname']);
		$this->assertSame('Иванов Иван Иванович', $call['attrs']['cn']);
		$this->assertSame('Иванов', $call['attrs']['sn']);
		$this->assertSame('Иван Иванович', $call['attrs']['givenname']);
		$this->assertSame('Инженер', $call['attrs']['title']);
		//поля схемы синхронизации inventory-to-ad.ps1 - чтобы первый же
		//прогон не переписал созданную учётку
		$this->assertSame('00123', $call['attrs']['employeeid'], 'табельный (EmployeeID)');
		$this->assertSame('744912345678', $call['attrs']['admindescription'], 'ИНН/uid (adminDescription)');
		$this->assertSame('ivanov.i@corp.local', $call['attrs']['mail']);
		$this->assertSame('+7(999)123-4567', $call['attrs']['mobile'], 'мобильный в формате синхронизации');
		$this->assertSame(14, strlen($call['password']));

		//логин записан в карточку сотрудника
		$this->assertSame('ivanov.i', Users::findOne($user->id)->Login);

		//журнал: SMS-шаг связан, пароль не попал ни в одну запись
		$parent = IntegrationsLog::findOne($result->logId);
		$this->assertSame('ok', $parent->result);
		$child = IntegrationsLog::findOne(['parent_id' => $parent->id]);
		$this->assertSame('sms', $child->provider);
		foreach (IntegrationsLog::find()->all() as $log) {
			foreach (['params', 'message'] as $field) {
				$this->assertStringNotContainsString($call['password'], (string)$log->$field);
			}
		}
	}

	/** Провал предпроверки создания = останов ДО SMS, ничего не создаётся */
	public function testCreateStopsWhenVerifyFails()
	{
		$user = $this->makeUser(['Login' => '']);
		$provider = $this->makeManageProvider(['verifyFails' => true]);
		$form = new AdCreateAccountForm(['login' => 'test.new', 'ou' => static::USERS_OU]);

		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION_CREATE,
			$user, $form, ['login' => 'executor', 'password' => 'wrong']);

		$this->assertFalse($result->ok);
		$this->assertStringContainsString('SMS не отправлено', $result->message);
		$this->assertArrayNotHasKey('create', $provider->calls);
		$this->assertNull(IntegrationsLog::findOne(['parent_id' => $result->logId]), 'SMS не отправлялось');
	}

	/** Неудача SMS = останов: учётка не создаётся */
	public function testCreateStopsWhenSmsFails()
	{
		Yii::$app->params['integrations']['sms']['url'] = 'data://text/plain,';
		IntegrationsRegistry::reset();

		$user = $this->makeUser(['Login' => '']);
		$provider = $this->makeManageProvider();
		$form = new AdCreateAccountForm(['login' => 'test.new', 'ou' => static::USERS_OU]);

		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION_CREATE,
			$user, $form, ['login' => 'executor', 'password' => 'x']);

		$this->assertFalse($result->ok);
		$this->assertStringContainsString('НЕ создана', $result->message);
		$this->assertArrayNotHasKey('create', $provider->calls);
	}

	/** Отказ AD после успешного SMS: честное сообщение, логин НЕ записан */
	public function testCreateLdapFailureAfterSms()
	{
		$user = $this->makeUser(['Login' => '']);
		$provider = $this->makeManageProvider(['createFails' => true]);
		$form = new AdCreateAccountForm(['login' => 'test.new', 'ou' => static::USERS_OU]);

		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION_CREATE,
			$user, $form, ['login' => 'executor', 'password' => 'x']);

		$this->assertFalse($result->ok);
		$this->assertStringContainsString('SMS отправлено, но учётка в AD НЕ создана', $result->message);
		$this->assertSame('', Users::findOne($user->id)->Login, 'логин не записан');
		//SMS-шаг при этом в журнале успешен
		$this->assertSame('ok', IntegrationsLog::findOne(['parent_id' => $result->logId])->result);
	}

	/** Занятый логин отсекается предпроверкой до SMS */
	public function testCreateRejectsBusyLogin()
	{
		$user = $this->makeUser(['Login' => '']);
		$provider = $this->makeManageProvider(['loginBusy' => true]);
		$form = new AdCreateAccountForm(['login' => 'test.new', 'ou' => static::USERS_OU]);

		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION_CREATE,
			$user, $form, ['login' => 'executor', 'password' => 'x']);

		$this->assertFalse($result->ok);
		$this->assertStringContainsString('уже существует', $result->message);
		$this->assertNull(IntegrationsLog::findOne(['parent_id' => $result->logId]), 'SMS не отправлялось');
	}

	/** OU вне настроенного корня отклоняется до любых обращений к AD */
	public function testCreateRejectsForeignOu()
	{
		$user = $this->makeUser(['Login' => '']);
		$provider = $this->makeManageProvider();
		$form = new AdCreateAccountForm(['login' => 'test.new', 'ou' => 'OU=Admins,DC=corp,DC=local']);

		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION_CREATE,
			$user, $form, ['login' => 'executor', 'password' => 'x']);

		$this->assertFalse($result->ok);
		$this->assertStringContainsString('вне разрешённых корней', $result->message);
		$this->assertArrayNotHasKey('verifyCreate', $provider->calls);
	}

	/** Уволенному в инвентаризации сотруднику учётка не создаётся */
	public function testCreateRejectsArchived()
	{
		$user = $this->makeUser(['Login' => '', 'Uvolen' => 1]);
		$provider = $this->makeManageProvider();
		$form = new AdCreateAccountForm(['login' => 'test.new', 'ou' => static::USERS_OU]);

		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION_CREATE,
			$user, $form, ['login' => 'executor', 'password' => 'x']);

		$this->assertFalse($result->ok);
		$this->assertStringContainsString('уволен', $result->message);
		$this->assertArrayNotHasKey('verifyCreate', $provider->calls);
	}

	/**
	 * Полный успешный сценарий восстановления: состояние перепроверено
	 * (отключена + в контейнере уволенных), SMS ДО записи, восстановление
	 * с целевым OU и разблокировкой
	 */
	public function testRestoreSuccessFlow()
	{
		$user = $this->makeUser();
		$provider = $this->makeManageProvider(['account' => $this->firedAccount()]);
		$form = new AdRestoreAccountForm(['ou' => 'OU=IT,'.static::USERS_OU, 'unlock' => true, 'length' => 15]);
		$this->assertTrue($form->validate(), implode('; ', $form->getErrorSummary(true)));

		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION_RESTORE,
			$user, $form, ['login' => 'executor', 'password' => 'executor-secret']);

		$this->assertTrue($result->ok, $result->message);
		$this->assertStringContainsString('включена и возвращена', $result->message);

		//предпроверка прав вызвана до записи
		$this->assertSame(['test.ad'], $provider->calls['verifyManage']);

		//восстановление: цель, OU, разблокировка, пароль запрошенной длины
		$call = $provider->calls['restore'][0];
		$this->assertSame('test.ad', $call['targetLogin']);
		$this->assertSame('OU=IT,'.static::USERS_OU, $call['newParentDn']);
		$this->assertTrue($call['unlock']);
		$this->assertSame(15, strlen($call['password']));

		//журнал: SMS-шаг связан, пароль не попал в журнал
		$child = IntegrationsLog::findOne(['parent_id' => $result->logId]);
		$this->assertSame('sms', $child->provider);
		foreach (IntegrationsLog::find()->all() as $log) {
			$this->assertStringNotContainsString($call['password'], (string)$log->params);
		}
	}

	/**
	 * Восстановление отклоняет неподходящие состояния: учётки нет,
	 * учётка включена, учётка отключена не в контейнере уволенных
	 */
	public function testRestoreRejectsWrongState()
	{
		$user = $this->makeUser();
		$form = new AdRestoreAccountForm(['ou' => static::USERS_OU]);
		$credentials = ['login' => 'executor', 'password' => 'x'];

		//учётки нет
		$provider = $this->makeManageProvider(['account' => null]);
		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION_RESTORE, $user, $form, $credentials);
		$this->assertFalse($result->ok);
		$this->assertStringContainsString('не найдена', $result->message);

		//учётка включена
		$provider = $this->makeManageProvider(['account' => ['enabled' => true, 'dn' => 'CN=X,'.static::USERS_OU] + $this->firedAccount()]);
		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION_RESTORE, $user, $form, $credentials);
		$this->assertFalse($result->ok);
		$this->assertStringContainsString('не требуется', $result->message);

		//отключена, но не в контейнере уволенных
		$provider = $this->makeManageProvider(['account' => ['dn' => 'CN=X,OU=IT,'.static::USERS_OU] + $this->firedAccount()]);
		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION_RESTORE, $user, $form, $credentials);
		$this->assertFalse($result->ok);
		$this->assertStringContainsString('не в контейнере уволенных', $result->message);
		$this->assertArrayNotHasKey('restore', $provider->calls);
	}

	/**
	 * Кнопка создания живёт внутри панели: у активного сотрудника без
	 * учётки в AD (в т.ч. вовсе без логина); уволенному и в compact не
	 * предлагается
	 */
	public function testPanelCreateButton()
	{
		//логин задан, но учётки в AD нет
		$html = $this->makeManageProvider(['account' => null])
			->renderPanel(AdUserProvider::PANEL, new Users(['Login' => 'ghost']));
		$this->assertStringContainsString('не найдена', $html);
		$this->assertStringContainsString('Создать учётную запись', $html);
		$this->assertStringContainsString('create-account', $html);

		//логина нет вовсе - панель объясняет и предлагает создать
		$html = $this->makeManageProvider(['account' => null])
			->renderPanel(AdUserProvider::PANEL, new Users(['Ename' => 'Иванов Иван']));
		$this->assertStringContainsString('логин AD не задан', $html);
		$this->assertStringContainsString('Создать учётную запись', $html);
		$this->assertStringContainsString('ivanov.i', $html, 'предложенный логин в prefill кнопки');

		//уволенному не предлагается
		$html = $this->makeManageProvider(['account' => null])
			->renderPanel(AdUserProvider::PANEL, new Users(['Login' => 'ghost', 'Uvolen' => 1]));
		$this->assertStringNotContainsString('Создать', $html);

		//compact - без кнопок
		$provider = $this->makeManageProvider(['account' => null]);
		$provider->compact = true;
		$this->assertStringNotContainsString('Создать',
			$provider->renderPanel(AdUserProvider::PANEL, new Users(['Login' => 'ghost'])));

		//создание не настроено (нет usersOu) - кнопки нет
		$html = $this->makeAdProvider(null)->renderPanel(AdUserProvider::PANEL, new Users(['Login' => 'ghost']));
		$this->assertStringNotContainsString('Создать', $html);
	}

	/**
	 * Кнопка восстановления - только у отключённой учётки в контейнере
	 * уволенных; сброс пароля ей не предлагается; целевое OU в prefill -
	 * зеркало пути увольнения
	 */
	public function testPanelRestoreButton()
	{
		$model = new Users(['Login' => 'test.ad']);

		$html = $this->makeManageProvider(['account' => $this->firedAccount()])
			->renderPanel(AdUserProvider::PANEL, $model);
		$this->assertStringContainsString('в уволенных', $html);
		$this->assertStringContainsString('Восстановить учётную запись', $html);
		$this->assertStringNotContainsString('Сбросить пароль', $html, 'отключённой уволенной учётке сброс не предлагается');
		//зеркальный путь: OU=IT,OU=Fired -> OU=IT,OU=Staff (urlencoded в prefill)
		$this->assertStringContainsString(urlencode('OU=IT,'.static::USERS_OU), $html);

		//отключена, но не в уволенных - восстановления нет, сброс есть
		$html = $this->makeManageProvider(['account' => ['dn' => 'CN=X,OU=IT,'.static::USERS_OU] + $this->firedAccount()])
			->renderPanel(AdUserProvider::PANEL, $model);
		$this->assertStringNotContainsString('Восстановить', $html);
		$this->assertStringContainsString('Сбросить пароль', $html);

		//уволенному сотруднику восстановление не предлагается
		$html = $this->makeManageProvider(['account' => $this->firedAccount()])
			->renderPanel(AdUserProvider::PANEL, new Users(['Login' => 'test.ad', 'Uvolen' => 1]));
		$this->assertStringNotContainsString('Восстановить', $html);
	}

	/**
	 * Пары корней «рабочий ↔ уволенные» (ouPairs, зеркало конфига
	 * $inventory2ad_sync скрипта увольнения): создание доступно во всех
	 * users-корнях, восстановление зеркалит и проверяется СТРОГО в
	 * рамках своей пары - без угадывания
	 */
	public function testOuPairs()
	{
		$pairs = ['ouPairs' => [
			['users' => 'OU=Пользователи,DC=azimuth,DC=local',
				'dismissed' => 'OU=Азимут,OU=Уволенные,DC=azimuth,DC=local'],
			['users' => 'OU=External,DC=azimuth,DC=local',
				'dismissed' => 'OU=External,OU=Уволенные,DC=azimuth,DC=local'],
		]];
		$credentials = ['login' => 'executor', 'password' => 'x'];

		//оба действия доступны
		$actions = $this->makeManageProvider([], $pairs)->actions(null);
		$this->assertArrayHasKey(AdUserProvider::ACTION_CREATE, $actions);
		$this->assertArrayHasKey(AdUserProvider::ACTION_RESTORE, $actions);

		//создание: OU под вторым корнем (контрагенты) проходит
		$user = $this->makeUser(['Login' => '']);
		$provider = $this->makeManageProvider([], $pairs);
		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION_CREATE, $user,
			new AdCreateAccountForm(['login' => 'test.new', 'ou' => 'OU=Подрядчики,OU=External,DC=azimuth,DC=local']),
			$credentials);
		$this->assertTrue($result->ok, $result->message);

		//вне обоих корней - отказ
		$provider = $this->makeManageProvider([], $pairs);
		$result = IntegrationsRegistry::runActionForm($provider, AdUserProvider::ACTION_CREATE, $user,
			new AdCreateAccountForm(['login' => 'test.new2', 'ou' => 'OU=Admins,DC=azimuth,DC=local']),
			$credentials);
		$this->assertFalse($result->ok);
		$this->assertStringContainsString('вне разрешённых корней', $result->message);

		//панель уволенного контрагента: зеркало строго в External-корень
		//своей пары (не в первый корень)
		$account = $this->firedAccount();
		$account['dn'] = 'CN=Тест АД,OU=Подрядчики,OU=External,OU=Уволенные,DC=azimuth,DC=local';
		$html = $this->makeManageProvider(['account' => $account], $pairs)
			->renderPanel(AdUserProvider::PANEL, new Users(['Login' => 'test.ad']));
		$this->assertStringContainsString('Восстановить учётную запись', $html);
		$this->assertStringContainsString(urlencode('OU=Подрядчики,OU=External,DC=azimuth,DC=local'), $html);

		//восстановление в OU чужой пары отклоняется
		$restorer = $this->makeManageProvider(['account' => $account], $pairs);
		$result = IntegrationsRegistry::runActionForm($restorer, AdUserProvider::ACTION_RESTORE,
			$this->makeUser(),
			new AdRestoreAccountForm(['ou' => 'OU=Пользователи,DC=azimuth,DC=local']), $credentials);
		$this->assertFalse($result->ok);
		$this->assertStringContainsString('вне корня учёток этой пары', $result->message);
		$this->assertArrayNotHasKey('restore', $restorer->calls);

		//в свой корень - проходит
		$restorer = $this->makeManageProvider(['account' => $account], $pairs);
		$result = IntegrationsRegistry::runActionForm($restorer, AdUserProvider::ACTION_RESTORE,
			$this->makeUser(),
			new AdRestoreAccountForm(['ou' => 'OU=Подрядчики,OU=External,DC=azimuth,DC=local']), $credentials);
		$this->assertTrue($result->ok, $result->message);
		$this->assertSame('OU=Подрядчики,OU=External,DC=azimuth,DC=local',
			$restorer->calls['restore'][0]['newParentDn']);
	}

	/**
	 * Формат телефонов - 1:1 как correctMobile/correctPhonesList скрипта
	 * синхронизации (lib_funcs.ps1): иначе прогон синхронизации
	 * переформатирует mobile созданной учётки
	 */
	public function testSyncPhoneFormat()
	{
		$this->assertSame('+7(912)345-6789', AdUserProvider::syncPhoneFormat('79123456789'));
		$this->assertSame('+7(912)345-6789', AdUserProvider::syncPhoneFormat('89123456789'), '8 -> 7');
		$this->assertSame('+7(912)345-6789', AdUserProvider::syncPhoneFormat('+7 912 345-67-89'), 'пробелы/тире/плюс вычищаются');
		$this->assertSame('+7(912)345-6789', AdUserProvider::syncPhoneFormat('7(912)3456789'), 'скобки уже на месте');
		$this->assertSame('123', AdUserProvider::syncPhoneFormat('123'), 'короткие как есть');
		$this->assertSame('+375291234567', AdUserProvider::syncPhoneFormat('810375291234567'), 'международный набор через 810');

		//список: каждый нормализуется, сборка через запятую
		$this->assertSame('+7(912)345-6789,+7(999)123-4567',
			AdUserProvider::syncPhonesFormat('89123456789, 79991234567'));
	}

	/** Формы: обязательность логина/OU, нормализация и формат логина */
	public function testManageFormsValidation()
	{
		$this->assertFalse((new AdCreateAccountForm())->validate(), 'логин и OU обязательны');
		$this->assertTrue((new AdCreateAccountForm(['login' => 'Ivanov.I ', 'ou' => static::USERS_OU]))->validate());

		$form = new AdCreateAccountForm(['login' => ' Ivanov.I ', 'ou' => static::USERS_OU]);
		$form->validate();
		$this->assertSame('ivanov.i', $form->login, 'логин нормализуется');

		$this->assertFalse((new AdCreateAccountForm(['login' => 'иванов', 'ou' => static::USERS_OU]))->validate(), 'только латиница');
		$this->assertFalse((new AdCreateAccountForm(['login' => '1abc', 'ou' => static::USERS_OU]))->validate(), 'с буквы');
		$this->assertTrue((new AdCreateAccountForm(['login' => str_repeat('a', 12), 'ou' => static::USERS_OU]))->validate(), '12 символов - предел');
		$this->assertFalse((new AdCreateAccountForm(['login' => str_repeat('a', 13), 'ou' => static::USERS_OU]))->validate(), 'не более 12 символов (SAP)');

		$this->assertFalse((new AdRestoreAccountForm())->validate(), 'OU обязателен');
		$this->assertTrue((new AdRestoreAccountForm(['ou' => static::USERS_OU]))->validate());
	}
}
