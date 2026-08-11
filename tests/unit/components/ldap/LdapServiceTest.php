<?php

namespace tests\unit\components\ldap;

use app\components\ldap\LdapService;
use Codeception\Test\Unit;

/**
 * Юнит-тесты LdapService — чистая логика без обращения к LDAP:
 * конвертация Windows FILETIME, формирование bind-имени, отказ на пустом
 * пароле. Сетевые методы (authenticate/accountInfo/resetPassword)
 * проверяются против живого DC консольной командой `yii ldap/*`.
 */
class LdapServiceTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** доступ к protected-методам через замыкание */
	private function call(LdapService $service, string $method, ...$args)
	{
		$fn = \Closure::bind(fn() => $this->$method(...$args), $service, LdapService::class);
		return $fn();
	}

	private function service(array $connection = []): LdapService
	{
		$s = new LdapService();
		$s->connection = $connection;
		return $s;
	}

	/** Пустой пароль не проверяется bind'ом (защита от anonymous-bind) */
	public function testEmptyPasswordRejected()
	{
		//не должно быть обращения к сети: пустой пароль отсекается сразу
		$this->assertFalse($this->service()->authenticate('someone', ''));
	}

	/** bindName добавляет account_suffix, если в логине нет '@' */
	public function testBindName()
	{
		$s = $this->service(['account_suffix' => '@corp.local']);

		$this->assertSame('ivanov@corp.local', $this->call($s, 'bindName', 'ivanov'));
		$this->assertSame('ivanov@corp.local', $this->call($s, 'bindName', ' ivanov '));
		//уже с доменом - не трогаем
		$this->assertSame('ivanov@other.local', $this->call($s, 'bindName', 'ivanov@other.local'));

		//без суффикса в конфиге - как есть
		$this->assertSame('ivanov', $this->call($this->service(), 'bindName', 'ivanov'));
	}

	/** Windows FILETIME → unix timestamp */
	public function testWinTimeToUnix()
	{
		$s = $this->service();

		//известное соответствие: 2021-01-01 00:00:00 UTC
		$unix = gmmktime(0, 0, 0, 1, 1, 2021);
		$filetime = ($unix + 11644473600) * 10000000;
		$this->assertSame($unix, $this->call($s, 'winTimeToUnix', (string)$filetime));

		//пустое/0/максимум => null
		$this->assertNull($this->call($s, 'winTimeToUnix', '0'));
		$this->assertNull($this->call($s, 'winTimeToUnix', null));
		$this->assertNull($this->call($s, 'winTimeToUnix', '9223372036854775807'));
	}

	/** winTimeOrNever: разные трактовки «никогда» */
	public function testWinTimeOrNever()
	{
		$s = $this->service();

		//null остаётся null (атрибут отсутствует)
		$this->assertNull($this->call($s, 'winTimeOrNever', null, false));

		//максимум = never (срок пароля «не истекает»)
		$this->assertSame('never', $this->call($s, 'winTimeOrNever', '9223372036854775807', false));

		//accountExpires: 0 тоже = never (zeroIsNever=true)
		$this->assertSame('never', $this->call($s, 'winTimeOrNever', '0', true));

		//реальное значение конвертируется
		$unix = gmmktime(12, 0, 0, 6, 15, 2026);
		$filetime = ($unix + 11644473600) * 10000000;
		$this->assertSame($unix, $this->call($s, 'winTimeOrNever', (string)$filetime, false));
	}

	/** Конфиг соединения приводится к терминам LdapRecord с дефолтами */
	public function testConnectionConfigDefaults()
	{
		$cfg = $this->call($this->service(['hosts' => ['dc.local'], 'base_dn' => 'DC=x']), 'connectionConfig');

		$this->assertSame(['dc.local'], $cfg['hosts']);
		$this->assertSame('DC=x', $cfg['base_dn']);
		$this->assertSame(389, $cfg['port']);       //дефолт
		$this->assertSame(5, $cfg['timeout']);      //дефолт
		$this->assertFalse($cfg['use_ssl']);
	}
}
