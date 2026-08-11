<?php

namespace tests\unit\components\integrations;

use app\components\integrations\IntegrationsRegistry;
use app\components\integrations\providers\AdPasswordResetForm;
use app\components\integrations\providers\AdPasswordResetProvider;
use app\components\integrations\providers\AdUserProvider;
use app\components\integrations\providers\SmsProvider;
use app\models\IntegrationsLog;
use app\models\Techs;
use app\models\Users;
use Codeception\Test\Unit;
use Yii;

/**
 * Тесты интеграций ActiveDirectory (docs/dev/integrations.md):
 * панель-справка о учётке и именной сброс пароля с композицией через SMS.
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
	 * Провайдер сброса с подменённой записью в LDAP
	 * @param bool $ldapFails имитировать отказ AD
	 */
	private function makeResetProvider(bool $ldapFails = false): AdPasswordResetProvider
	{
		$provider = new class($ldapFails) extends AdPasswordResetProvider {
			public bool $ldapFails;
			public array $resetCalls = [];

			public function __construct(bool $ldapFails)
			{
				$this->ldapFails = $ldapFails;
			}

			protected function ldapResetPassword(string $targetLogin, string $password,
				bool $mustChange, array $credentials): void
			{
				if ($this->ldapFails) throw new \RuntimeException('нет прав на сброс');
				$this->resetCalls[] = compact('targetLogin', 'password', 'mustChange', 'credentials');
			}
		};
		$provider->id = 'ad-reset';
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

	/** Рендер панели: статус, смена/истечение пароля, DN, last logon */
	public function testAdPanelRender()
	{
		$provider = $this->makeAdProvider([
			'dn' => 'CN=Тест АД,OU=IT,DC=corp,DC=local',
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
		$this->assertStringContainsString('OU=IT,DC=corp,DC=local', $html);
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

	// ==================== AdPasswordResetProvider (сброс пароля) ====================

	/**
	 * Полный успешный сценарий через реестр: SMS отправлено ДО записи в AD,
	 * пароль сгенерирован и передан в LDAP, журнал связал шаги parent_id,
	 * пароль не попал ни в одну запись журнала
	 */
	public function testResetSuccessFlow()
	{
		$user = $this->makeUser();
		$provider = $this->makeResetProvider();
		$form = new AdPasswordResetForm(['password' => '', 'mustChange' => true]);
		$this->assertTrue($form->validate());

		$result = IntegrationsRegistry::runActionForm($provider, AdPasswordResetProvider::ACTION,
			$user, $form, ['login' => 'executor', 'password' => 'executor-secret']);

		$this->assertTrue($result->ok, $result->message);
		$this->assertStringContainsString('79991234567', $result->message);

		//LDAP вызван с валидным сгенерированным паролем от имени исполнителя
		$this->assertCount(1, $provider->resetCalls);
		$call = $provider->resetCalls[0];
		$this->assertSame('test.ad', $call['targetLogin']);
		$this->assertTrue($call['mustChange']);
		$this->assertSame('executor', $call['credentials']['login']);
		$password = $call['password'];
		$this->assertSame(AdPasswordResetProvider::GEN_LENGTH, strlen($password));

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

	/** Неудача SMS = останов: запись в AD не выполняется */
	public function testResetStopsWhenSmsFails()
	{
		//пустой ответ шлюза = ошибка отправки
		Yii::$app->params['integrations']['sms']['url'] = 'data://text/plain,';
		IntegrationsRegistry::reset();

		$user = $this->makeUser();
		$provider = $this->makeResetProvider();

		$result = IntegrationsRegistry::runActionForm($provider, AdPasswordResetProvider::ACTION,
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

		$result = IntegrationsRegistry::runActionForm($provider, AdPasswordResetProvider::ACTION,
			$user, new AdPasswordResetForm(), ['login' => 'executor', 'password' => 'x']);

		$this->assertFalse($result->ok);
		$this->assertStringContainsString('SMS отправлено, но пароль в AD НЕ изменён', $result->message);
		//SMS-шаг при этом в журнале успешен
		$child = IntegrationsLog::findOne(['parent_id' => $result->logId]);
		$this->assertSame('ok', $child->result);
	}

	/** Без мобильного номера сброс не выполняется (пароль некуда доставить) */
	public function testResetRequiresMobile()
	{
		$user = $this->makeUser(['Mobile' => '']);
		$provider = $this->makeResetProvider();

		$result = IntegrationsRegistry::runActionForm($provider, AdPasswordResetProvider::ACTION,
			$user, new AdPasswordResetForm(), ['login' => 'executor', 'password' => 'x']);

		$this->assertFalse($result->ok);
		$this->assertStringContainsString('мобильный номер', $result->message);
		$this->assertCount(0, $provider->resetCalls);
		$this->assertNull(IntegrationsLog::findOne(['parent_id' => $result->logId]), 'SMS не отправлялось');
	}

	/** Серверный вызов без кредов исполнителя отклоняется (L2+) */
	public function testResetRequiresCredentials()
	{
		$user = $this->makeUser();
		$provider = $this->makeResetProvider();

		$result = IntegrationsRegistry::runActionForm($provider, AdPasswordResetProvider::ACTION,
			$user, new AdPasswordResetForm(), null);

		$this->assertFalse($result->ok);
		$this->assertStringContainsString('учетные данные исполнителя', $result->message);
		$this->assertCount(0, $provider->resetCalls);
	}

	/** Генератор паролей: длина, все классы символов, без неоднозначных */
	public function testGeneratePassword()
	{
		$provider = new AdPasswordResetProvider();
		for ($i = 0; $i < 20; $i++) {
			$password = $provider->generatePassword();
			$this->assertSame(AdPasswordResetProvider::GEN_LENGTH, strlen($password));
			$this->assertMatchesRegularExpression('/[A-Z]/', $password);
			$this->assertMatchesRegularExpression('/[a-z]/', $password);
			$this->assertMatchesRegularExpression('/[0-9]/', $password);
			$this->assertDoesNotMatchRegularExpression('/[0O1lI]/', $password);
		}
	}

	/** Дескриптор действия: именной уровень (L2+), не standalone */
	public function testResetActionDescriptor()
	{
		$descriptor = (new AdPasswordResetProvider())->actions(null)[AdPasswordResetProvider::ACTION];
		$this->assertSame(AdPasswordResetProvider::LEVEL_PERSONAL, $descriptor['level']);
		$this->assertArrayNotHasKey('standalone', $descriptor);
		$this->assertSame(AdPasswordResetForm::class, $descriptor['form']);
	}

	/** Форма: пустой пароль допустим (генерация), короткий - нет */
	public function testResetFormValidation()
	{
		$this->assertTrue((new AdPasswordResetForm(['password' => '']))->validate());
		$this->assertTrue((new AdPasswordResetForm(['password' => 'LongEnough42']))->validate());
		$this->assertFalse((new AdPasswordResetForm(['password' => 'short']))->validate());
	}
}
