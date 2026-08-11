<?php

namespace tests\unit\models;

use app\models\Users;
use Codeception\Test\Unit;
use Yii;

/**
 * Проверка пароля пользователя (Users::validatePassword) с акцентом на
 * устойчивость к сбою LDAP: недоступный контроллер домена должен давать
 * управляемый отказ (false + authServiceError), а не исключение 500.
 */
class UsersValidatePasswordTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** был ли компонент ldap до теста (в тестовом конфиге его нет) */
	private bool $hadLdap = false;

	protected function _before()
	{
		$this->hadLdap = Yii::$app->has('ldap');
	}

	protected function _after()
	{
		//убираем стабы ldap, подставленные тестом
		if (!$this->hadLdap && Yii::$app->has('ldap')) {
			Yii::$app->set('ldap', null);
		}
	}

	/** Локальный пароль проверяется без обращения к LDAP */
	public function testLocalPassword()
	{
		$user = new Users(['Login' => 'local.user']);
		$user->password = password_hash('secret123', PASSWORD_DEFAULT);

		$this->assertTrue($user->validatePassword('secret123'));
		$this->assertFalse($user->validatePassword('wrong'));
		$this->assertFalse($user->authServiceError, 'локальная проверка - не сбой службы');
	}

	/**
	 * Пустой логин AD — тихий false без обращения к LDAP (и без сбоя
	 * службы). Детерминировано независимо от наличия ldap в окружении.
	 */
	public function testEmptyLoginNoLdapCall()
	{
		$user = new Users(['Login' => '']);
		$this->assertFalse($user->validatePassword('x'));
		$this->assertFalse($user->authServiceError, 'пустой логин - не сбой службы');
	}

	/**
	 * Ключевой сценарий: LDAP кидает исключение (недоступен сервер) —
	 * validatePassword НЕ пробрасывает 500, а возвращает false и
	 * помечает authServiceError
	 */
	public function testLdapUnavailableDoesNotThrow()
	{
		//стаб LdapService: authenticate() бросает как ldaprecord на
		//недоступном контроллере домена
		Yii::$app->set('ldap', new class extends \yii\base\Component {
			public function authenticate($login, $password)
			{
				throw new \Exception('Can\'t contact LDAP server');
			}
		});

		$user = new Users(['Login' => 'ad.user']);
		$result = $user->validatePassword('any-password');

		$this->assertFalse($result, 'сбой LDAP трактуется как непройденная проверка');
		$this->assertTrue($user->authServiceError, 'помечен сбой службы, а не неверный пароль');
	}

	/** Успешный ответ LDAP пропускает пользователя */
	public function testLdapSuccess()
	{
		Yii::$app->set('ldap', new class extends \yii\base\Component {
			public function authenticate($login, $password)
			{
				return $password === 'correct';
			}
		});

		$user = new Users(['Login' => 'ad.user']);
		$this->assertTrue($user->validatePassword('correct'));
		$this->assertFalse($user->validatePassword('nope'));
		$this->assertFalse($user->authServiceError);
	}
}
