<?php

namespace app\components\ldap;

use LdapRecord\Connection;
use LdapRecord\Container;
use LdapRecord\Models\ActiveDirectory\Computer as AdComputer;
use LdapRecord\Models\ActiveDirectory\Group as AdGroup;
use LdapRecord\Models\ActiveDirectory\OrganizationalUnit as AdOu;
use LdapRecord\Models\ActiveDirectory\User as AdUser;
use LdapRecord\Models\Attributes\DistinguishedName;
use LdapRecord\Models\Entry;
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
	 * DN на компоненты RDN. Запятые внутри значений экранированы слэшем и
	 * разделителями не считаются; пробелы вокруг разделителей игнорируются.
	 * @return string[]
	 */
	protected static function dnComponents(string $dn): array
	{
		$dn = trim($dn);
		if ($dn === '') return [];
		return array_map('trim', preg_split('/(?<!\\\\),/', $dn) ?: []);
	}

	/**
	 * Родительский контейнер объекта (DN без первого RDN)
	 */
	public static function parentDn(string $dn): string
	{
		$components = static::dnComponents($dn);
		array_shift($components);
		return implode(',', $components);
	}

	/**
	 * Равенство DN без учёта регистра (mb-aware: strcasecmp не сворачивает
	 * кириллицу в именах OU) и пробелов у разделителей
	 */
	public static function dnEquals(string $a, string $b): bool
	{
		return mb_strtolower(implode(',', static::dnComponents($a)))
			=== mb_strtolower(implode(',', static::dnComponents($b)));
	}

	/**
	 * Лежит ли DN внутри поддерева $base (сравнение покомпонентное,
	 * без учёта регистра). Нужна интеграциям AD: проверить, что выбранное
	 * подразделение/группа не вышли за настроенный корень, и что учётка
	 * действительно в контейнере уволенных.
	 * @param bool $orEqual true - сам $base тоже считается подходящим
	 */
	public static function dnIsUnder(string $dn, string $base, bool $orEqual = false): bool
	{
		$dnParts = static::dnComponents($dn);
		$baseParts = static::dnComponents($base);
		if (!count($baseParts) || count($dnParts) < count($baseParts)) return false;
		if (!$orEqual && count($dnParts) === count($baseParts)) return false;
		$tail = array_slice($dnParts, -count($baseParts));
		return static::dnEquals(implode(',', $tail), implode(',', $baseParts));
	}

	/**
	 * Перенос DN из одного корня в другой с сохранением относительного
	 * пути: увольнение (usr_dismiss.ps1) зеркалит путь учётки из рабочего
	 * корня в корень уволенных - восстановление вычисляет обратное зеркало.
	 * @return string|null null = $dn не лежит под $fromBase
	 */
	public static function relocateDn(string $dn, string $fromBase, string $toBase): ?string
	{
		if (!static::dnIsUnder($dn, $fromBase, true)) return null;
		$dnParts = static::dnComponents($dn);
		$prefix = array_slice($dnParts, 0, count($dnParts) - count(static::dnComponents($fromBase)));
		return implode(',', array_merge($prefix, static::dnComponents($toBase)));
	}

	/**
	 * Подразделения (OU) поддерева $rootDn, отсортированные как дерево
	 * (родитель прежде детей) - для селекта в форме создания учётки.
	 * Сам $rootDn попадает в список первым (если он OU).
	 * @return array [['dn'=>string, 'name'=>string, 'depth'=>int], ...]
	 *   depth 0 = сам корень
	 * @throws \LdapRecord\LdapRecordException при недоступности сервера
	 */
	public function ouList(string $rootDn): array
	{
		$this->serviceConnection();

		$rootDepth = count(static::dnComponents($rootDn));
		$items = [];
		/** @var AdOu $ou */
		foreach (AdOu::on(static::CONN_SERVICE)->in($rootDn)->paginate(1000) as $ou) {
			$dn = $ou->getDn();
			$components = static::dnComponents($dn);
			//имена контейнеров от корня вниз (для сортировки деревом)
			$relative = [];
			foreach (array_reverse(array_slice($components, 0, max(0, count($components) - $rootDepth))) as $component) {
				$relative[] = $this->unescapeDnValue(preg_replace('/^[^=]+=/', '', $component));
			}
			$name = count($relative)
				? end($relative)
				: $this->unescapeDnValue((string)(new DistinguishedName($dn))->name());
			$items[] = [
				'dn' => $dn,
				'name' => $name,
				'depth' => count($relative),
				//\x01 < любого печатного символа: родитель всегда прежде детей
				'sort' => implode("\x01", array_map('mb_strtolower', $relative)),
			];
		}
		usort($items, static fn($a, $b) => strnatcasecmp($a['sort'], $b['sort']));
		return array_map(static fn($item) => array_diff_key($item, ['sort' => 1]), $items);
	}

	/**
	 * Группы безопасности поддерева $rootDn (null = весь каталог),
	 * отсортированные по имени - для мультиселекта в форме создания учётки.
	 * @return array [['name'=>string, 'dn'=>string], ...]
	 * @throws \LdapRecord\LdapRecordException при недоступности сервера
	 */
	public function groupList(?string $rootDn = null): array
	{
		$this->serviceConnection();

		$query = AdGroup::on(static::CONN_SERVICE)->select(['cn']);
		if (!empty($rootDn)) $query->in($rootDn);

		$groups = [];
		foreach ($query->paginate(1000) as $group) {
			$dn = $group->getDn();
			$name = $this->unescapeDnValue((string)(new DistinguishedName($dn))->name());
			$groups[] = ['name' => $name !== '' ? $name : $dn, 'dn' => $dn];
		}
		usort($groups, static fn($a, $b) => strnatcasecmp($a['name'], $b['name']));
		return $groups;
	}

	/**
	 * Свободен ли логин (sAMAccountName) в каталоге
	 * @throws \LdapRecord\LdapRecordException при недоступности сервера
	 */
	public function loginIsFree(string $login): bool
	{
		$this->serviceConnection();
		return !is_object(
			AdUser::on(static::CONN_SERVICE)->where('samaccountname', '=', $login)->first()
		);
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
		$this->verifyWriteAttributes($targetLogin,
			['unicodepwd' => 'сбрасывать пароль этого пользователя'],
			$execLogin, $execPassword);
	}

	/**
	 * НЕДЕСТРУКТИВНАЯ предпроверка: валидны ли креды исполнителя и есть ли
	 * у него право записи перечисленных атрибутов ИМЕННО этой учётки
	 * (bind + чтение allowedAttributesEffective, без записи в AD).
	 * Обобщение {@see verifyResetPermission()}: восстановлению учётки нужны
	 * и unicodePwd (пароль), и userAccountControl (включение).
	 *
	 * Если AD не вернул список (не поддерживается/недоступен) —
	 * ограничиваемся проверкой кредов, не блокируя: сама запись всё равно
	 * проверит права по факту.
	 *
	 * @param array $attributes [ldap-атрибут => человекочитаемое право]
	 * @throws \Throwable неверные креды / нет прав / цель не найдена / DC недоступен
	 */
	public function verifyWriteAttributes(string $targetLogin, array $attributes,
		string $execLogin, string $execPassword): void
	{
		$this->withExecutor($execLogin, $execPassword, function (string $name) use ($targetLogin, $attributes, $execLogin) {
			/** @var AdUser|null $user */
			$user = AdUser::on($name)
				->where('samaccountname', '=', $targetLogin)
				->select(['allowedattributeseffective'])
				->first();
			if (!is_object($user)) {
				throw new \RuntimeException("Учётка $targetLogin не найдена в AD");
			}

			$allowed = array_map('strtolower', $user->getAttributes()['allowedattributeseffective'] ?? []);
			if (empty($allowed)) return; //AD не отдал список - права проверит сама запись

			$missing = [];
			foreach ($attributes as $attribute => $description) {
				if (!in_array(strtolower($attribute), $allowed, true)) $missing[] = $description;
			}
			if ($missing) {
				throw new \RuntimeException(
					"у учётной записи $execLogin нет права ".implode('; ', $missing)
				);
			}
		});
	}

	/**
	 * НЕДЕСТРУКТИВНАЯ предпроверка перед созданием учётки: валидны ли креды
	 * исполнителя, есть ли у него право создавать пользователей в выбранном
	 * OU (конструируемый атрибут allowedChildClassesEffective, AD вычисляет
	 * его для забиндившегося) и пополнять выбранные группы (member в
	 * allowedAttributesEffective групп). Без записи в AD.
	 *
	 * @param string $ouDn куда создаём
	 * @param string[] $groupDns группы, в которые планируется включить
	 * @throws \Throwable неверные креды / нет прав / OU или группа не найдены
	 */
	public function verifyCreatePermission(string $ouDn, array $groupDns,
		string $execLogin, string $execPassword): void
	{
		$this->withExecutor($execLogin, $execPassword, function (string $name) use ($ouDn, $groupDns, $execLogin) {
			$ou = Entry::on($name)->select(['allowedchildclasseseffective'])->find($ouDn);
			if (!is_object($ou)) {
				throw new \RuntimeException("Подразделение $ouDn не найдено (или нет доступа)");
			}
			$classes = array_map('strtolower', $ou->getAttributes()['allowedchildclasseseffective'] ?? []);
			//список посчитан, но класса user в нём нет => права создавать нет
			if (!empty($classes) && !in_array('user', $classes, true)) {
				throw new \RuntimeException(
					"у учётной записи $execLogin нет права создавать пользователей в $ouDn"
				);
			}

			foreach ($groupDns as $groupDn) {
				$group = Entry::on($name)->select(['allowedattributeseffective'])->find($groupDn);
				if (!is_object($group)) {
					throw new \RuntimeException("Группа $groupDn не найдена (или нет доступа)");
				}
				$allowed = array_map('strtolower', $group->getAttributes()['allowedattributeseffective'] ?? []);
				if (!empty($allowed) && !in_array('member', $allowed, true)) {
					throw new \RuntimeException(
						"у учётной записи $execLogin нет права добавлять участников в группу $groupDn"
					);
				}
			}
		});
	}

	/**
	 * Создать учётку пользователя в AD ОТ ИМЕНИ исполнителя (L2+).
	 * Порядок операций продиктован AD: объект создаётся сразу с паролем
	 * (unicodePwd на создании требует LDAPS - наш конфиг такой), затем
	 * включается отдельной записью (включить учётку без пароля, прошедшего
	 * политику, AD не даст), затем добавляется в группы (запись идёт в
	 * атрибут member САМИХ групп). Неудача включения/групп не откатывает
	 * создание - возвращается честный пооперационный отчёт.
	 *
	 * @param array $attrs атрибуты: samaccountname (обязателен), cn,
	 *   displayname, givenname, sn, title, department, company, mail,
	 *   mobile, pager, employeeid, employeenumber, admindescription,
	 *   upnSuffix (null = account_suffix); состав повторяет схему полей
	 *   скрипта синхронизации inventory-to-ad.ps1 (см.
	 *   AdUserProvider::accountAttributes())
	 * @param string $ouDn куда создаём
	 * @param string[] $groupDns группы (DN), в которые включить
	 * @return array ['dn', 'enabled'(bool), 'enable_error'(?string),
	 *   'groups'(имена добавленных), 'group_errors'(string[])]
	 * @throws \Throwable само создание не удалось (логин занят, нет прав,
	 *   политика паролей, недоступность)
	 */
	public function createAccount(array $attrs, string $ouDn, array $groupDns, string $password,
		string $execLogin, string $execPassword): array
	{
		return $this->withExecutor($execLogin, $execPassword, function (string $name) use ($attrs, $ouDn, $groupDns, $password) {
			$login = (string)$attrs['samaccountname'];
			if (is_object(AdUser::on($name)->where('samaccountname', '=', $login)->first())) {
				throw new \RuntimeException("Учётка $login уже существует в AD");
			}

			$suffix = $attrs['upnSuffix'] ?? ($this->connection['account_suffix'] ?? '');
			if ($suffix !== '' && $suffix[0] !== '@') $suffix = '@'.$suffix;

			/** @var AdUser $user */
			$user = (new AdUser)->setConnection($name)->inside($ouDn);
			$user->cn = $attrs['cn'] ?? $login;
			$user->samaccountname = $login;
			$user->userprincipalname = $suffix !== '' ? $login.$suffix : $login;
			foreach (['displayname', 'givenname', 'sn', 'title', 'department', 'company',
				'mail', 'mobile', 'pager', 'employeeid', 'employeenumber', 'admindescription'] as $attribute) {
				if (!empty($attrs[$attribute])) $user->$attribute = $attrs[$attribute];
			}
			$user->password = $password; //unicodePwd, требует защищённого соединения
			$user->save();

			//перечитать из каталога ДО включения: AD сам выставил созданной
			//учётке userAccountControl (546 = disabled+passwd_notreqd), а
			//модель в памяти об этом не знает - без refresh() запись ниже
			//уйдёт как ADD вместо REPLACE и упадёт с «Type or value exists»
			$user->refresh();

			//включение отдельной операцией: пароль уже установлен
			$enableError = null;
			try {
				$user->useraccountcontrol = 512; //NORMAL_ACCOUNT
				$user->save();
			} catch (\Throwable $e) {
				$enableError = $e->getMessage();
			}

			$groups = [];
			$groupErrors = [];
			foreach ($groupDns as $groupDn) {
				$groupName = $this->unescapeDnValue((string)(new DistinguishedName($groupDn))->name());
				if ($groupName === '') $groupName = $groupDn;
				try {
					/** @var AdGroup|null $group */
					$group = AdGroup::on($name)->find($groupDn);
					if (!is_object($group)) throw new \RuntimeException('группа не найдена');
					$group->members()->attach($user);
					$groups[] = $groupName;
				} catch (\Throwable $e) {
					$groupErrors[] = $groupName.': '.$e->getMessage();
				}
			}

			//ПОДТВЕРЖДЕНИЕ: перечитываем созданную учётку
			/** @var AdUser|null $fresh */
			$fresh = AdUser::on($name)->where('samaccountname', '=', $login)->first();
			if (!is_object($fresh)) {
				throw new \RuntimeException('AD принял запрос, но созданная учётка не найдена при перечитывании');
			}
			$uac = (int)($fresh->getAttributes()['useraccountcontrol'][0] ?? 0);

			return [
				'dn' => $fresh->getDn(),
				'enabled' => !($uac & 0x2),
				'enable_error' => $enableError,
				'groups' => $groups,
				'group_errors' => $groupErrors,
			];
		});
	}

	/**
	 * Восстановить уволенную учётку ОТ ИМЕНИ исполнителя (L2+): новый
	 * пароль + включение (+опц. разблокировка) одной записью, затем
	 * перемещение в рабочее подразделение. Пароль ставится ПЕРВЫМ и
	 * подтверждается по pwdLastSet (как в {@see resetPassword()});
	 * неудача перемещения не откатывает включение - возвращается в
	 * move_error для честного отчёта (учётка уже рабочая, перенести можно
	 * руками).
	 *
	 * @param string $targetLogin sAMAccountName восстанавливаемой учётки
	 * @param string $newParentDn целевое подразделение (должно существовать)
	 * @return array ['dn_before', 'dn_after', 'pwd_last_set_before',
	 *   'pwd_last_set_after', 'enabled'(bool), 'unlocked'(bool),
	 *   'move_error'(?string)]
	 * @throws \Throwable пароль/включение не удались (нет прав, политика
	 *   паролей, цель не найдена, целевое OU не существует, недоступность)
	 */
	public function restoreAccount(string $targetLogin, string $newParentDn, string $password,
		bool $unlock, string $execLogin, string $execPassword): array
	{
		return $this->withExecutor($execLogin, $execPassword, function (string $name) use ($targetLogin, $newParentDn, $password, $unlock) {
			/** @var AdUser|null $user */
			$user = AdUser::on($name)->where('samaccountname', '=', $targetLogin)->first();
			if (!is_object($user)) {
				throw new \RuntimeException("Учётка $targetLogin не найдена в AD");
			}

			$dnBefore = $user->getDn();
			$before = $user->getAttributes()['pwdlastset'][0] ?? null;
			$uac = (int)($user->getAttributes()['useraccountcontrol'][0] ?? 0);

			//целевое подразделение должно существовать ДО каких-либо записей
			if (!is_object(Entry::on($name)->find($newParentDn))) {
				throw new \RuntimeException("Целевое подразделение $newParentDn не найдено в AD");
			}

			//пароль + включение (+разблокировка) одной операцией
			$user->password = $password;
			$user->useraccountcontrol = $uac & ~0x2; //снимаем ACCOUNTDISABLE
			if ($unlock) $user->setRawAttribute('lockouttime', '0');
			$user->save();

			//перемещение в рабочее дерево - после включения: если оно
			//сорвётся, учётка уже рабочая (перенести можно и руками)
			$moveError = null;
			try {
				if (!static::dnEquals(static::parentDn($dnBefore), $newParentDn)) {
					$user->move($newParentDn);
				}
			} catch (\Throwable $e) {
				$moveError = $e->getMessage();
			}

			//ПОДТВЕРЖДЕНИЕ: перечитываем и проверяем смену пароля и включение
			/** @var AdUser|null $fresh */
			$fresh = AdUser::on($name)->where('samaccountname', '=', $targetLogin)->first();
			if (!is_object($fresh)) {
				throw new \RuntimeException('AD принял запрос, но учётка не найдена при перечитывании');
			}
			$freshRaw = $fresh->getAttributes();
			$after = $freshRaw['pwdlastset'][0] ?? null;
			if ((string)$after === (string)$before) {
				throw new \RuntimeException(
					'AD принял запрос, но отметка смены пароля (pwdLastSet) не изменилась — '
					.'пароль НЕ установлен (проверьте права исполнителя и политику паролей)'
				);
			}
			$freshUac = (int)($freshRaw['useraccountcontrol'][0] ?? 0);

			return [
				'dn_before' => $dnBefore,
				'dn_after' => $fresh->getDn(),
				'pwd_last_set_before' => $this->winTimeToUnix($before),
				'pwd_last_set_after' => $this->winTimeToUnix($after),
				'enabled' => !($freshUac & 0x2),
				'unlocked' => $unlock,
				'move_error' => $moveError,
			];
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
