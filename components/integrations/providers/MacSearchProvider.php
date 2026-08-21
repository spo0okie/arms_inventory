<?php

namespace app\components\integrations\providers;

use app\components\integrations\IntegrationProvider;
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
 * Обход занимает секунды, но железка может тормозить: сервис отвечает либо
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
	/** id единственной панели провайдера */
	const PANEL = 'ports';

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

	/** @var Techs[] опрошенные коммутаторы (id => модель) — для рендера ссылок */
	protected array $switches = [];

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
		return $macs ? implode(',', $macs).'@'.$this->requestedScope() : null;
	}

	/**
	 * Панель есть, но сама в карточке не рисуется ('auto' => false): опрос
	 * коммутаторов — дорогая операция, запускать её при каждом открытии
	 * карточки незачем. Панель открывают по клику иконки рядом с адресом
	 * ({@see \app\components\MacSearchWidget}) через тот же proxy.
	 */
	public function panels(ArmsModel $model): array
	{
		return [
			static::PANEL => [
				'title' => $this->getTitle(),
				'ttl' => $this->config['cacheTtl'] ?? static::DEFAULT_TTL,
				'auto' => (bool)($this->config['autoPanel'] ?? false),
			],
		];
	}

	public function renderPanel(string $panelId, ArmsModel $model): string
	{
		$attempt = (int)Yii::$app->request->get('attempt', 0);
		$macs = $this->requestedMacs($model);
		$targets = $this->targetsFor($model);
		$results = $this->search($macs, $targets);

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
	 * ищут там, где стоит объект, но железку могли и перевезти — тогда
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
			//архивные (списанные) коммутаторы опрашивать бессмысленно
			->andWhere(['or', ['tech_states.archived' => 0], ['tech_states.archived' => null]])
			->andWhere(['not', ['techs.ip' => null]])
			->andWhere(['<>', 'techs.ip', ''])
			->limit((int)($this->config['maxTargets'] ?? static::DEFAULT_MAX_TARGETS));

		if (!is_null($placeIds)) $query->andWhere(['techs.places_id' => $placeIds]);

		$targets = [];
		$this->switches = [];
		foreach ($query->all() as $tech) {
			/** @var Techs $tech */
			$ip = static::firstIp($tech->ip);
			if (!$ip) continue;    //в поле адреса что-то есть, но не IP

			$this->switches[$tech->id] = $tech;
			$targets[] = [
				'id' => $tech->id,
				'host' => $ip,
				'vendor' => is_object($tech->model) && is_object($tech->model->manufacturer)
					? $tech->model->manufacturer->name : '',
				'model' => is_object($tech->model) ? $tech->model->name : '',
			];
		}
		return $targets;
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
				$data['rows'] = $this->annotateUplinks($data['rows'] ?? []);
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
	 * Имена портов на железке и в инвентаризации пишут по-разному
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
			$linked[$port->techs_id][static::portKey($port->name)] =
				is_object($peer) ? $peer->name : '';
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
	protected function refreshUrl(ArmsModel $model, array $results, int $attempt): ?string
	{
		if (!$this->isPending($results)) return null;
		if ($attempt + 1 >= (int)($this->config['maxAttempts'] ?? static::DEFAULT_MAX_ATTEMPTS)) return null;

		return Url::to(['/integrations/panel',
			'provider' => $this->id,
			'panel' => static::PANEL,
			'class' => StringHelper::class2Id(get_class($model)),
			'id' => $model->id,
			'attempt' => $attempt + 1,
		] + ($this->compact ? ['compact' => 1] : []));
	}

	/**
	 * Запрос опроса к сервису.
	 * @param string $mac нормализованный адрес (12 hex)
	 * @param array $targets цели опроса
	 * @return array ответ сервиса (status/rows/errors/targets/...)
	 * @throws \RuntimeException при ошибке транспорта/ответа
	 */
	protected function fetch(string $mac, array $targets): array
	{
		$payload = [
			'mac' => $mac,
			'targets' => $targets,
			'wait' => (int)($this->config['wait'] ?? static::DEFAULT_WAIT),
		];

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
