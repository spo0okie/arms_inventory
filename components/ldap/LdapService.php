<?php

namespace app\components\ldap;

use LdapRecord\Connection;
use LdapRecord\Container;
use LdapRecord\Models\ActiveDirectory\User as AdUser;
use Yii;
use yii\base\Component;

/**
 * Единственная точка приложения, знающая о конкретной LDAP-библиотеке
 * (сейчас — directorytree/ldaprecord). Всё остальное (аутентификация в
 * Users::validatePassword, интеграции AD в
 * components/integrations/providers/Ad*Provider) ходит в AD только через
 * этот сервис. Смена библиотеки = правка одного этого класса.
 *
 * Регистрируется как компонент `ldap` (config/web.php ← config/ldap.php):
 * ```php
 * 'ldap' => [
 *     'class' => \app\components\ldap\LdapService::class,
 *     'connection' => [ hosts, port, base_dn, username, password,
 *                       use_ssl, use_tls, account_suffix, options, timeout ],
 * ],
 * ```
 *
 * Сервисная учётка (`connection.username/password`) — read-only, для
 * аутентификации и чтения справки. Запись (сброс пароля) выполняется
 * отдельным соединением под личными кредами исполнителя — см.
 * {@see resetPassword()}.
 *
 * Соединение ленивое: конструирование компонента НЕ ходит в сеть
 * (в отличие от прежнего adldap2 autoconnect), поэтому недоступность DC
 * не роняет обращение к `Yii::$app->ldap` само по себе.
 */
class LdapService extends Component
{
	/** имя соединения сервисной учётки в LdapRecord Container */
	const CONN_SERVICE = 'arms-service';

	/** @var array конфиг соединения сервисной учётки */
	public array $connection = [];

	/** @var Connection|null ленивое соединение сервисной учётки */
	private ?Connection $serviceConnection = null;

	/**
	 * Проверить логин/пароль пользователя в AD (bind под его учёткой).
	 * @return bool true - креды верны; false - неверны
	 * @throws \LdapRecord\LdapRecordException при недоступности сервера
	 *   (ловит вызывающий: Users::validatePassword трактует как сбой службы)
	 */
	public function authenticate(string $login, string $password): bool
	{
		//пустой пароль нельзя проверять bind'ом: сервер может ответить
		//"anonymous bind OK" и вернуть true (ложный успех)
		if ($password === '') return false;

		return $this->serviceConnection()->auth()->attempt($this->bindName($login), $password);
	}

	/**
	 * Справка об учётке AD (для панели AdUserProvider), нормализованная.
	 * @return array|null null = учётка не найдена; иначе:
	 *   dn, enabled(bool), locked(bool), password_last_set(unix ts|null),
	 *   password_expires(unix ts|null|'never'), must_change_password(bool),
	 *   last_logon(unix ts|null), account_expires(unix ts|null|'never')
	 * @throws \LdapRecord\LdapRecordException при недоступности сервера
	 */
	public function accountInfo(string $login): ?array
	{
		$this->serviceConnection(); //регистрируем соединение в Container для AdUser::on()

		/** @var AdUser|null $user */
		$user = AdUser::on(static::CONN_SERVICE)
			//вычисляемый атрибут срока пароля по умолчанию не отдаётся
			->select(['*', 'msds-userpasswordexpirytimecomputed'])
			->where('samaccountname', '=', $login)
			->first();
		if (!is_object($user)) return null;

		//читаем СЫРЫЕ значения (getAttributes) — модельные касты AD
		//(pwdlastset/accountexpires как windows-int) вернули бы Carbon,
		//а нам нужен исходный FILETIME для единообразной конвертации
		$raw = $user->getAttributes();
		$first = static fn(string $key) => $raw[$key][0] ?? null;

		$uac = (int)$first('useraccountcontrol');
		$pwdLastSet = $first('pwdlastset');

		return [
			'dn' => $user->getDn(),
			'enabled' => !($uac & 0x2), //ACCOUNTDISABLE
			'locked' => (int)$first('lockouttime') > 0,
			'password_last_set' => $this->winTimeToUnix($pwdLastSet),
			'password_expires' => $this->winTimeOrNever($first('msds-userpasswordexpirytimecomputed')),
			'must_change_password' => (string)$pwdLastSet === '0',
			'last_logon' => $this->winTimeToUnix($first('lastlogontimestamp')),
			'account_expires' => $this->winTimeOrNever($first('accountexpires'), true),
		];
	}

