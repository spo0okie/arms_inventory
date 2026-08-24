<?php

namespace app\components;

use app\components\integrations\IntegrationsRegistry;
use app\components\integrations\providers\MacSearchProvider;
use app\models\Places;
use app\models\Ports;
use app\models\Techs;
use yii\helpers\Url;

/**
 * Карта сети площадки: коммутаторы и связи между ними — из записей
 * инвентаризации, без опроса (docs/dev/network-map.md).
 *
 * Граф строится из того, что уже записано в `ports`: связь порт↔порт между
 * двумя коммутаторами площадки — ребро. Опрос (LLDP) ничего нового сюда не
 * приносит, он подтверждает или опровергает эти рёбра слоем — тот же
 * принцип, что у таблицы портов в карточке.
 *
 * Узел — коммутатор; стек (общий management-IP на площадке) — один узел с
 * перечнем членов: стек выводится, а не хранится. Оконечные устройства не
 * рисуются — только счётчик занятых портов на узле: на площадке в 70
 * коммутаторов серверы и телефоны превратили бы схему в кашу.
 */
class NetworkMap
{
	/** @var Places площадка (корень ветки помещений) */
	public Places $site;

	/** @var array id узла => ['id','members'=>Techs[],'name','ports'=>int,'used'=>int] */
	public array $nodes = [];

	/**
	 * @var array рёбра: ключ «a|b|группа» => ['a'=>nodeId,'b'=>nodeId,
	 *   'links'=>[['port'=>Ports,'peer'=>Ports], ...], 'aggr'=>string]
	 */
	public array $edges = [];

	/**
	 * @var array аплинки на коммутаторы ДРУГИХ площадок - настоящий край
	 * карты. Связи с не-коммутаторами (серверы, СХД, телефоны) сюда не
	 * попадают вовсе: на боевой площадке это все патч-корды разом, портянка
	 * на тысячи строк, и им место в карточке коммутатора, а не тут
	 */
	public array $outside = [];

	/** @var bool сгруппировать узлы по помещениям (mermaid subgraph) */
	public bool $groupByPlace = false;

	/** сколько неопознанных соседей рисовать на схеме (остальные - в таблице) */
	const UNKNOWN_ON_DIAGRAM = 12;

	/** @var int[] id техники => id узла */
	protected array $nodeOf = [];

	/**
	 * @var array|null слой сверки с сетью ({@see overlay()}):
	 *  confirmed  - записанные рёбра, которые LLDP подтвердил (ключи edges);
	 *  unseen     - записанные рёбра, которых LLDP не видит (ключи edges) -
	 *               обе стороны опрошены, а линка нет: выключен, без LLDP, перенесён;
	 *  found      - связи, которые LLDP видит, а записи нет: предложения записать,
	 *               [['a'=>Techs,'port'=>string,'b'=>Techs,'peer'=>[...resolvePortName],
	 *                 'conflict'=>Ports|null, 'protocol'=>string]];
	 *  unknown    - сосед не опознан в инвентаризации: незаписанный коммутатор;
	 *  outside    - сосед опознан, но не на карте (не коммутатор / другая площадка);
	 *  failed     - коммутаторы, которые не ответили (строки errors сервиса);
	 *  answered   - id коммутаторов, от которых есть хоть что-то.
	 */
	public ?array $overlay = null;

	public function __construct(Places $site)
	{
		$this->site = $site;
		$this->build();
	}

	/** Коды типов, которые считаем коммутаторами: как у интеграции macsearch, если она настроена */
	public static function switchTypes(): array
	{
		foreach (IntegrationsRegistry::providers() as $provider) {
			if ($provider instanceof MacSearchProvider) {
				return $provider->config['switchTypes'] ?? MacSearchProvider::DEFAULT_SWITCH_TYPES;
			}
		}
		return MacSearchProvider::DEFAULT_SWITCH_TYPES;
	}

