<?php

namespace app\components\ldap;

use LdapRecord\Connection;
use LdapRecord\Container;
use LdapRecord\Models\ActiveDirectory\Computer as AdComputer;
use LdapRecord\Models\ActiveDirectory\User as AdUser;
use LdapRecord\Models\Attributes\DistinguishedName;
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
	 *   dn, path (контейнеры сверху вниз), domain — см. {@see placement()},
	 *   а также enabled(bool), locked(bool),
	 *   password_last_set(unix ts|null),
	 *   password_expires(unix ts|null|'never'), must_change_password(bool),
	 *   last_logon(unix ts|null), account_expires(unix ts|null|'never')
	 * @throws \LdapRecord\LdapRecordException при недоступности сервера
	 */
	public function accountInfo(string $login): ?array
	{
		$this->serviceConnection(); //регистрируем соединение в Container для AdUser::on()

		/** @var AdUser|null $user */
		$user = AdUser::on(static::CONN_SERVICE)
			//вычисляемые атрибуты по умолчанию не отдаются, запрашиваем явно:
			//срок пароля и computed-UAC (в нём достоверные биты блокировки и
			//просроченности пароля)
			->select(['*', 'msds-userpasswordexpirytimecomputed', 'msds-user-account-control-computed'])
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

		//msDS-User-Account-Control-Computed: AD вычисляет его на лету, там
		//ДОСТОВЕРНЫЕ признаки блокировки и просроченного пароля. Сырой
		//lockoutTime для этого не годится: после истечения окна блокировки
		//AD не обнуляет его, и учётка выглядела бы вечно заблокированной.
		$computed = (int)$first('msds-user-account-control-computed');
		$lockedBit = 0x10;         //UF_LOCKOUT
		$pwdExpiredBit = 0x800000; //UF_PASSWORD_EXPIRED

		return $this->placement($user->getDn()) + [
			'enabled' => !($uac & 0x2), //ACCOUNTDISABLE
			'locked' => (bool)($computed & $lockedBit),
			'password_expired' => (bool)($computed & $pwdExpiredBit),
			'password_last_set' => $this->winTimeToUnix($pwdLastSet),
			'password_expires' => $this->winTimeOrNever($first('msds-userpasswordexpirytimecomputed')),
			'must_change_password' => (string)$pwdLastSet === '0',
			'last_logon' => $this->winTimeToUnix($first('lastlogontimestamp')),
			'account_expires' => $this->winTimeOrNever($first('accountexpires'), true),
		];
	}

	/**
	 * Справка об учётке КОМПЬЮТЕРА в AD (для панели AdComputerProvider):
	 * где он лежит в дереве и в каких группах состоит.
	 *
	 * Ищем по sAMAccountName (у компьютеров это «ИМЯ$»), с запасным
	 * поиском по cn - бывает, что имя объекта разошлось с учётной записью.
	 * Имя из инвентаризации может прийти FQDN'ом, берём короткую часть.
	 *
	 * @return array|null null = компьютер не найден; иначе:
	 *   dn, path (контейнеры сверху вниз), domain, groups[{name,dn}],
	 *   enabled(bool), os, dns_name, last_logon(unix ts|null), description
	 * @throws \LdapRecord\LdapRecordException при недоступности сервера
	 */
	public function computerInfo(string $name): ?array
	{
		$this->serviceConnection(); //регистрируем соединение для AdComputer::on()

		$short = explode('.', trim($name))[0];
		if ($short === '') return null;

		//memberOf запрашиваем явно: это обратная ссылка, и не всякий
		//сервер отдаёт её в ответ на «*»
		$query = static fn() => AdComputer::on(static::CONN_SERVICE)->select(['*', 'memberof']);

		/** @var AdComputer|null $computer */
		$computer = $query()->where('samaccountname', '=', $short.'$')->first();
		if (!is_object($computer)) $computer = $query()->where('cn', '=', $short)->first();
		if (!is_object($computer)) return null;

		$raw = $computer->getAttributes();
		$first = static fn(string $key) => $raw[$key][0] ?? null;

		return $this->placement($computer->getDn()) + [
			'groups' => $this->groupsOf($raw),
			'enabled' => !((int)$first('useraccountcontrol') & 0x2), //ACCOUNTDISABLE
			'os' => trim(($first('operatingsystem') ?? '').' '.($first('operatingsystemversion') ?? '')),
			'dns_name' => $first('dnshostname'),
			'last_logon' => $this->winTimeToUnix($first('lastlogontimestamp')),
			'description' => $first('description'),
		];
	}

	/**
	 * Размещение объекта в каталоге: DN и разобранный путь по дереву.
	 * Общее для учёток пользователей и компьютеров — и панели показывают
	 * его одинаково (views/ad-common/dn-path.php).
	 *
	 * @return array ['dn'=>string, 'path'=>string[] контейнеры сверху
	 *   вниз, 'domain'=>string]
	 */
	protected function placement(string $dn): array
	{
		[$path, $domain] = $this->dnPath($dn);
		return ['dn' => $dn, 'path' => $path, 'domain' => $domain];
	}

	/**
	 * Группы объекта из memberOf, отсортированные по имени.
	 * Только ПРЯМЫЕ членства: memberOf не содержит ни вложенных групп,
	 * ни первичной («Пользователи/Компьютеры домена»).
	 *
	 * @param array $raw сырые атрибуты объекта
	 * @return array [['name'=>string, 'dn'=>string], ...]
	 */
	protected function groupsOf(array $raw): array
	{
		$groups = [];
		foreach ($raw['memberof'] ?? [] as $groupDn) {
			$name = $this->unescapeDnValue((string)(new DistinguishedName($groupDn))->name());
			$groups[] = ['name' => $name !== '' ? $name : $groupDn, 'dn' => $groupDn];
		}
		usort($groups, static fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
		return $groups;
	}

	/**
	 * Разбор DN на путь в дереве: контейнеры сверху вниз и домен.
	 * Первый RDN - сам объект, его пропускаем; DC-части складываются
	 * в имя домена.
	 * @return array [string[] контейнеры, string домен]
	 */
	protected function dnPath(string $dn): array
	{
		$containers = [];
		$domain = [];
		$components = (new DistinguishedName($dn))->multi();
		array_shift($components); //сам объект

		foreach ($components as [$attribute, $value]) {
			$value = $this->unescapeDnValue($value);
			if (strtolower($attribute) === 'dc') $domain[] = $value;
			else $containers[] = $value;
		}
		return [array_reverse($containers), implode('.', $domain)];
	}

	/**
	 * Значение RDN в человеческий вид: ldap_explode_dn (на нём построен
	 * разбор DN в ldaprecord) отдаёт всё не-ASCII побайтово в hex -
	 * «OU=Челябинск» приходит как «OU=\D0\A7\D0\B5...». Собираем байты
	 * обратно в UTF-8; одиночные экранирования спецсимволов (\, \+ и
	 * т.п.) просто теряют слэш.
	 */
	protected function unescapeDnValue(string $value): string
	{
		$value = preg_replace_callback(
			'/\\\\([0-9A-Fa-f]{2})/',
			static fn(array $m) => chr(hexdec($m[1])),
			$value
		);
		return preg_replace('/\\\\(.)/', '$1', $value);
	}

	/**
	 * НЕДЕСТРУКТИВНАЯ предпроверка перед сбросом пароля: валидны ли креды
	 * исполнителя и достаточно ли у него прав сбросить пароль ИМЕННО этому
	 * пользователю. Не пишет в AD (только bind + чтение), поэтому не
	 * создаёт шум в аудите. Вызывать ДО отправки SMS: снимает сценарии
	 * «SMS ушло, а пароль не сменился» (опечатка в пароле исполнителя;
	 * верный пароль, но учётка без прав записи в AD).
	 *
	 * Право на сброс определяется через конструируемый атрибут
	 * allowedAttributesEffective: AD вычисляет его для читающего
	 * (забиндившегося) пользователя — если в списке есть unicodePwd, у
	 * исполнителя есть право сменить пароль цели. Если AD не вернул список
	 * (не поддерживается/недоступен) — ограничиваемся проверкой кредов,
	 * не блокируя (сам сброс всё равно проверит права по факту).
	 *
	 * @throws \Throwable неверные креды / нет прав / цель не найдена / DC недоступен
	 */
	public function verifyResetPermission(string $targetLogin, string $execLogin, string $execPassword): void
	{
		$this->withExecutor($execLogin, $execPassword, function (string $name) use ($targetLogin) {
			/** @var AdUser|null $user */
			$user = AdUser::on($name)
				->where('samaccountname', '=', $targetLogin)
				->select(['allowedattributeseffective'])
				->first();
			if (!is_object($user)) {
				throw new \RuntimeException("Учётка $targetLogin не найдена в AD");
			}

			$allowed = array_map('strtolower', $user->getAttributes()['allowedattributeseffective'] ?? []);
			//список посчитан, но unicodePwd в нём нет => прав на сброс нет
			if (!empty($allowed) && !in_array('unicodepwd', $allowed, true)) {
				throw new \RuntimeException(
					"у учётной записи $execLogin нет права сбрасывать пароль этого пользователя"
				);
			}
		});
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
	 * @param bool $unlock заодно разблокировать учётку (lockoutTime=0)
	 * @param string $execLogin логин исполнителя
	 * @param string $execPassword пароль исполнителя
	 * @throws \Throwable
	 */
	public function resetPassword(string $targetLogin, string $newPassword, bool $unlock,
		string $execLogin, string $execPassword): array
	{
		return $this->withExecutor($execLogin, $execPassword, function (string $name) use ($targetLogin, $newPassword, $unlock) {
			/** @var AdUser|null $user */
			$user = AdUser::on($name)->where('samaccountname', '=', $targetLogin)->first();
			if (!is_object($user)) {
				throw new \RuntimeException("Учётка $targetLogin не найдена в AD");
			}

			$dn = $user->getDn();
			//отметка смены пароля ДО операции — по ней подтвердим факт смены
			$before = $user->getAttributes()['pwdlastset'][0] ?? null;

			//LdapRecord кодирует пароль в unicodePwd (HasPassword,
			//passwordAttribute='unicodepwd'); строка = сброс админом.
			//Требует защищённого соединения (assertSecureConnection).
			$user->password = $newPassword;
			//разблокировка = lockoutTime в 0 (AD допускает только сброс в 0);
			//пишем сырым значением в обход windows-int-каста
			if ($unlock) $user->setRawAttribute('lockouttime', '0');
			$user->save();

			//ПОДТВЕРЖДЕНИЕ: перечитываем учётку и убеждаемся, что отметка
			//смены пароля обновилась. Без этого «успех» ничем не обеспечен —
			//AD мог принять запрос, но не изменить пароль.
			/** @var AdUser|null $fresh */
			$fresh = AdUser::on($name)->where('samaccountname', '=', $targetLogin)->first();
			$after = is_object($fresh) ? ($fresh->getAttributes()['pwdlastset'][0] ?? null) : null;

			if ((string)$after === (string)$before) {
				throw new \RuntimeException(
					'AD принял запрос, но отметка смены пароля (pwdLastSet) не изменилась — '
					.'пароль НЕ сброшен (проверьте права исполнителя и политику паролей)'
				);
			}

			return [
				'dn' => $dn,
				'pwd_last_set_before' => $this->winTimeToUnix($before),
				'pwd_last_set_after' => $this->winTimeToUnix($after),
				'unlocked' => $unlock,
			];
		});
	}

	/**
	 * Выполнить операцию под отдельным соединением, забинженным личными
	 * кредами исполнителя (проверка кредов + исполнитель в логах AD).
	 * Соединение регистрируется в Container под временным именем и
	 * гарантированно снимается после.
	 * @param callable $fn function(string $connectionName): mixed
	 * @throws \Throwable connect() бросает при неверных кредах/недоступности
	 */
	protected function withExecutor(string $execLogin, string $execPassword, callable $fn)
	{
		$name = 'arms-exec-'.md5($execLogin);
		$config = $this->connectionConfig();
		$config['username'] = $this->bindName($execLogin);
		$config['password'] = $execPassword;

		$connection = new Connection($config);
		$connection->connect(); //бросит при неверных кредах/недоступности
		Container::addConnection($connection, $name);

		try {
			return $fn($name);
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
