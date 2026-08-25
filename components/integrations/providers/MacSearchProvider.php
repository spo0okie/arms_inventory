<?php

namespace app\components\integrations\providers;

use app\components\integrations\IntegrationProvider;
use app\helpers\MacsHelper;
use app\helpers\StringHelper;
use app\models\base\ArmsModel;
use app\models\Comps;
use app\models\Places;
use app\models\Ports;
use app\models\Techs;
use Yii;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Панель «Порт коммутатора» в карточке ОС/оборудования: где MAC-адрес
 * объекта виден в сети.
 *
 * Внешний сервис [arms.macsearch](https://github.com/spo0okie/arms_macsearch)
 * стоит на машине с доступом к сетевому оборудованию (у сервера
 * инвентаризации такого доступа нет) и умеет ровно одно: опросить
 * ПЕРЕДАННЫЕ ему коммутаторы и сказать, на каком порту виден адрес.
 * Состав опроса собирает инвентаризация — она и знает, что считать
 * коммутатором, где он стоит и какого он вендора:
 *
 * - цели = оборудование с типом модели из `switchTypes`, непустым IP и
 *   неархивным состоянием, отфильтрованное по площадке объекта (`scope`);
 * - в каждой цели уезжает её id, и сервис возвращает его в найденной
 *   строке — результат сразу привязан к карточке коммутатора;
 * - аплинки отсеиваются здесь же, по связям портов ({@see Ports}): порт,
 *   связанный с портом другого коммутатора, — транзитный.
 *
 * Обход занимает секунды, но коммутатор может тормозить: сервис отвечает либо
 * результатом, либо `status=pending`, и тогда панель перезапрашивает себя
 * сама ({@see refreshUrl()}), пока не дождётся или не исчерпает попытки.
 *
 * Конфиг (params-local.php), ключ обязан быть 'macsearch' (от него зависит
 * путь view-файлов):
 * ```php
 * 'integrations' => [
 *     'macsearch' => [
 *         'class' => \app\components\integrations\providers\MacSearchProvider::class,
 *         'url' => 'http://macsearch.local:8088',  //база сервиса
 *         'token' => '<токен из config.priv.json сервиса>',
 *         //'title' => 'Порт коммутатора',
 *         //'scope' => 'place',    //область опроса ПО УМОЛЧАНИЮ (в меню у адреса
 *         //                       //можно выбрать другую): площадка объекта
 *         //                       //(place) или все сразу (all)
 *         //'switchTypes' => ['net_switch'], //коды типов оборудования (TechTypes.code),
 *         //                       //которые опрашиваем; напр. + 'net_router'
 *         //'maxTargets' => 200,   //предел целей в одном запросе (есть и у сервиса)
 *         //'transitFrom' => 4,    //с какого числа адресов порт считается транзитным
 *         //'bridgeToSwitchPorts' => ['internet','lan','switch','sw'], //как на устройствах-мостах
 *         //                       //(телефон с ПК за ним) зовётся порт к коммутатору
 *         //'bridgeToDevicePorts' => ['pc','comp'], //...и порт к устройству за мостом
 *         //'maxMacs' => 3,        //сколько адресов объекта искать
 *         //'includeLinked' => true, //брать и адреса привязанной ОС/АРМ
 *         //'wait' => 25,          //сколько сервис держит запрос до ответа pending
 *         //'timeout' => 30,       //таймаут HTTP (обязан быть больше wait)
 *         //'autoPanel' => true,   //рисовать панель в карточке автоматически;
 *         //                       //по умолчанию нет - опрос только по клику
 *         //                       //иконки поиска рядом с адресом
 *         //'cacheTtl' => 600,     //ttl панели, по умолчанию как кэш сервиса
 *         //'maxAttempts' => 3,    //сколько раз панель сама перезапросит pending
 *         //'verifySsl' => false,
 *     ],
 * ],
 * ```
 */
class MacSearchProvider extends IntegrationProvider
{
	/** id панели «Порт коммутатора» (где виден адрес объекта) */
	const PANEL = 'ports';

	/** id панели «Что за портами» в карточке самого коммутатора */
	const PANEL_SWITCH = 'switch';

	/** область опроса: площадка объекта / все коммутаторы */
	const SCOPE_PLACE = 'place';
	const SCOPE_ALL = 'all';

	/** коды типов оборудования, которые считаем коммутаторами (TechTypes.code) */
	const DEFAULT_SWITCH_TYPES = ['net_switch'];

	/** сколько адресов объекта опрашиваем по умолчанию */
	const DEFAULT_MAX_MACS = 3;

	/** предел целей в одном запросе */
	const DEFAULT_MAX_TARGETS = 200;

	/** сколько раз панель сама перезапросит себя, пока сервис ищет */
	const DEFAULT_MAX_ATTEMPTS = 3;

	/** сколько сервис держит HTTP-запрос, прежде чем ответить pending, сек */
	const DEFAULT_WAIT = 25;

	/** таймаут HTTP по умолчанию: заведомо больше DEFAULT_WAIT */
	const DEFAULT_TIMEOUT = 30;

	/** ttl панели по умолчанию, сек (у сервиса свой кэш той же длины) */
	const DEFAULT_TTL = 600;

	/**
	 * С какого числа адресов на порту считаем его транзитным (за портом сеть,
	 * а не устройство). Два-три адреса — штатное дело: телефон с ПК за ним,
	 * виртуалки, свитч под столом. Знание из `ports` эту оценку перебивает.
	 */
	const DEFAULT_TRANSIT_FROM = 4;

	/**
	 * Как на устройствах-мостах (телефон с ПК за ним) обычно подписаны порты:
	 * к коммутатору и к устройству за мостом. Только подсказка для раскладки
	 * цепочки — выбор за человеком, в строке предложения порт переключается.
	 * Имена с корпуса SPA525G: Internet/LAN/SW к коммутатору, PC дальше.
	 */
	const DEFAULT_BRIDGE_TO_SWITCH_PORTS = ['internet', 'lan', 'switch', 'sw'];
	const DEFAULT_BRIDGE_TO_DEVICE_PORTS = ['pc', 'comp'];

	/**
	 * Соседи по LLDP/CDP, которых карта сети не считает кандидатами в
	 * коммутаторы: IP-телефоны (SEP/SIP + MAC у Cisco, «IP Phone» в имени).
	 */
	const DEFAULT_NEIGHBOR_IGNORE =
		'~(?i)\bip.?phone\b|^s[ei]p[-_]?[0-9a-f]{8,}|^sip[-_]|[0-9a-f]{12}$~';

	/**
	 * Имена агрегированных каналов: Po1, BAGG1, Port-channel1, ae0, Lag2.
	 * Это не физический порт, и сопоставлять его с розеткой нельзя — иначе
	 * `Po1` выдаст себя за «порт 1» (числовой хвост у них одинаковый).
	 */
	const AGGREGATE_RE = '~^(po|port-?channel|bagg|bridge-aggregation|ae|lag)\\d+$~i';

	/** @var Techs[] опрошенные коммутаторы (id => модель) — для рендера ссылок */
	protected array $switches = [];

	/** @var array паспорт портов последнего опроса: имя порта => данные */
	protected array $passport = [];

	/** @var array соседи последнего опроса: имя порта => записи */
	protected array $neighbors = [];

	/** @var Techs|null коммутатор, чьи порты раскладываем ({@see switchPorts()}) */
	protected ?Techs $scanned = null;

	/**
	 * @var array стеки по id цели: id представителя => Techs[] члены (по id).
	 * Заполняется при сборе целей ({@see targets()}, {@see stackOf()}) и
	 * нужна раскладке результата ({@see attributeStack()})
	 */
	protected array $stacks = [];

	public function getTitle(): string
	{
		return $this->config['title'] ?? 'Порт коммутатора';
	}

	public function isConfigured(): bool
	{
		return !empty($this->config['url']) && !empty($this->config['token']);
	}

	public function appliesTo(ArmsModel $model): bool
	{
		return $model instanceof Comps || $model instanceof Techs;
	}

	/**
	 * Ключ привязки — сами адреса, по которым идёт опрос: во внешнем сервисе
	 * объектов инвентаризации нет, он знает только MAC. Он же ключ кэша, и
	 * поэтому учитывает запрошенный адрес: иконка поиска зовёт панель для
	 * ОДНОГО адреса, и результат не должен смешиваться с опросом всех.
	 */
	public function binding(ArmsModel $model): ?string
	{
		$macs = $this->requestedMacs($model);
		//область опроса тоже в ключе: «площадка объекта» и «все площадки» -
		//разные результаты, смешивать их в кэше нельзя
		$key = $macs ? implode(',', $macs).'@'.$this->requestedScope() : null;

		//у коммутатора есть вторая панель («что за портами»), и ключуется она
		//самим коммутатором: собственный MAC у коммутатора записан далеко не всегда
		if ($this->isSwitch($model)) $key = 'tech'.$model->id.($key ? '|'.$key : '');

		return $key;
	}

	/**
	 * Панели провайдера.
	 *
	 * «Порт коммутатора» есть всегда, но сама в карточке не рисуется
	 * ('auto' => false): опрос коммутаторов — дорогая операция, запускать её
	 * при каждом открытии карточки незачем. Её открывают по клику иконки
	 * рядом с адресом ({@see \app\components\MacSearchWidget}) через proxy.
	 *
	 * «Что за портами» появляется только в карточке самого коммутатора и
	 * рисуется кнопкой ('auto' => 'button'): точка входа в карточке нужна, а
	 * снимать таблицу MAC при каждом открытии — нет.
	 */
	public function panels(ArmsModel $model): array
	{
		$panels = [
			static::PANEL => [
				'title' => $this->getTitle(),
				'ttl' => $this->config['cacheTtl'] ?? static::DEFAULT_TTL,
				'auto' => (bool)($this->config['autoPanel'] ?? false),
			],
		];

		if ($this->isSwitch($model)) {
			//'auto' => false: панель не рисуется общим блоком интеграций - её
			//зовёт поимённо блок «Сетевые порты» карточки и подменяет ею свою
			//таблицу. Таблица портов одна, и владелец у неё карточка
			$panels[static::PANEL_SWITCH] = [
				'title' => 'Что подключено к портам',
				'ttl' => $this->config['cacheTtl'] ?? static::DEFAULT_TTL,
				'auto' => false,
				'button' => 'Опросить порты',
			];
		}

		return $panels;
	}

	/**
	 * Этот объект — коммутатор, который мы умеем опрашивать?
	 * (тип модели из switchTypes, есть IP, не архивный)
	 */
	public function isSwitch(ArmsModel $model): bool
	{
		if (!static::isSwitchable($model)) return false;
		/** @var Techs $model */
		if (!static::firstIp($model->ip)) return false;
		//неработающий (склад, ремонт, списание) не опрашивается - его статус
		//главнее того, что железо, возможно, ещё отвечает
		if (is_object($model->state) && !$model->state->operating) return false;

		return $this->isSwitchType($model);
	}

	/**
	 * Отметка опроса для панели: «сформировано за N c, когда».
	 *
	 * Берётся из самих данных (started_at/duration сервиса), а не из времени
	 * рендера: увидел через 40 секунд тот же штамп - значит, это тот же ответ,
	 * и неважно, какой из слоёв его вернул (кэш панели, кэш сервиса,
	 * присоединение к идущему опросу). Кэш сервиса помечается явно.
	 */
	public function scanStamp(?array $data): string
	{
		if (!is_array($data) || ($data['status'] ?? null) !== 'done') return '';
		$stamp = 'сформировано за '.(float)($data['duration'] ?? 0).' c';
		if (!empty($data['started_at'])) $stamp .= ', '.$data['started_at'];
		if (!empty($data['cached'])) $stamp .= ' (ответ из кэша сервиса)';
		return $stamp;
	}

	/** С какого числа адресов порт считается транзитным (конфиг transitFrom) */
	public function transitFrom(): int
	{
		return (int)($this->config['transitFrom'] ?? static::DEFAULT_TRANSIT_FROM);
	}

	/** Тип модели из switchTypes - коммутатор, за которой стоит сеть, а не он сам */
	public function isSwitchType(Techs $tech): bool
	{
		$types = $this->config['switchTypes'] ?? static::DEFAULT_SWITCH_TYPES;
		$type = is_object($tech->model) ? $tech->model->type : null;
		return is_object($type) && in_array($type->code, $types, true);
	}

	/** Оборудование ли это вообще (дешёвая проверка до обращений к связям) */
	protected static function isSwitchable(ArmsModel $model): bool
	{
		return $model instanceof Techs && !$model->isNewRecord;
	}

	public function renderPanel(string $panelId, ArmsModel $model): string
	{
		if ($panelId === static::PANEL_SWITCH) return $this->renderSwitchPanel($model);

		$attempt = (int)Yii::$app->request->get('attempt', 0);
		$macs = $this->requestedMacs($model);
		$targets = $this->targetsFor($model);
		$results = $this->search($macs, $targets);
		$this->cacheable = !$this->isPending($results);

		$html = $this->renderResults($results, $this->refreshUrl($model, $results, $attempt));

		//панель открыта в модалке (клик по иконке) - дадим ей заголовок:
		//h1 из ответа модалка переносит в свою шапку. Область опроса пишем
		//прямо в заголовке: из результата иначе не понять, где искали
		if ($this->requestedMac()) {
			$title = $this->getTitle().' — '.$this->scopeTitle($model);
			$html = Html::tag('h1', Html::encode($title)).$html;
		}
		return $html;
	}

	/**
	 * Панель «Что за портами» в карточке коммутатора: снимаем с него таблицу
	 * MAC целиком (mode=table) и раскладываем по портам.
	 *
	 * Опрашивается ровно один коммутатор — тот, чью карточку открыли: спрашивать
	 * площадку ради одной таблицы незачем.
	 */
	public function renderSwitchPanel(ArmsModel $model): string
	{
		/** @var Techs $model */
		$attempt = (int)Yii::$app->request->get('attempt', 0);
		$target = $this->describeTarget($model);
		if (!$target) return '<span class="text-secondary opacity-75">'
			.'у коммутатора не указан IP-адрес — опрашивать нечего</span>';

		//карточка члена стека: опрос идёт по общему IP и возвращает весь стек,
		//а показать надо только порты этого члена. Остальное - его соседям по
		//стеку, неприкаянное - представителю
		$members = $this->stackOf($model);
		$this->stacks = [$model->id => $members];
		foreach ($members as $member) $this->switches[$member->id] = $member;

		$data = null;
		$error = null;
		try {
			$data = $this->fetch(null, [$target], 'table');
			$data['rows'] = $this->annotateUplinks($this->attributeStack($data['rows'] ?? []));
			if (count($members) > 1) $data = $this->stackSlice($data, $model, $members);
		} catch (\Throwable $e) {
			$error = $e->getMessage();
		}

		$results = [['mac' => null, 'data' => $data, 'error' => $error]];
		//спиннер и ошибка - состояние, а не результат: в кэш им нельзя
		$this->cacheable = !$error && !$this->isPending($results)
			&& ($data['status'] ?? '') !== 'error' && empty($data['errors']);
		return $this->renderView('switch', [
			'ports' => $this->switchPorts($data['rows'] ?? [], $model,
				$data['ports'] ?? [], $data['neighbors'] ?? []),
			'data' => $data,
			'error' => $error,
			'refreshUrl' => $this->refreshUrl($model, $results, $attempt, static::PANEL_SWITCH),
			'provider' => $this,
			'tech' => $model,
			'stack' => count($members) > 1 ? $members : [],
			//имена, объявленные у соседей по стеку: «взять имена с коммутатора»
			//для этого члена их брать не должен
			'foreignNames' => count($members) > 1 ? static::foreignNames($model, $members) : [],
		]);
	}

	/**
	 * Доля одного члена стека в общем ответе: строки MAC с его id, паспорт и
	 * соседи по портам, которые принадлежат ему ({@see portOwner()});
	 * неприкаянные порты - представителю (первому по id).
	 * @param Techs[] $members
	 */
	protected function stackSlice(array $data, Techs $member, array $members): array
	{
		$representative = $members[0]->id === $member->id;
		$mine = function (string $port) use ($member, $members, $representative): bool {
			$owner = static::portOwner($port, $members);
			return is_object($owner) ? $owner->id === $member->id : $representative;
		};

		$data['rows'] = array_values(array_filter($data['rows'] ?? [],
			fn($row) => (int)($row['target'] ?? 0) === (int)$member->id));
		$data['ports'] = array_values(array_filter($data['ports'] ?? [],
			fn($item) => $mine((string)($item['name'] ?? ''))));
		$data['neighbors'] = array_values(array_filter($data['neighbors'] ?? [],
			fn($item) => $mine((string)($item['port'] ?? ''))));
		return $data;
	}

	/**
	 * Имена портов, объявленные у других членов стека.
	 * @param Techs[] $members
	 */
	public static function foreignNames(Techs $member, array $members): array
	{
		$names = [];
		foreach ($members as $other) {
			if ($other->id === $member->id) continue;
			foreach (array_keys($other->portsTemplate) as $name) $names[(string)$name] = true;
		}
		return array_keys($names);
	}

	/**
	 * Строки таблицы MAC -> порты коммутатора: что за каждым портом.
	 *
	 * Классификация тут только по числу адресов и связям портов; кто именно
	 * стоит за портом, решает сопоставление адресов с объектами
	 * ({@see resolveMacs()}) — оно идёт отдельно и одним запросом на всю
	 * таблицу, а не по строке.
	 *
	 * Порядок и состав портов берутся из объявления коммутатора
	 * ({@see Techs::getPortsTemplate()}): по имени порядок не восстановить, а
	 * инженеру в серверной нужен не только занятый порт, но и свободный —
	 * без объявления «порт пустой» и «порта не существует» неразличимы.
	 *
	 * Порт несёт и то, что записано в инвентаризации (строка `ports` и
	 * закреплённая за ней устройство), и то, что увидел опрос, и вердикт их
	 * сравнения ({@see verdict()}) — рисовать дифф по этому уже механика.
	 *
	 * @param Techs|null $tech коммутатор (null — порядок по числовому хвосту
	 *   имени и только те порты, на которых что-то нашлось)
	 * @param array $passport паспорт портов с коммутатора: описание, состояние,
	 *   настроенные VLAN, членство в агрегате (необязателен)
	 * @param array $neighbors соседи по LLDP/CDP (необязательны)
	 * @return array [['port'=>string,'vlans'=>string[],'macs'=>[['mac'=>string,'objects'=>ArmsModel[]]],
	 *   'count'=>int,'uplink'=>bool,'uplink_peer'=>string,'transit'=>bool,
	 *   'comment'=>string,'declared'=>bool,'link'=>Ports|null,'linked'=>Techs|null,
	 *   'found'=>Techs[],'verdict'=>string], ...]
	 */
	public function switchPorts(array $rows, ?Techs $tech = null, array $passport = [],
		array $neighbors = []): array
	{
		$declared = is_object($tech) ? $tech->portsList : [];
		$this->passport = [];
		foreach ($passport as $port) {
			if (!empty($port['name'])) $this->passport[(string)$port['name']] = $port;
		}
		$this->neighbors = [];
		$this->scanned = $tech;
		foreach ($neighbors as $neighbor) {
			if (!empty($neighbor['port'])) $this->neighbors[(string)$neighbor['port']][] = $neighbor;
		}
		$objects = $this->resolveMacs(array_column($rows, 'mac'));
		$transitFrom = $this->transitFrom();

		$ports = [];
		foreach ($rows as $row) {
			$name = (string)($row['port'] ?? '');
			if ($name === '') continue;

			if (!isset($ports[$name])) $ports[$name] = static::emptyPort($name);
			$ports[$name]['uplink'] = $ports[$name]['uplink'] || !empty($row['uplink']);
			if (empty($ports[$name]['uplink_peer'])) {
				$ports[$name]['uplink_peer'] = $row['uplink_peer'] ?? '';
			}

			$mac = static::hexMac($row['mac'] ?? '');
			if ($mac && !isset($ports[$name]['macs'][$mac])) {
				$ports[$name]['macs'][$mac] = ['mac' => $mac, 'objects' => $objects[$mac] ?? []];
			}

			$vlan = (string)($row['vlan'] ?? '');
			if ($vlan !== '' && !in_array($vlan, $ports[$name]['vlans'], true)) {
				$ports[$name]['vlans'][] = $vlan;
			}

			//сервис считает адреса на порту сам (port_macs), но у него это
			//число по одном коммутаторе - если его нет, считаем по строкам
			$ports[$name]['count'] = max($ports[$name]['count'],
				(int)($row['port_macs'] ?? 0), count($ports[$name]['macs']));
		}

		//порт может быть известен только из паспорта или от соседа - адресов на
		//нём нет, но показать его надо: свободная розетка и линк до соседа
		//видны именно так
		foreach (array_merge(array_keys($this->passport), array_keys($this->neighbors)) as $name) {
			if (!isset($ports[(string)$name])) $ports[(string)$name] = static::emptyPort((string)$name);
		}

		//таблицу MAC коммутатор ведёт на агрегате (Po1), а не на его портах:
		//раскладываем адреса по членам группы, чтобы у каждой розетки было
		//видно, что за ней. Членов знает паспорт (aggregate у порта)
		$members = [];
		foreach ($this->passport as $name => $item) {
			if (!empty($item['aggregate'])) $members[(string)$item['aggregate']][] = (string)$name;
		}
		foreach ($members as $aggregate => $names) {
			if (!isset($ports[$aggregate])) continue;
			foreach ($names as $name) {
				if (!isset($ports[$name])) $ports[$name] = static::emptyPort($name);
				foreach ($ports[$aggregate]['macs'] as $mac => $item) $ports[$name]['macs'][$mac] = $item;
				foreach ($ports[$aggregate]['vlans'] as $vlan) {
					if (!in_array($vlan, $ports[$name]['vlans'], true)) $ports[$name]['vlans'][] = $vlan;
				}
				$ports[$name]['count'] = max($ports[$name]['count'], $ports[$aggregate]['count']);
				$ports[$name]['uplink'] = $ports[$name]['uplink'] || $ports[$aggregate]['uplink'];
				if (!strlen($ports[$name]['uplink_peer'])) $ports[$name]['uplink_peer'] = $ports[$aggregate]['uplink_peer'];
			}
		}

		foreach ($ports as $name => &$port) {
			$port['macs'] = array_values($port['macs']);
			$port['physical'] = $this->isPhysical((string)$name);
			//за портом сеть, а не устройство: либо так сказали связи портов,
			//либо адресов слишком много для «устройство + телефон + виртуалки»
			$port['transit'] = $port['uplink'] || $port['count'] >= $transitFrom;
		}
		unset($port);

		//интерфейсы без розетки (сам агрегат, Vlan120, Loopback0) в таблице
		//портов не нужны: воткнуть в них нечего, а на 48-портовом коммутаторе их
		//ещё с десяток. Убираем те, что ничего не знают либо уже всё отдали
		//членам; агрегат без паспорта (SSH-коммутатор без SNMP) остаётся - иначе
		//адреса за ним пропали бы из таблицы. Сырые данные по всем - в
		//свёрнутом блоке. Объявленные в инвентаризации имена показываем всегда
		$declaredNames = array_map('strval', array_keys($declared));
		foreach ($ports as $name => $port) {
			if ($port['physical'] || in_array((string)$name, $declaredNames, true)) continue;
			if (!count($port['macs']) || isset($members[(string)$name])) unset($ports[$name]);
		}

		if (!$declared && $this->passport) {
			//объявления в инвентаризации нет, но коммутатор рассказал о себе сама -
			//берём её список портов: он в аппаратном порядке (по ifIndex)
			$ordered = [];
			foreach ($this->passport as $name => $port) {
				//интерфейсы без розетки, которые выше уже выкинуты, заново не заводим
				if (!isset($ports[(string)$name]) && !$this->isPhysical((string)$name)) continue;
				$found = static::matchPort((string)$name, $ports);
				$entry = is_null($found) ? static::emptyPort((string)$name) : $ports[$found];
				$entry['port'] = (string)$name;
				$entry['real'] = (string)$name;
				$ordered[] = $entry;
				if (!is_null($found)) unset($ports[$found]);
			}
			foreach ($ports as $port) $ordered[] = $port;
			return array_map([$this, 'annotatePort'], $ordered);
		}

		if (!$declared) {
			//коммутатор ничего про себя не объявил - остаётся порядок по номеру
			uasort($ports, fn($one, $other) =>
				strnatcasecmp(static::portKey($one['port']), static::portKey($other['port']))
					?: strnatcasecmp($one['port'], $other['port']));
			foreach ($ports as &$port) $port['real'] = $port['port'];
			unset($port);
			return array_map([$this, 'annotatePort'], array_values($ports));
		}

		//объявленные порты идут в объявленном порядке, включая свободные
		$ordered = [];
		foreach ($declared as $name => $declaration) {
			//имена портов бывают числовыми - PHP делает такие ключи массива int
			$name = (string)$name;
			$found = static::matchPort($name, $ports);
			$port = is_null($found) ? static::emptyPort($name) : $ports[$found];
			//показываем объявленное имя: оно с корпуса, а не из вывода коммутатора.
			//Настоящее имя запоминаем: инвентаризация зовёт розетку Ge0/23, а
			//коммутатор - Gi1/0/47, и паспорт с соседями лежат под её именем
			$port['real'] = is_null($found) ? '' : (string)$found;
			$port['port'] = $name;
			$port['comment'] = (string)($declaration['port_comment'] ?? '');
			$port['link'] = $declaration['port_link'] ?? null;
			$port['declared'] = true;
			$ordered[] = $port;
			if (!is_null($found)) unset($ports[$found]);
		}

		//порты, которых в объявлении нет (агрегаты Po1/BAGG1, забытые модули) -
		//в конец: это находка, а не мусор
		foreach ($ports as $port) {
			$port['real'] = $port['port'];
			$ordered[] = $port;
		}

		return array_map([$this, 'annotatePort'], $ordered);
	}

	/**
	 * Сравнение записанного с найденным по одному порту.
	 *
	 * Вердикты: ok (нашли то, что записано), replaced (вместо записанного
	 * другое известное), foreign (записанное молчит, но адреса на порту есть и
	 * они не опознаны), quiet (на порту вообще нет адресов), added (записано
	 * пусто, нашлось известное), seen (адреса есть, объектов с ними нет),
	 * free (пусто и там и там), transit (за портом сеть), self (за портом
	 * виден сам коммутатор - служебный порт или петля), unknown (опроса не
	 * было).
	 *
	 * Отдельно про «пропало»: такого вердикта НЕТ и предлагать «убрать с
	 * порта» не из чего. Порт без адресов — это и «устройство убрали», и
	 * «устройство выключено», и «оно молчало дольше времени старения записи»;
	 * различить их можно только по состоянию линка, а оно приедет вместе с
	 * паспортом портов. Предлагать стереть правильную запись на основании
	 * тишины нельзя: на коммутаторе с десятком спящих серверов это десяток
	 * предложений испортить данные.
	 */
	protected function annotatePort(array $port): array
	{
		$port = $this->applyPassport($port);
		$port['neighbors'] = $this->neighbors[$port['real']] ?? $this->neighbors[$port['port']] ?? [];
		$port['linked'] = static::linkedDevice($port['link'] ?? null);
		$port['found'] = static::foundDevices($port['macs']);

		//за портом виден адрес самого коммутатора: так бывает на служебном
		//порту (CPU у D-Link) и при петле. Предлагать связь коммутатора с самим
		//собой нельзя - это не находка, а предупреждение
		//Сосед по стеку - тот же коммутатор: его адрес за портом тоже «свой»
		$port['self'] = false;
		if (is_object($this->scanned)) {
			$selfIds = array_map(fn(Techs $member) => (int)$member->id,
				$this->stacks[$this->scanned->id] ?? [$this->scanned]);
			$others = array_values(array_filter($port['found'],
				fn(Techs $device) => !in_array((int)$device->id, $selfIds, true)));
			$port['self'] = count($others) !== count($port['found']);
			$port['found'] = $others;
		}

		//сосед по LLDP - куда более веский факт, чем адреса за портом: коммутатор
		//прямо говорит, кто на том конце кабеля и в какой он розетке
		$neighbor = $this->neighborDevice($port);
		if (is_object($neighbor['device'] ?? null)) {
			$port['found'] = [$neighbor['device']];
			$port['neighbor_port'] = $neighbor['port'];
		}

		$port['verdict'] = static::verdict($port);
		//предложения: записано пусто или другое - всегда; записанное сошлось, но
		//рядом нашлось ещё одно - только если из них складывается цепочка
		//(телефон воткнули между коммутатором и ПК, или ПК - за телефон)
		$port['proposals'] = [];
		if (in_array($port['verdict'], ['added', 'replaced'], true)) {
			$port['proposals'] = $this->proposals($port);
		} elseif ($port['verdict'] === 'ok' && count($port['found']) === 2) {
			$chain = $this->proposals($port);
			if (count($chain) === 1 && is_array($chain[0]['chain'])) $port['proposals'] = $chain;
		}
		return $port;
	}

	/**
	 * Сосед этого порта, опознанный в инвентаризации.
	 *
	 * Ищем по адресу (в том числе внутри диапазонов: производитель выделяет
	 * устройству блок адресов, и порт соседа попадает в него), затем по имени —
	 * инвентарному номеру или hostname.
	 *
	 * @return array ['device' => Techs|null, 'port' => string имя порта соседа]
	 */
	protected function neighborDevice(array $port): array
	{
		foreach ($port['neighbors'] as $neighbor) {
			$device = $this->identifyNeighbor($neighbor);
			if (is_object($device)) {
				return ['device' => $device, 'port' => (string)($neighbor['remote_port'] ?? '')];
			}
		}
		return ['device' => null, 'port' => ''];
	}

	/**
	 * Сосед, который заведомо не коммутатор, - карте сети не кандидат.
	 *
	 * Главный признак - LLDP system capabilities (`remote_caps`): сосед сам
	 * говорит, кем работает. Всё, что не bridge и не router (телефоны,
	 * станции, точки доступа), отфильтровывается. Соседям без capabilities
	 * (CDP без поля, старые прошивки) остаётся фолбэк по имени - телефоны
	 * объявляют себя SEP<MAC>/SIP<MAC>/«IP Phone» (шаблон neighborIgnore).
	 */
	public function isIgnoredNeighbor(array $neighbor): bool
	{
		$caps = trim((string)($neighbor['remote_caps'] ?? ''));
		if (strlen($caps)) {
			//телефон с ПК-портом честно объявляет и bridge (он им и работает),
			//поэтому telephone главнее: телефон - это телефон
			if (preg_match('~telephone~', $caps)) return true;
			return !preg_match('~bridge|router~', $caps);
		}
		$pattern = $this->config['neighborIgnore'] ?? static::DEFAULT_NEIGHBOR_IGNORE;
		if (!strlen((string)$pattern)) return false;
		$name = trim((string)($neighbor['remote_name'] ?? ''));
		return strlen($name) && preg_match($pattern, $name);
	}

	/**
	 * Кто этот сосед в инвентаризации: по адресу (и внутри диапазонов), по
	 * имени (инвентарный номер, hostname, с доменом и без), по IP (sysName у
	 * некоторых железок - адрес управления). Не опознан - null: это находка
	 * «незаписанный коммутатор», а не мусор.
	 */
	public function identifyNeighbor(array $neighbor): ?Techs
	{
		$device = null;
		$mac = static::hexMac($neighbor['remote_mac'] ?? '');
		if ($mac) {
			foreach ($this->resolveMacs([$mac])[$mac] ?? [] as $object) {
				$device = static::deviceOf($object);
				if (is_object($device)) break;
			}
			//адрес соседа может лежать внутри записанного диапазона
			if (!is_object($device)) $device = static::deviceByMacRange($mac);
		}

		$name = trim((string)($neighbor['remote_name'] ?? ''));
		if (!is_object($device) && strlen($name)) $device = static::deviceByName($name);
		if (!is_object($device) && strlen($name) && static::firstIp($name) === $name) {
			$device = static::deviceByIp($name);
		}
		return $device;
	}

	/**
	 * Устройство по любому из его адресов.
	 *
	 * Устройство - это НАБОР адресов (у ядра их семь), и LLDP может
	 * представиться любым из них, не обязательно первым. Тот же принцип, что
	 * у device aliases в NetDisco: сосед опознаётся по любому алиасу.
	 */
	protected static function deviceByIp(string $ip): ?Techs
	{
		foreach (Techs::find()->where(['like', 'techs.ip', $ip])->all() as $tech) {
			/** @var Techs $tech */
			foreach (preg_split('~\s+~', trim((string)$tech->ip)) as $candidate) {
				if (static::firstIp($candidate) === $ip) return $tech;
			}
		}
		return null;
	}

	/**
	 * Полный опрос площадки для карты сети: mode=table — соседи (LLDP/CDP) и
	 * таблицы MAC одним заходом. FDB тут не для показа, а для второго
	 * фильтра: за портом один адрес, он опознан и это не коммутатор — LLDP-
	 * сосед на этом порту точно не коммутатор, как бы себя ни называл.
	 * Строки разложены по членам стеков. Ответ сервиса как есть (status /
	 * rows / neighbors / errors), чтобы вызывающий видел pending и неопрошенных.
	 *
	 * @throws \RuntimeException сервис недоступен / ответил ошибкой
	 */
	public function siteNeighbors(int $siteId): array
	{
		$targets = $this->targets(static::placeSubtree($siteId));
		if (!$targets) return ['status' => 'done', 'rows' => [], 'neighbors' => [], 'errors' => [], 'targets' => []];
		$data = $this->fetch(null, $targets, 'table');
		$data['rows'] = $this->attributeStack($data['rows'] ?? []);
		$data['neighbors'] = $this->attributeStack($data['neighbors'] ?? []);
		return $data;
	}

	/**
	 * Имя порта, как его назвал сосед по LLDP, -> объявленный порт устройства.
	 *
	 * Правило для ЗАПИСИ связи строже, чем для показа: точное имя, затем
	 * единственное совпадение по ключу, иначе - человеку (кандидаты списком).
	 * Автосоздание по неоднозначному ключу запрещено: `portKey()` не различает
	 * Gi1/0/1 и Te1/0/1. Портов не объявлено - связь без порта.
	 *
	 * @return array ['id'=>int|null,'name'=>string|null,'candidates'=>[['id','name'],...]]
	 *   name null при пустом объявлении; candidates непусты только при неоднозначности
	 */
	public static function resolvePortName(Techs $device, string $lldpName): array
	{
		$declared = [];
		foreach ($device->portsList as $item) {
			$link = $item['port_link'] ?? null;
			$declared[(string)$item['port_name']] = is_object($link) ? (int)$link->id : null;
		}
		if (!count($declared)) return ['id' => null, 'name' => null, 'candidates' => []];

		$lldpName = trim($lldpName);
		if (array_key_exists($lldpName, $declared)) {    //значение null - порт без строки
			return ['id' => $declared[$lldpName], 'name' => $lldpName, 'candidates' => []];
		}
		$byKey = array_filter(array_keys($declared), fn($name) =>
			!static::isAggregate((string)$name) && static::portKey((string)$name) === static::portKey($lldpName));
		if (count($byKey) === 1) {
			$name = (string)reset($byKey);
			return ['id' => $declared[$name], 'name' => $name, 'candidates' => []];
		}
		//последний числовой хвост: «GigabitEthernet1/0/8» против объявленных
		//«1..28» у смартов. Требование единственности снимает коллизию
		//Gi1/0/8 против Te1/0/8: оба объявлены - решает человек
		$number = static::portNumber($lldpName);
		if ($number !== '') {
			$byNumber = array_filter(array_keys($declared), fn($name) =>
				!static::isAggregate((string)$name) && static::portNumber((string)$name) === $number);
			if (count($byNumber) === 1) {
				$name = (string)reset($byNumber);
				return ['id' => $declared[$name], 'name' => $name, 'candidates' => []];
			}
		}
		$candidates = [];
		foreach ($declared as $name => $id) $candidates[] = ['id' => $id, 'name' => (string)$name];
		return ['id' => null, 'name' => null, 'candidates' => $candidates];
	}

	/** Устройство, у которой искомый адрес попадает в записанный диапазон (issue #120) */
	protected static function deviceByMacRange(string $hex): ?Techs
	{
		$condition = MacsHelper::rangeMemberCondition(['techs.mac'], $hex);
		if (!$condition) return null;

		$tech = Techs::find()->where($condition)->limit(1)->one();
		return is_object($tech) ? $tech : null;
	}

	/** Устройство по имени соседа: инвентарный номер либо hostname */
	protected static function deviceByName(string $name): ?Techs
	{
		//LLDP печатает то короткое имя, то FQDN - домен отбрасываем
		$short = explode('.', $name)[0];
		$tech = Techs::find()
			->where(['or', ['techs.num' => $name], ['techs.hostname' => $name],
				['techs.num' => $short], ['techs.hostname' => $short]])
			->limit(1)->one();
		return is_object($tech) ? $tech : null;
	}

	/**
	 * Паспорт порта поверх находок: описание с коммутатора, состояние, VLAN,
	 * членство в агрегате.
	 *
	 * Настроенные VLAN важнее замеченных: из таблицы MAC видно только те, где
	 * был трафик, и транк с двадцатью VLAN покажет два.
	 */
	protected function applyPassport(array $port): array
	{
		$found = $this->passport[$port['real']] ?? $this->passport[$port['port']] ?? null;
		if (!$found) return $port;

		$port['description'] = (string)($found['description'] ?? '');
		$port['admin'] = (string)($found['admin'] ?? '');
		$port['oper'] = (string)($found['oper'] ?? '');
		$port['speed'] = (int)($found['speed'] ?? 0);
		$port['aggregate'] = (string)($found['aggregate'] ?? '');

		if (!empty($found['vlans'])) {
			$port['vlans'] = [];
			foreach ($found['vlans'] as $vlan) {
				$port['vlans'][] = ['vlan' => (string)$vlan['vlan'],
					'untagged' => !empty($vlan['untagged'])];
			}
			$port['vlans_configured'] = true;
		}
		return $port;
	}

	/**
	 * Что предложить привязать к порту, когда за ним нашлось оборудование.
	 *
	 * Одно устройство — одно предложение. Два устройства — самый частый
	 * случай «телефон с ПК за ним», и узнаём мы только вырожденную схему: у
	 * одного РОВНО два порта (мост), у другого один либо ни одного (лист) — тогда
	 * вопроса «кто за кем» нет вовсе, есть только «какими портами», и
	 * предлагаем цепочку «порт → телефон (Internet) ; телефон (PC) → ПК
	 * (eth)». Какой порт моста смотрит в коммутатор — подсказка по именам
	 * из конфига, в строке предложения он переключается. Всё остальное —
	 * список предложений «привязать X», схему решает человек.
	 *
	 * @return array [['device'=>Techs,'peers'=>[...],'chain'=>null|[
	 *   'via'=>['id'=>int|null,'name'=>string], 'leaf'=>Techs, 'leaf_peers'=>[...]]], ...]
	 */
	protected function proposals(array $port): array
	{
		$found = $port['found'];
		$prefer = (string)($port['neighbor_port'] ?? '');
		$neighborDevice = strlen($prefer) && count($found) ? $found[0] : null;

		//порт записанного устройства, которым оно уже смотрит на нас: занятые
		//порты из кандидатов исключаются, а этот - наш же, он и должен остаться
		$current = is_object($port['link'] ?? null) && is_object($port['link']->linkPort)
			? $port['link']->linkPort : null;

		$proposals = [];
		foreach ($found as $device) {
			$peers = static::peerPorts($device, is_object($neighborDevice)
				&& $neighborDevice->id === $device->id ? $prefer : '');
			if (is_object($current) && (int)$current->techs_id === (int)$device->id
				&& !in_array((string)$current->name, array_column($peers, 'name'), true)) {
				array_unshift($peers, ['id' => (int)$current->id, 'name' => (string)$current->name]);
			}
			$proposals[] = ['device' => $device, 'peers' => $peers, 'chain' => null];
		}
		if (count($proposals) !== 2) return $proposals;

		//вырожденная схема: ровно два порта у моста, ровно один у листа -
		//считаем по объявлению коммутатора, а не по свободным
		$declared = fn(array $item) => count($item['device']->portsTemplate);
		$bridges = array_filter($proposals, fn($item) => $declared($item) === 2 && count($item['peers']) === 2);
		//лист - устройство с одним портом либо вовсе без объявленных портов:
		//тогда оно привязывается без порта, и схема от этого не теряется
		$leaves = array_filter($proposals, fn($item) => $declared($item) === 0
			|| $declared($item) === 1 && count($item['peers']) === 1);
		if (count($bridges) !== 1 || count($leaves) !== 1) return $proposals;

		$bridge = reset($bridges);
		$leaf = reset($leaves);

		//какой порт моста смотрит в коммутатор - подсказка по именам из конфига,
		//иначе первый. Оба порта остаются в peers: в строке предложения это
		//переключатель, второй порт автоматически уходит к листу
		$peers = $bridge['peers'];
		//мост уже записан на этом порту - его порт к нам известен точно
		$toSwitch = is_object($current) && (int)$current->techs_id === (int)$bridge['device']->id
			? ['id' => (int)$current->id, 'name' => (string)$current->name] : null;
		if (is_null($toSwitch)) $toSwitch = static::pickPeer($peers, $this->config['bridgeToSwitchPorts']
			?? static::DEFAULT_BRIDGE_TO_SWITCH_PORTS);
		if (is_null($toSwitch)) {
			$toDevice = static::pickPeer($peers, $this->config['bridgeToDevicePorts']
				?? static::DEFAULT_BRIDGE_TO_DEVICE_PORTS);
			$toSwitch = is_array($toDevice) && $toDevice['name'] === $peers[0]['name'] ? $peers[1] : $peers[0];
		}
		$toLeaf = $peers[0]['name'] === $toSwitch['name'] ? $peers[1] : $peers[0];

		return [[
			'device' => $bridge['device'],
			'peers' => [$toSwitch, $toLeaf],
			'chain' => ['via' => $toLeaf, 'leaf' => $leaf['device'], 'leaf_peers' => $leaf['peers']],
		]];
	}

	/** Первый порт из списка, чьё имя совпадает с одним из названных (без регистра) */
	protected static function pickPeer(array $peers, array $names): ?array
	{
		$names = array_map('mb_strtolower', $names);
		foreach ($peers as $peer) {
			if (in_array(mb_strtolower(trim($peer['name'])), $names, true)) return $peer;
		}
		return null;
	}

	/**
	 * Свободные порты найденного устройства — кандидаты на «ту сторону» связи.
	 *
	 * @return array [['id'=>int|null,'name'=>string], ...]; id пуст, если порт
	 *   только объявлен моделью и строки под него ещё нет (создастся при связи)
	 */
	protected static function peerPorts(Techs $device, string $prefer = ''): array
	{
		//сосед сам сказал, каким портом он смотрит на нас - гадать не нужно
		if (strlen($prefer)) {
			$port = Ports::find()->where(['techs_id' => $device->id, 'name' => $prefer])->one();
			return [['id' => is_object($port) ? (int)$port->id : null, 'name' => $prefer]];
		}

		$peers = [];
		foreach ($device->portsList as $item) {
			$link = $item['port_link'] ?? null;
			//занятый порт в кандидаты не берём: перецеплять чужую связь молча нельзя
			if (is_object($link) && !empty($link->link_ports_id)) continue;
			$peers[] = [
				'id' => is_object($link) ? (int)$link->id : null,
				'name' => (string)$item['port_name'],
			];
		}
		return $peers;
	}

	/** Устройство, закреплённая за портом в инвентаризации */
	protected static function linkedDevice($link): ?Techs
	{
		if (!is_object($link)) return null;
		if (is_object($link->linkPort) && is_object($link->linkPort->tech)) return $link->linkPort->tech;
		return is_object($link->linkTech) ? $link->linkTech : null;
	}

	/**
	 * Найденные за портом устройства (без дублей).
	 *
	 * Адрес пишут и на ОС — тогда за портом стоит её АРМ: порт соединяется с
	 * железом, а не с операционной системой.
	 */
	protected static function foundDevices(array $macs): array
	{
		$devices = [];
		foreach ($macs as $item) {
			foreach ($item['objects'] as $object) {
				$tech = static::deviceOf($object);
				if (is_object($tech) && !isset($devices[$tech->id])) $devices[$tech->id] = $tech;
			}
		}
		return array_values($devices);
	}

	/** Объект инвентаризации -> устройство (у ОС это её АРМ) */
	protected static function deviceOf(ArmsModel $object): ?Techs
	{
		if ($object instanceof Techs) return $object;
		if ($object instanceof Comps && is_object($object->arm)) return $object->arm;
		return null;
	}

	/** Вердикт по порту {@see annotatePort()} */
	protected static function verdict(array $port): string
	{
		//сосед опознан: связь коммутатор-коммутатор важнее счёта адресов за портом
		$neighbor = count($port['found']) && !empty($port['neighbor_port'] ?? '');
		if (!$neighbor && !empty($port['transit'])) return 'transit';

		//порт выключен администратором: воткнуть в него нельзя, и это не «свободен»
		if (($port['admin'] ?? '') === 'down' && !count($port['macs'])
			&& !is_object($port['linked'] ?? null)) return 'disabled';

		$linked = $port['linked'];
		$found = $port['found'];

		//за портом только сам коммутатор и ничего не записано - отдельный вердикт:
		//ни «свободен», ни «привязать» тут не подходят
		if (!empty($port['self']) && !count($found) && !is_object($linked)) return 'self';

		if (is_object($linked)) {
			foreach ($found as $device) if ($device->id === $linked->id) return 'ok';
			//на порту другое известное устройство - вот это уже факт
			if (count($found)) return 'replaced';
			//адреса есть, но чьи - неизвестно: может быть вторая сетевая того
			//же сервера, которой нет в инвентаризации
			return count($port['macs']) ? 'foreign' : 'quiet';
		}
		if (count($found)) return 'added';

		//адрес или сосед на порту есть, а кто это - в инвентаризации не
		//записано: порт занят, и называть его свободным нельзя
		return count($port['macs']) || count($port['neighbors']) ? 'seen' : 'free';
	}

	/**
	 * Какой из найденных портов соответствует объявленному имени.
	 *
	 * Одну и ту же розетку коммутатор и инвентаризация называют по-разному:
	 * `Gi1/0/12`, `GigabitEthernet1/0/12`, `12`. Сравниваем по убыванию
	 * строгости — точное имя, затем «числовой хвост» ({@see portKey()}), затем
	 * просто номер, — и каждый раз требуем единственного кандидата.
	 *
	 * Ослабление до номера допустимо потому, что это ПОКАЗ: ошибиться можно
	 * только тем, что занятый порт нарисуется свободным, а находка уедет в
	 * конец списка — и то и другое видно глазом. Для записи связей правило
	 * строже (plans/network-map.md, этап 3.3).
	 *
	 * @param array $ports ещё не разобранные найденные порты (имя => данные)
	 * @return string|null имя найденного порта
	 */
	protected static function matchPort(string $name, array $ports): ?string
	{
		$candidates = [
			fn($found) => (string)$found === $name,
			//дальше сравнение приблизительное, и агрегат в него не пускаем:
			//у Po1 тот же числовой хвост, что у порта 1
			fn($found) => !static::isAggregate((string)$found)
				&& static::portKey((string)$found) === static::portKey($name),
			fn($found) => !static::isAggregate((string)$found)
				&& static::portNumber((string)$found) !== ''
				&& static::portNumber((string)$found) === static::portNumber($name),
		];

		foreach ($candidates as $matches) {
			$found = array_keys(array_filter($ports, $matches, ARRAY_FILTER_USE_KEY));
			if (count($found) !== 1) continue;    //ноль или неоднозначно - пробуем слабее
			return $found[0];
		}
		return null;
	}

	/** Имя похоже на агрегированный канал, а не на физический порт? */
	public static function isAggregate(string $name): bool
	{
		return (bool)preg_match(static::AGGREGATE_RE, trim($name));
	}

	/**
	 * Розетка ли это на корпусе.
	 *
	 * Коммутатор говорит это типом интерфейса (ifType 6 - ethernetCsmacd): всё
	 * остальное - агрегат (161), VLAN-интерфейс (53), loopback (24) - на
	 * корпусе не существует. Сервис постарше типа не присылает, паспорта
	 * может не быть вовсе - тогда судим по имени: известные имена пачек и
	 * интерфейсов Vlan/Loopback/Null.
	 */
	public function isPhysical(string $name): bool
	{
		$item = $this->passport[$name] ?? null;
		if (is_array($item) && isset($item['type'])) return (int)$item['type'] === 6;
		if (static::isAggregate($name)) return false;
		//cpu - служебный «порт» процессора коммутатора (D-Link): розетки нет
		return !preg_match('~^(vlan|vl|loopback|lo|null|tunnel|nve|bvi|irb|cpu)\s*\d*$~i', trim($name));
	}

	/** Номер порта: последнее число имени ('' — числа нет) */
	public static function portNumber(string $name): string
	{
		return preg_match('~(\d+)\s*$~', $name, $found) ? $found[1] : '';
	}

	/** Заготовка порта без находок (свободный либо ещё не наполненный) */
	protected static function emptyPort(string $name): array
	{
		return [
			'port' => $name,
			//как порт зовётся на самом коммутаторе: показываем-то мы объявленное имя
			//(оно с корпуса), но паспорт, соседи и «взять имена» ходят по этому
			'real' => '',
			'vlans' => [],
			'macs' => [],
			'count' => 0,
			'uplink' => false,
			'uplink_peer' => '',
			'transit' => false,
			'comment' => '',
			'declared' => false,
			'link' => null,
			'linked' => null,
			'found' => [],
			'proposals' => [],
			'neighbors' => [],
			'neighbor_port' => '',
			'description' => '',
			'admin' => '',
			'oper' => '',
			'speed' => 0,
			'aggregate' => '',
			'vlans_configured' => false,
			//розетка на корпусе, а не Vlan120/Po1/Loopback0: коммутатор говорит это
			//типом интерфейса (ifType 6), а без паспорта судим по имени
			'physical' => true,
			//за портом виден адрес самого коммутатора ({@see annotatePort()})
			'self' => false,
			'verdict' => 'free',
		];
	}

	/**
	 * Адреса -> объекты инвентаризации, одним запросом на группу.
	 *
	 * Адрес пишут то на железе, то на его ОС, поэтому ищем в обоих. Диапазоны
	 * адресов (issue #120) сюда не попадают: в таблице коммутатора адреса
	 * всегда конкретные, а вхождение в диапазон — отдельный (дорогой) запрос,
	 * он понадобится при опознании соседей.
	 *
	 * @param string[] $macs адреса в любом виде
	 * @return array [12hex => ArmsModel[]]
	 */
	public function resolveMacs(array $macs): array
	{
		$needles = [];
		foreach ($macs as $mac) {
			$hex = static::hexMac($mac);
			if ($hex && !in_array($hex, $needles, true)) $needles[] = $hex;
		}
		if (!$needles) return [];

		$found = [];
		//чанками: условие с сотней LIKE - это один проход по таблице, а сотня
		//отдельных запросов - сто проходов (индекса по подстроке всё равно нет)
		foreach (array_chunk($needles, 100) as $chunk) {
			foreach ([Techs::class, Comps::class] as $class) {
				$condition = ['or'];
				foreach ($chunk as $hex) $condition[] = ['like', 'mac', $hex];

				/** @var ArmsModel $item */
				foreach ($class::find()->where($condition)->all() as $item) {
					foreach (static::hexMacs($item->mac) as $hex) {
						if (!in_array($hex, $chunk, true)) continue;
						$found[$hex][] = $item;
					}
				}
			}
		}
		return $found;
	}

	/** Адрес в 12 hex ('' — это не полный адрес) */
	public static function hexMac(?string $value): string
	{
		$hex = preg_replace('/[^0-9a-f]/', '', mb_strtolower((string)$value));
		return strlen($hex) === 12 && hexdec($hex) > 0 ? $hex : '';
	}

	/** Все одиночные адреса многострочного поля (диапазоны пропускаем) */
	public static function hexMacs(?string $value): array
	{
		$macs = [];
		foreach (explode("\n", (string)$value) as $line) {
			$hex = static::hexMac($line);
			if ($hex && !in_array($hex, $macs, true)) $macs[] = $hex;
		}
		return $macs;
	}

	/**
	 * Адреса, по которым идёт опрос: либо один запрошенный (иконка поиска
	 * рядом с конкретным адресом), либо все адреса объекта.
	 * @return string[]
	 */
	public function requestedMacs(ArmsModel $model): array
	{
		$macs = $this->macs($model);
		$requested = $this->requestedMac();

		//чужой адрес в запросе игнорируем: опрашиваем только то, что есть
		//у объекта - иначе панель превращается в свободный сканер
		if ($requested && in_array($requested, $macs, true)) return [$requested];

		return $macs;
	}

	/** Где искали — для заголовка результата: имя площадки или «все площадки» */
	public function scopeTitle(ArmsModel $model): string
	{
		if ($this->requestedScope() === static::SCOPE_ALL) return 'все площадки';

		$site = static::siteOf($model);
		$place = $site ? Places::findOne($site) : null;
		//площадка не заполнена - опрашивали всё, так и скажем
		return is_object($place) ? $place->name : 'все площадки';
	}

	/** Запрошенный адрес из параметра mac (12 hex) или null */
	protected function requestedMac(): ?string
	{
		$mac = preg_replace('/[^0-9a-f]/', '',
			mb_strtolower((string)Yii::$app->request->get('mac', '')));
		return strlen($mac) === 12 ? $mac : null;
	}

	// --- цели опроса ----------------------------------------------------

	/**
	 * Коммутаторы, которые надо опросить ради этого объекта.
	 *
	 * scope=place (по умолчанию) — опрашиваем площадку, где стоит объект:
	 * искать МАК рабочей станции по всем филиалам страны незачем. Нет
	 * площадки (не заполнена) — опрашиваем всё.
	 *
	 * @param ArmsModel|null $model объект, из карточки которого ищем
	 *   (null - поиск «везде», со страницы поиска по MAC)
	 * @param int|null $placeId явная площадка (страница поиска)
	 * @return array целей для сервиса
	 */
	public function targetsFor(?ArmsModel $model = null, ?int $placeId = null): array
	{
		$placeIds = null;
		if (!is_null($placeId)) {
			$placeIds = static::placeSubtree($placeId);
		} elseif ($model && $this->requestedScope() === static::SCOPE_PLACE) {
			$site = static::siteOf($model);
			if ($site) $placeIds = static::placeSubtree($site);
		}

		return $this->targets($placeIds);
	}

	/**
	 * Область опроса: площадка объекта или все коммутаторы.
	 *
	 * Запрос (пункт меню у адреса) перекрывает умолчание конфига: обычно
	 * ищут там, где стоит объект, но устройство могли и перевезти — тогда
	 * нужен опрос по всем площадкам.
	 */
	public function requestedScope(): string
	{
		$requested = (string)Yii::$app->request->get('scope', '');
		if (in_array($requested, [static::SCOPE_PLACE, static::SCOPE_ALL], true)) return $requested;

		return ($this->config['scope'] ?? static::SCOPE_PLACE) === static::SCOPE_ALL
			? static::SCOPE_ALL : static::SCOPE_PLACE;
	}

	/**
	 * Список целей для сервиса: оборудование-коммутаторы с адресом.
	 * @param int[]|null $placeIds ограничение по помещениям (null - без него)
	 */
	public function targets(?array $placeIds = null): array
	{
		$query = Techs::find()
			->joinWith(['model.type', 'model.manufacturer', 'state'], true)
			->where(['tech_types.code' => $this->config['switchTypes'] ?? static::DEFAULT_SWITCH_TYPES])
			//опрашиваем только работающее: у статуса флаг operating (склад,
			//ремонт, списание - не цели). Без статуса - считаем работающим
			->andWhere(['or', ['tech_states.operating' => 1], ['techs.state_id' => null]])
			->andWhere(['not', ['techs.ip' => null]])
			->andWhere(['<>', 'techs.ip', ''])
			->limit((int)($this->config['maxTargets'] ?? static::DEFAULT_MAX_TARGETS));

		if (!is_null($placeIds)) $query->andWhere(['techs.places_id' => $placeIds]);

		//стек - конфигурное состояние, а не инвентарная единица: у членов свои
		//карточки и номера, а management-IP один. Цель опроса - одна на стек
		//(представитель - член с наименьшим id), иначе сервис схлопывает N
		//целей по host и весь результат прилипает к первой попавшейся.
		//Ограничение площадкой обязательно: одинаковые адреса в филиалах - норма
		$groups = [];
		foreach ($query->all() as $tech) {
			/** @var Techs $tech */
			$ip = static::firstIp($tech->ip);
			if (!$ip) continue;    //в поле адреса что-то есть, но не IP
			$groups[static::siteOf($tech).'|'.$ip][] = $tech;
		}

		$targets = [];
		$this->switches = [];
		$this->stacks = [];
		foreach ($groups as $members) {
			usort($members, fn(Techs $one, Techs $other) => $one->id <=> $other->id);
			foreach ($members as $member) $this->switches[$member->id] = $member;
			$target = $this->describeTarget($members[0]);
			if (!$target) continue;
			$this->stacks[$members[0]->id] = $members;
			$targets[] = $target;
		}
		return $targets;
	}

	/**
	 * Члены стека, в котором состоит коммутатор: тот же тип, та же площадка,
	 * тот же management-IP, не архивные; по id. Одиночка - стек из одного.
	 *
	 * @return Techs[]
	 */
	public function stackOf(Techs $tech): array
	{
		$ip = static::firstIp($tech->ip);
		if (!$ip) return [$tech];
		$site = static::siteOf($tech);

		$members = [$tech->id => $tech];
		$query = Techs::find()
			->joinWith(['model.type', 'state'], true)
			->where(['tech_types.code' => $this->config['switchTypes'] ?? static::DEFAULT_SWITCH_TYPES])
			->andWhere(['or', ['tech_states.operating' => 1], ['techs.state_id' => null]])
			->andWhere(['like', 'techs.ip', $ip])
			->andWhere(['<>', 'techs.id', $tech->id]);
		foreach ($query->all() as $candidate) {
			/** @var Techs $candidate */
			if (static::firstIp($candidate->ip) !== $ip || static::siteOf($candidate) !== $site) continue;
			$members[$candidate->id] = $candidate;
		}
		ksort($members);
		return array_values($members);
	}

	/**
	 * Разложить строки результата по членам стека.
	 *
	 * Сервис вернул всё под id цели (представителя); порт принадлежит тому
	 * члену, в чьём объявлении он есть - точное имя, затем ключ, и только
	 * при единственном совпадении. Не нашлось ни у кого (или нашлось у
	 * нескольких - члены с одинаковыми модельными именами без своих
	 * «портов фактически») - строка остаётся на представителе с пометкой.
	 * Никаких догадок по диапазонам MAC.
	 */
	public function attributeStack(array $rows): array
	{
		foreach ($rows as &$row) {
			$members = $this->stacks[$row['target'] ?? 0] ?? null;
			if (!$members || count($members) < 2) continue;
			$owner = static::portOwner((string)($row['port'] ?? ''), $members);
			$row['target'] = is_object($owner) ? $owner->id : $members[0]->id;
			if (!is_object($owner)) $row['stack_unassigned'] = true;
		}
		return $rows;
	}

	/**
	 * Какому члену стека принадлежит порт: по объявленным именам, точное ->
	 * ключ, и каждый раз - единственный кандидат.
	 * @param Techs[] $members
	 */
	public static function portOwner(string $name, array $members): ?Techs
	{
		if ($name === '') return null;
		foreach ([
			fn(string $declared) => $declared === $name,
			fn(string $declared) => !static::isAggregate($declared)
				&& static::portKey($declared) === static::portKey($name),
		] as $matches) {
			$owners = [];
			foreach ($members as $member) {
				foreach (array_keys($member->portsTemplate) as $declared) {
					if ($matches((string)$declared)) { $owners[$member->id] = $member; break; }
				}
			}
			if (count($owners) === 1) return reset($owners);
		}
		return null;
	}

	/**
	 * Одна цель для сервиса: адрес, вендор и модель, как их знает
	 * инвентаризация (сопоставление «модель → драйвер» живёт в сервисе).
	 * Заодно запоминает коммутатор для рендера ссылок в результатах.
	 *
	 * @return array|null null — у коммутатора нет адреса, опрашивать нечего
	 */
	public function describeTarget(Techs $tech): ?array
	{
		$ip = static::firstIp($tech->ip);
		if (!$ip) return null;

		$this->switches[$tech->id] = $tech;
		return [
			'id' => $tech->id,
			'host' => $ip,
			'vendor' => is_object($tech->model) && is_object($tech->model->manufacturer)
				? $tech->model->manufacturer->name : '',
			'model' => is_object($tech->model) ? $tech->model->name : '',
		];
	}

	/** Опрошенные коммутаторы (id => Techs) — рендер ссылок в результатах */
	public function switches(): array
	{
		return $this->switches;
	}

	/**
	 * Площадка объекта: корень ветки помещений, в которой он стоит
	 * (филиал), либо null, если помещение не заполнено.
	 */
	public static function siteOf(ArmsModel $model): ?int
	{
		$placeId = null;
		if ($model instanceof Techs) $placeId = $model->places_id;
		//у ОС своего помещения нет - берём у её АРМ
		if ($model instanceof Comps && is_object($model->arm)) $placeId = $model->arm->places_id;
		if (!$placeId) return null;

		$place = Places::findOne($placeId);
		$guard = 0;    //дерево помещений редактируют руками - страхуемся от кольца
		while (is_object($place) && $place->parent_id && ++$guard < 32) {
			$place = Places::findOne($place->parent_id);
		}
		return is_object($place) ? $place->id : null;
	}

	/** Все помещения ветки (сам корень + потомки любой глубины) */
	public static function placeSubtree(int $rootId): array
	{
		$children = [];
		foreach (Places::find()->select(['id', 'parent_id'])->asArray()->all() as $place) {
			$children[$place['parent_id']][] = (int)$place['id'];
		}

		$ids = [$rootId];
		$queue = [$rootId];
		while ($queue) {
			$current = array_shift($queue);
			foreach ($children[$current] ?? [] as $child) {
				if (in_array($child, $ids, true)) continue;    //кольцо в дереве
				$ids[] = $child;
				$queue[] = $child;
			}
		}
		return $ids;
	}

	/** Первый IPv4 из многострочного поля адресов */
	public static function firstIp(?string $value): ?string
	{
		foreach (preg_split('/[\s,;]+/', (string)$value) as $candidate) {
			$candidate = trim($candidate);
			if (filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return $candidate;
		}
		return null;
	}

	// --- адреса объекта --------------------------------------------------

	/**
	 * MAC-адреса объекта для поиска: одиночные адреса самого объекта и
	 * (по умолчанию) привязанной ОС/АРМ — адрес сетевой карты записывают
	 * то на железе, то на ОС. Диапазоны адресов пропускаем: на портах
	 * ищется конкретный адрес.
	 *
	 * @return string[] нормализованные адреса (12 hex), не больше maxMacs
	 */
	public function macs(ArmsModel $model): array
	{
		$sources = [$model->mac ?? ''];

		if ($this->config['includeLinked'] ?? true) {
			if ($model instanceof Techs && is_object($model->comp)) $sources[] = $model->comp->mac;
			if ($model instanceof Comps && is_object($model->arm)) $sources[] = $model->arm->mac;
		}

		$macs = [];
		foreach ($sources as $source) {
			foreach (explode("\n", (string)$source) as $line) {
				$mac = preg_replace('/[^0-9a-f]/', '', mb_strtolower($line));
				//12 hex = одиночный адрес; 24 = диапазон (issue #120), его не ищем
				if (strlen($mac) !== 12 || hexdec($mac) === 0) continue;
				if (!in_array($mac, $macs, true)) $macs[] = $mac;
			}
		}

		return array_slice($macs, 0, max(1, (int)($this->config['maxMacs'] ?? static::DEFAULT_MAX_MACS)));
	}

	// --- опрос ------------------------------------------------------------

	/**
	 * Опрос сервиса по списку адресов (по запросу на адрес, цели общие).
	 * Недоступность сервиса — штатный исход, пока хоть один адрес ответил;
	 * не ответил ни один — исключение (панель недоступна, ловит ядро).
	 *
	 * @param string[] $macs
	 * @param array $targets цели ({@see targetsFor()})
	 * @return array [['mac'=>string, 'data'=>array|null, 'error'=>string|null], ...]
	 * @throws \RuntimeException если сервис не ответил ни по одному адресу
	 */
	public function search(array $macs, array $targets): array
	{
		if (!$targets) return [];    //нечего опрашивать - панель скажет об этом

		$results = [];
		$lastError = null;
		foreach ($macs as $mac) {
			try {
				$data = $this->fetch($mac, $targets);
				//сначала раскладываем по членам стека, потом метим транзит: связи
				//портов лежат у конкретного члена, а не у представителя
				$data['rows'] = $this->annotateUplinks($this->attributeStack($data['rows'] ?? []));
				$results[] = ['mac' => $mac, 'data' => $data, 'error' => null];
			} catch (\Throwable $e) {
				$lastError = $e;
				$results[] = ['mac' => $mac, 'data' => null, 'error' => $e->getMessage()];
			}
		}

		$answered = array_filter($results, fn($result) => !is_null($result['data']));
		if ($lastError && !$answered) throw $lastError;

		return $results;
	}

	/**
	 * Пометка транзитных портов по связям портов инвентаризации: если
	 * найденный порт связан с портом другого коммутатора, устройство через
	 * него видно транзитом, а подключено в другом месте.
	 *
	 * Имена портов на коммутаторе и в инвентаризации пишут по-разному
	 * (Gi1/0/12, GigabitEthernet1/0/12, ge1/0/12), поэтому сравниваем по
	 * «числовому хвосту» имени.
	 */
	public function annotateUplinks(array $rows): array
	{
		$techIds = array_filter(array_unique(array_column($rows, 'target')));
		if (!$techIds) return $rows;

		/** @var Ports[] $ports */
		$ports = Ports::find()->where(['techs_id' => $techIds])->all();
		$linked = [];
		foreach ($ports as $port) {
			if (!$port->link_ports_id || !is_object($port->linkPort)) continue;
			$peer = $port->linkPort->tech;
			//транзит - это когда за портом ДРУГОЙ КОММУТАТОР: сервер или СХД
			//на том конце кабеля - не сеть, а то самое устройство, которое и
			//должно быть видно на порту
			if (!is_object($peer) || !$this->isSwitchType($peer)) continue;
			$linked[$port->techs_id][static::portKey($port->name)] = $peer->name;
		}

		foreach ($rows as &$row) {
			$key = static::portKey($row['port'] ?? '');
			$peer = $linked[$row['target'] ?? null][$key] ?? null;
			if (is_null($peer)) continue;
			$row['uplink'] = true;
			$row['uplink_peer'] = $peer;
		}
		return $rows;
	}

	/** Сопоставимое имя порта: числовой хвост либо имя без разделителей */
	public static function portKey(string $name): string
	{
		if (preg_match('~(\d+(?:/\d+)+|\d+)\s*$~', $name, $found)) return $found[1];
		return mb_strtolower(preg_replace('~[^0-9a-zA-Z]~', '', $name));
	}

	/** Есть ли среди результатов незавершённый опрос */
	public function isPending(array $results): bool
	{
		foreach ($results as $result) {
			if (($result['data']['status'] ?? null) === 'pending') return true;
		}
		return false;
	}

	/** Рендер результатов (панель карточки и страница поиска — один вид) */
	public function renderResults(array $results, ?string $refreshUrl = null): string
	{
		return $this->renderView('ports', [
			'results' => $results,
			'refreshUrl' => $refreshUrl,
			'switches' => $this->switches,
			'provider' => $this,
		]);
	}

	/**
	 * URL самоперезапроса панели, пока сервис опрашивает (null — не нужен
	 * или попытки исчерпаны).
	 */
	protected function refreshUrl(ArmsModel $model, array $results, int $attempt,
		string $panel = self::PANEL): ?string
	{
		if (!$this->isPending($results)) return null;
		if ($attempt + 1 >= (int)($this->config['maxAttempts'] ?? static::DEFAULT_MAX_ATTEMPTS)) return null;

		return Url::to(['/integrations/panel',
			'provider' => $this->id,
			'panel' => $panel,
			'class' => StringHelper::class2Id(get_class($model)),
			'id' => $model->id,
			'attempt' => $attempt + 1,
		] + ($this->compact ? ['compact' => 1] : []));
	}

	/**
	 * Запрос опроса к сервису.
	 * @param string|null $mac нормализованный адрес (12 hex); null — режимам
	 *   table/neighbors адрес не нужен, они снимают с коммутатора всё
	 * @param array $targets цели опроса
	 * @param string $mode режим сервиса: lookup / table / neighbors
	 * @return array ответ сервиса (status/rows/errors/targets/...)
	 * @throws \RuntimeException при ошибке транспорта/ответа
	 */
	protected function fetch(?string $mac, array $targets, string $mode = 'lookup'): array
	{
		$payload = [
			'targets' => $targets,
			'mode' => $mode,
			'wait' => (int)($this->config['wait'] ?? static::DEFAULT_WAIT),
		];
		if (!is_null($mac)) $payload['mac'] = $mac;

		[$response, $status] = $this->httpPost(
			rtrim($this->config['url'], '/').'/api/search',
			json_encode($payload, JSON_UNESCAPED_UNICODE)
		);

		$data = json_decode($response, true);
		if (!is_array($data)) {
			//не-JSON: ответил не сервис, а что-то на пути (прокси, веб-сервер)
			$snippet = trim(mb_substr(preg_replace('/\s+/', ' ', strip_tags($response)), 0, 160));
			throw new \RuntimeException(
				"Некорректный ответ сервиса поиска MAC (HTTP $status): ".($snippet ?: 'пустой ответ'));
		}
		if ($status >= 400) {
			throw new \RuntimeException('Сервис поиска MAC (HTTP '.$status.'): '
				.($data['error'] ?? 'ошибка запроса'));
		}
		if (empty($data['status'])) throw new \RuntimeException('Некорректный ответ сервиса поиска MAC: нет status');

		return $data;
	}

	/**
	 * HTTP POST с токеном. Вынесен отдельным методом: тесты подменяют его,
	 * не трогая сеть.
	 * @return array [string тело, int HTTP-код (0 если не распознан)]
	 * @throws \RuntimeException при ошибке транспорта (ловит ядро)
	 */
	protected function httpPost(string $url, string $body): array
	{
		$context = stream_context_create([
			'http' => [
				'method' => 'POST',
				'timeout' => $this->timeout(),
				'header' => "Content-Type: application/json\r\n"
					.'Authorization: Bearer '.$this->config['token']."\r\n",
				'content' => $body,
				'ignore_errors' => true, //читать тело и при 4xx (там JSON с error)
			],
			'ssl' => [
				'verify_peer' => $this->config['verifySsl'] ?? false,
				'verify_peer_name' => $this->config['verifySsl'] ?? false,
			],
		]);

		$response = @file_get_contents($url, false, $context);
		if ($response === false) throw new \RuntimeException('Сервис поиска MAC недоступен');

		$status = 0;
		if (isset($http_response_header[0]) && preg_match('#^HTTP/\S+\s+(\d+)#', $http_response_header[0], $m)) {
			$status = (int)$m[1];
		}
		return [$response, $status];
	}
}