	/** Площадки — корни дерева помещений, на которых есть коммутаторы */
	public static function sites(): array
	{
		$roots = Places::find()->where(['parent_id' => null])->orWhere(['parent_id' => 0])
			->orderBy('name')->all();
		return $roots;
	}

	protected function build(): void
	{
		$placeIds = MacSearchProvider::placeSubtree($this->site->id);
		/** @var Techs[] $switches */
		$switches = Techs::find()
			->joinWith(['model.type', 'state'], true)
			->with(['ports.linkPort.tech.model.type', 'ports.linkPort.tech.state'])
			->where(['tech_types.code' => static::switchTypes()])
			//на карте только работающее: у статуса флаг operating; без статуса -
			//считаем работающим
			->andWhere(['or', ['tech_states.operating' => 1], ['techs.state_id' => null]])
			->andWhere(['techs.places_id' => $placeIds])
			->orderBy('techs.id')
			->all();

		//стеки: общий первый IP -> один узел
		$groups = [];
		foreach ($switches as $switch) {
			$ip = MacSearchProvider::firstIp($switch->ip);
			$groups[$ip ? 'ip:'.$ip : 'tech:'.$switch->id][] = $switch;
		}
		foreach ($groups as $members) {
			$node = [
				'id' => $members[0]->id,
				'members' => $members,
				'name' => implode(' + ', array_map(fn(Techs $tech) => $tech->name, $members)),
				'ports' => 0,
				'used' => 0,
				//помещение узла - для группировки на схеме
				'place_id' => $members[0]->places_id,
				'place' => is_object($members[0]->place) ? $members[0]->place->name : '',
			];
			foreach ($members as $member) {
				$this->nodeOf[$member->id] = $node['id'];
				$node['ports'] += count($member->portsTemplate);
				foreach ($member->ports as $port) if ($port->link_ports_id) $node['used']++;
			}
			$this->nodes[$node['id']] = $node;
		}

		//рёбра: каждая пара портов ровно один раз (связь парная, видна с обеих сторон)
		$seen = [];
		foreach ($switches as $switch) {
			foreach ($switch->ports as $port) {
				$peer = $port->linkPort;
				if (!is_object($peer) || !is_object($peer->tech)) continue;
				$pairKey = min($port->id, $peer->id).'-'.max($port->id, $peer->id);
				if (isset($seen[$pairKey])) continue;
				$seen[$pairKey] = true;

				$a = $this->nodeOf[$switch->id];
				$b = $this->nodeOf[$peer->tech->id] ?? null;
				if (is_null($b)) {
					//та сторона не на карте. Коммутатор другой площадки - край
					//карты, перечисляем; всё остальное (серверы, телефоны) - не
					//тема этой страницы, см. комментарий у $outside
					if (static::isSwitchModel($peer->tech)) {
						$this->outside[] = ['port' => $port, 'peer' => $peer];
					}
					continue;
				}
				if ($a === $b) continue;    //связь внутри стека - не ребро карты

				//группа портов - одно ребро с числом кабелей, а не N параллельных
				$aggr = (string)($port->aggr ?: $peer->aggr);
				$key = min($a, $b).'|'.max($a, $b).'|'.$aggr;
				if (!isset($this->edges[$key])) {
					$this->edges[$key] = ['a' => $a, 'b' => $b, 'aggr' => $aggr, 'links' => []];
				}
				//порт со стороны узла a - первым, чтобы подпись читалась слева направо
				$this->edges[$key]['links'][] = $a === min($a, $b)
					? ['port' => $port, 'peer' => $peer] : ['port' => $peer, 'peer' => $port];
			}
		}
	}