	/**
	 * Сбросить пароль пользователя в AD ОТ ИМЕНИ исполнителя (L2+).
	 * Отдельное соединение под личными кредами: и проверка их валидности,
	 * и присутствие исполнителя в логах AD. Требует SSL/TLS (наш конфиг
	 * такой). Бросает исключение при любой неудаче (нет прав, политика
	 * паролей, неверные креды исполнителя, недоступность).
	 *
	 * @param string $targetLogin sAMAccountName чьей учётке меняем пароль
	 * @param string $newPassword новый пароль
	 * @param bool $mustChange потребовать смену при следующем входе
	 * @param string $execLogin логин исполнителя
	 * @param string $execPassword пароль исполнителя
	 * @throws \Throwable
	 */
	public function resetPassword(string $targetLogin, string $newPassword, bool $mustChange,
		string $execLogin, string $execPassword): void
	{
		$name = 'arms-reset-'.md5($execLogin);
		$config = $this->connectionConfig();
		$config['username'] = $this->bindName($execLogin);
		$config['password'] = $execPassword;

		$connection = new Connection($config);
		$connection->connect(); //бросит при неверных кредах/недоступности
		Container::addConnection($connection, $name);

		try {
			/** @var AdUser|null $user */
			$user = AdUser::on($name)->where('samaccountname', '=', $targetLogin)->first();
			if (!is_object($user)) {
				throw new \RuntimeException("Учётка $targetLogin не найдена в AD");
			}

			//LdapRecord кодирует пароль в unicodePwd (HasPassword,
			//passwordAttribute='unicodepwd'); строка = сброс админом.
			//Требует защищённого соединения (assertSecureConnection).
			$user->password = $newPassword;
			//0 в pwdlastset = «сменить пароль при следующем входе»;
			//пишем сырым значением в обход windows-int-каста
			if ($mustChange) $user->setRawAttribute('pwdlastset', '0');
			$user->save();
		} finally {
			Container::getInstance()->removeConnection($name);
		}
	}

	/** Проверка доступности DC (для healthcheck/консольной команды) */
	public function ping(): bool
	{
		$this->serviceConnection()->connect();
		return $this->serviceConnection()->isConnected();
	}

	/**
	 * Ленивое соединение сервисной учётки (регистрируется в Container под
	 * именем CONN_SERVICE, чтобы модели AdUser::on() его находили)
	 */
	protected function serviceConnection(): Connection
	{
		if ($this->serviceConnection === null) {
			$this->serviceConnection = new Connection($this->connectionConfig());
			Container::addConnection($this->serviceConnection, static::CONN_SERVICE);
		}
		return $this->serviceConnection;
	}

	/** Конфиг соединения в терминах LdapRecord из параметров компонента */
	protected function connectionConfig(): array
	{
		$c = $this->connection;
		return [
			'hosts' => (array)($c['hosts'] ?? []),
			'port' => (int)($c['port'] ?? 389),
			'base_dn' => $c['base_dn'] ?? '',
			'username' => $c['username'] ?? '',
			'password' => $c['password'] ?? '',
			'use_ssl' => (bool)($c['use_ssl'] ?? false),
			'use_tls' => (bool)($c['use_tls'] ?? false),
			'timeout' => (int)($c['timeout'] ?? 5),
			'options' => $c['options'] ?? [],
		];
	}

	/** Приводит логин к bindable-виду: добавляет account_suffix, если нет '@' */
	protected function bindName(string $login): string
	{
		$login = trim($login);
		$suffix = $this->connection['account_suffix'] ?? '';
		if ($suffix && strpos($login, '@') === false) return $login.$suffix;
		return $login;
	}

	/**
	 * Windows FILETIME (100-нс интервалы с 1601) → unix ts.
	 * @return int|null null для пустого/0/«никогда»
	 */
	protected function winTimeToUnix($value): ?int
	{
		$value = (int)$value;
		if ($value <= 0 || $value >= 0x7FFFFFFFFFFFFFFF) return null;
		return intdiv($value, 10000000) - 11644473600;
	}

	/**
	 * Как winTimeToUnix, но 0/пусто/максимум трактует как 'never'
	 * (для сроков истечения — там 0 значит «не истекает»)
	 * @param bool $zeroIsNever accountExpires: 0 тоже = «никогда»
	 * @return int|string|null unix ts | 'never' | null
	 */
	protected function winTimeOrNever($value, bool $zeroIsNever = false)
	{
		if (is_null($value)) return null;
		$int = (int)$value;
		if ($int >= 0x7FFFFFFFFFFFFFFF || ($zeroIsNever && $int <= 0)) return 'never';
		$unix = $this->winTimeToUnix($value);
		return is_null($unix) ? 'never' : $unix;
	}
}