	/**
	 * Сверка записанного с тем, что видят коммутаторы по LLDP/CDP.
	 *
	 * Сосед опознаётся провайдером (адрес, диапазон, имя, IP). LLDP
	 * симметричен - одну связь сообщают обе стороны, поэтому находки
	 * складываются по неупорядоченной паре (устройство, порт). Записанное
	 * ребро, которого не видно ни с одной из опрошенных сторон, помечается,
	 * но не предлагается к удалению: «не видно» - это и выключен, и LLDP
	 * отключён, и перенесли. Решает человек.
	 *
	 * @param array $data ответ сервиса mode=neighbors (rows/errors), строки
	 *   уже разложены по членам стеков
	 */
	public function overlay(array $data, MacSearchProvider $provider): void
	{
		$overlay = ['confirmed' => [], 'unseen' => [], 'found' => [], 'unknown' => [],
			'outside' => [], 'failed' => $data['errors'] ?? [], 'answered' => []];

		//записанные связи по ключу (устройство, имя порта) с обеих сторон
		$recorded = [];
		foreach ($this->edges as $key => $edge) {
			foreach ($edge['links'] as $link) {
				$recorded[static::endKey($link['port'])] = ['edge' => $key, 'port' => $link['port'], 'peer' => $link['peer']];
				$recorded[static::endKey($link['peer'])] = ['edge' => $key, 'port' => $link['peer'], 'peer' => $link['port']];
			}
		}
		//связи за пределы карты тоже «записанное»: если LLDP видит на этом порту
		//коммутатор площадки, это расхождение, а не чистая находка
		foreach ($this->outside as $link) {
			$recorded[static::endKey($link['port'])] = ['edge' => null, 'port' => $link['port'], 'peer' => $link['peer']];
		}

		$seenPairs = [];
		foreach ($data['rows'] ?? [] as $row) {
			$local = $this->tech((int)($row['target'] ?? 0));
			$localPort = trim((string)($row['port'] ?? ''));
			if (!is_object($local) || $localPort === '') continue;
			$overlay['answered'][$local->id] = true;

			//телефоны и прочая ботва LLDP - не кандидаты в коммутаторы: считаем,
			//но не показываем (счётчик в шапке, чтобы фильтр не был молчаливым)
			if ($provider->isIgnoredNeighbor($row)) {
				$overlay['ignored'] = ($overlay['ignored'] ?? 0) + 1;
				continue;
			}

			$remote = $provider->identifyNeighbor($row);
			if (!is_object($remote)) {
				$name = trim((string)($row['remote_name'] ?? ''));
				$mac = trim((string)($row['remote_mac'] ?? ''));
				//запись без какой-либо личности (ни имени, ни адреса) чинить не
				//по чему: это застарелые/пустые строки LLDP-таблиц, только шум
				if ($name === '' && $mac === '') {
					$overlay['noise'] = ($overlay['noise'] ?? 0) + 1;
					continue;
				}
				//одного соседа коммутатор повторяет по разу на запись таблицы -
				//схлопываем по (порт, кто): человеку нужен факт, а не тираж
				$key = $local->id.'|'.$localPort.'|'.mb_strtolower($name ?: $mac);
				if (isset($overlay['unknown'][$key])) {
					$overlay['unknown'][$key]['count']++;
					continue;
				}
				$overlay['unknown'][$key] = ['a' => $local, 'port' => $localPort,
					'row' => $row, 'count' => 1];
				continue;
			}
			if (!isset($this->nodeOf[$remote->id])) {
				$overlay['outside'][] = ['a' => $local, 'port' => $localPort, 'b' => $remote, 'row' => $row];
				continue;
			}
			if ($this->nodeOf[$remote->id] === $this->nodeOf[$local->id]) continue;    //внутри стека

			//имя порта, как его сообщил сосед, -> объявленный порт соседа
			$remotePort = trim((string)($row['remote_port'] ?? ''));
			$resolved = MacSearchProvider::resolvePortName($remote, $remotePort);
			$localResolved = MacSearchProvider::resolvePortName($local, $localPort);
			$localName = $localResolved['name'] ?? $localPort;

			//одна связь - с двух сторон: второй раз не считаем
			$pair = static::pairKey($local->id, $localName, $remote->id, $resolved['name'] ?? $remotePort);
			if (isset($seenPairs[$pair])) continue;
			$seenPairs[$pair] = true;

			//записано ли это ребро: смотрим с локальной стороны
			$known = $recorded[$local->id.'|'.$localName] ?? null;
			if (is_object($known['peer'] ?? null) && (int)$known['peer']->techs_id === (int)$remote->id
				&& (is_null($resolved['name']) || (string)$known['peer']->name === (string)$resolved['name'])) {
				$overlay['confirmed'][$known['edge']] = true;
				continue;
			}

			//на порту соседа тоже может быть записано другое - связь парная, и
			//запись с нашей стороны молча перецепила бы его
			$knownRemote = is_null($resolved['name']) ? null
				: ($recorded[$remote->id.'|'.$resolved['name']] ?? null);
			$remoteConflict = is_object($knownRemote['peer'] ?? null)
				&& !((int)$knownRemote['peer']->techs_id === (int)$local->id
					&& (string)$knownRemote['peer']->name === $localName)
				? $knownRemote['peer'] : null;

			$overlay['found'][] = [
				'a' => $local, 'port' => $localName,
				'b' => $remote, 'peer' => $resolved, 'lldp_port' => $remotePort,
				//записано что-то другое (с нашей стороны или со стороны соседа) -
				//показываем, что именно, и предложение превращается в замену
				'conflict' => is_object($known['peer'] ?? null) ? $known['peer'] : null,
				'conflict_remote' => $remoteConflict,
				'protocol' => (string)($row['protocol'] ?? ''),
			];
		}

		//записанное, которого не видно ни с одной из ОТВЕТИВШИХ сторон
		foreach ($this->edges as $key => $edge) {
			if (isset($overlay['confirmed'][$key])) continue;
			$aAnswered = $this->nodeAnswered($edge['a'], $overlay['answered']);
			$bAnswered = $this->nodeAnswered($edge['b'], $overlay['answered']);
			if ($aAnswered || $bAnswered) $overlay['unseen'][$key] = true;
		}

		$overlay['unknown'] = array_values($overlay['unknown']);
		$this->overlay = $overlay;
	}

	/** Ключ конца связи: устройство + имя порта */
	protected static function endKey(Ports $port): string
	{
		return $port->techs_id.'|'.$port->name;
	}

	/** Неупорядоченный ключ пары концов */
	protected static function pairKey(int $a, string $ap, int $b, string $bp): string
	{
		$one = $a.'|'.$ap;
		$two = $b.'|'.$bp;
		return $one < $two ? $one.'#'.$two : $two.'#'.$one;
	}

	/** Коммутатор карты по id (член любого узла) */
	public function tech(int $id): ?Techs
	{
		$nodeId = $this->nodeOf[$id] ?? null;
		if (is_null($nodeId)) return null;
		foreach ($this->nodes[$nodeId]['members'] as $member) if ($member->id === $id) return $member;
		return null;
	}

	/** Ответил ли хоть один член узла */
	protected function nodeAnswered(int $nodeId, array $answered): bool
	{
		foreach ($this->nodes[$nodeId]['members'] as $member) if (isset($answered[$member->id])) return true;
		return false;
	}

	/**
	 * Текст диаграммы mermaid. Имена экранируются как строки в кавычках;
	 * клик по узлу ведёт в карточку (первого члена стека). Со слоем сверки:
	 * подтверждённые рёбра обычные, невидимые LLDP - жёлтые, найденные и не
	 * записанные - зелёный пунктир, неопознанные соседи - узлы «?».
	 */
	public function mermaid(): string
	{
		$lines = ['graph TD'];

		//узлы; по желанию - в рамках помещений (subgraph). Узел прямо на корне
		//площадки - «без своего помещения», рамка вокруг всей площадки ничего
		//не говорит и остаётся снаружи
		$byPlace = [];
		foreach ($this->nodes as $node) {
			$grouped = $this->groupByPlace && $node['place_id']
				&& (int)$node['place_id'] !== (int)$this->site->id;
			$byPlace[$grouped ? (int)$node['place_id'] : 0][] = $node;
		}
		foreach ($byPlace as $placeId => $nodes) {
			if ($placeId) {
				$lines[] = '  subgraph p'.$placeId.'["'.static::quote($nodes[0]['place']).'"]';
			}
			foreach ($nodes as $node) {
				$label = $node['name'].($node['ports']
					? '<br/><small>'.$node['used'].' / '.$node['ports'].'</small>' : '');
				$lines[] = '  n'.$node['id'].'["'.static::quote($label).'"]';
			}
			if ($placeId) $lines[] = '  end';
		}
		foreach ($this->nodes as $node) {
			$lines[] = '  click n'.$node['id'].' "'.Url::to(['/techs/view', 'id' => $node['id']]).'"';
		}

		$styles = [];
		$index = 0;
		foreach ($this->edges as $key => $edge) {
			$first = $edge['links'][0];
			$label = $first['port']->name.' — '.$first['peer']->name;
			if (count($edge['links']) > 1) {
				$label = ($edge['aggr'] ?: 'группа').' ×'.count($edge['links']);
			}
			$lines[] = '  n'.$edge['a'].' ---|"'.static::quote($label).'"| n'.$edge['b'];
			if (isset($this->overlay['unseen'][$key])) $styles[] = '  linkStyle '.$index.' stroke:#ffc107,stroke-width:3px';
			$index++;
		}

		if (is_array($this->overlay)) {
			foreach ($this->overlay['found'] as $found) {
				$label = $found['port'].' — '.($found['peer']['name'] ?? $found['lldp_port']);
				$lines[] = '  n'.$this->nodeOf[$found['a']->id].' -.-|"'.static::quote($label).'"| n'
					.$this->nodeOf[$found['b']->id];
				$styles[] = '  linkStyle '.$index.' stroke:#198754,stroke-width:3px';
				$index++;
			}
			//неопознанные - узлом на ИМЯ соседа, не на строку: один сосед виден
			//нескольким коммутаторам, и это должен быть один «?». Больше
			//дюжины не рисуем - схема не резиновая, полный список в таблице
			$unknownNodes = [];
			$skipped = 0;
			foreach ($this->overlay['unknown'] as $unknown) {
				$row = $unknown['row'];
				$name = trim((string)($row['remote_name'] ?? '')) ?: (string)($row['remote_mac'] ?? '?');
				$nodeKey = mb_strtolower($name);
				if (!isset($unknownNodes[$nodeKey])) {
					if (count($unknownNodes) >= static::UNKNOWN_ON_DIAGRAM) { $skipped++; continue; }
					$unknownNodes[$nodeKey] = 'u'.(count($unknownNodes) + 1);
					$lines[] = '  '.$unknownNodes[$nodeKey].'(["? '.static::quote($name).'"])';
				}
				$lines[] = '  n'.$this->nodeOf[$unknown['a']->id].' -.-|"'.static::quote(
					$unknown['port'].' — '.($row['remote_port'] ?? '')).'"| '.$unknownNodes[$nodeKey];
				$styles[] = '  linkStyle '.$index.' stroke:#6c757d,stroke-dasharray:3';
				$index++;
			}
			if ($skipped) {
				$lines[] = '  uMore(["… ещё '.$skipped.' неопознанных — список под схемой"])';
			}
		}
		return implode("\n", array_merge($lines, $styles));
	}

	/** Тип модели устройства - из списка «коммутаторов» */
	public static function isSwitchModel(Techs $tech): bool
	{
		$type = is_object($tech->model) ? $tech->model->type : null;
		return is_object($type) && in_array($type->code, static::switchTypes(), true);
	}

	/** Строка в кавычках mermaid: кавычки и разметка не должны ломать диаграмму */
	protected static function quote(string $text): string
	{
		return str_replace(['"', '#'], ['#quot;', '#35;'], $text);
	}
}
