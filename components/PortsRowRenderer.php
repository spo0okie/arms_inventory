<?php

namespace app\components;

use app\components\widgets\page\ModelWidget;
use app\models\Ports;
use app\models\Techs;
use Yii;
use yii\helpers\Html;

/**
 * Содержимое строки о порте - кусками, чтобы его могли разложить и таблица
 * ({@see views/techs/_ports-table.php}), и раскладка корпуса
 * ({@see views/techs/_ports-layout.php}).
 *
 * Про один порт есть ровно один набор фактов: что записано, что увидел опрос,
 * что предлагается сделать. Раскладка корпуса - другой ВИД тех же фактов, а не
 * другие факты, поэтому рендер тут один, а виды отличаются только тем, куда
 * они кладут получившиеся куски: таблица - по ячейкам строки, раскладка - в
 * повёрнутую колонку над портом или под ним.
 *
 * Куски отдаются готовым HTML: собирать их из данных - работа этого класса,
 * расставлять - работа вида.
 */
class PortsRowRenderer
{
	/** Пометки диффа: галочка - подтверждение, кнопка - предложение действия */
	const MARKS = [
		'ok' => ['fas fa-check text-secondary', 'Найдено то, что записано'],
		'self' => ['fas fa-exclamation text-warning', 'За портом виден адрес самого коммутатора: '
			.'это служебный порт (CPU) либо петля. Связь коммутатора с самим собой записать нельзя'],
	];

	/** Предложения: иконка, подсказка, что делать (do), нужно ли подтверждение */
	const OFFERS = [
		'replaced' => ['fas fa-redo text-success',
			'На порту другое оборудование - заменить', 'attach', null],
		'added' => ['fas fa-plus text-success',
			'Порт был пуст, оборудование обнаружено - привязать', 'attach', null],
		'foreign' => ['fas fa-times text-danger',
			'Записанное оборудование не отозвалось, а адреса на порту чужие. '
			.'Убрать оборудование с порта?',
			'detach', 'Записанного оборудования на порту не видно, но адреса там есть. '
			.'Оборудование может быть просто выключено, а адреса принадлежать его '
			.'второму интерфейсу. Точно убрать его с порта?'],
		'quiet' => ['fas fa-times text-danger',
			'Адресов на порту не видно - убрать оборудование с порта?',
			'detach', 'Оборудование может быть просто выключено. Точно убрать его с порта?'],
	];

	/** @var Techs устройство, чьи порты выводим */
	protected Techs $model;

	/** @var array строки о портах {@see \app\components\integrations\providers\MacSearchProvider::switchPorts()} */
	protected array $rows;

	/** @var bool был ли опрос: без него вердикты не выносятся */
	protected bool $scanned;

	/** @var int с какого числа адресов порт считается транзитным */
	protected int $transitFrom;

	/** @var array члены каждой группы портов по данным коммутатора: имя группы => имена портов */
	protected array $aggregateMembers = [];

	/** @var int сколько групп коммутатор собрал, а инвентаризация об этом не знает */
	protected int $aggregateOffers = 0;

	public function __construct(Techs $model, array $rows, bool $scanned, int $transitFrom = 4)
	{
		$this->model = $model;
		$this->rows = $rows;
		$this->scanned = $scanned;
		$this->transitFrom = $transitFrom;

		//члены каждой группы: ярлык ставится на всех сразу, по одному он смысла не имеет
		foreach ($rows as $port) {
			if (!strlen($port['aggregate'] ?? '')) continue;
			$this->aggregateMembers[$port['aggregate']][] = $port['port'];
			//коммутатор собрал порт в группу, а в инвентаризации этого нет
			if (!is_object($port['link'] ?? null)
				|| (string)$port['link']->aggr !== (string)$port['aggregate']) $this->aggregateOffers++;
		}
	}

	/**
	 * Нужна ли колонка агрегации: у большинства железок её содержимое пусто,
	 * а место она занимает
	 */
	public function hasAggregates(): bool
	{
		foreach ($this->rows as $port) {
			if (!empty($port['aggregate'])) return true;
			if (is_object($port['link'] ?? null) && strlen((string)$port['link']->aggr)) return true;
		}
		return false;
	}

	/** Сколько всего расхождений нашёл опрос */
	public function offered(): int
	{
		$offered = $this->aggregateOffers;
		foreach ($this->rows as $port) if (isset(static::OFFERS[$port['verdict']])) $offered++;
		return $offered;
	}

	/** Сколько из них принимается одной кнопкой (только безопасное: привязка и ярлык группы) */
	public function acceptable(): int
	{
		$acceptable = $this->aggregateOffers;
		foreach ($this->rows as $port) if (count($port['proposals'] ?? []) === 1) $acceptable++;
		return $acceptable;
	}

	/**
	 * Всё о порте готовым HTML.
	 *
	 * @return array [
	 *   'port' => имя порта ссылкой и VLAN под ним,
	 *   'aggregate' => группа портов: записанная, замеченная, кнопка ярлыка,
	 *   'comment' => пояснение из инвентаризации и описание с коммутатора,
	 *   'connection' => ['mode' => 'port'|'tech'|'none', 'comment' => подпись
	 *      порта той стороны, 'body' => что на порту: записанное и находки],
	 *   'action' => последняя колонка: галочка, кнопки предложений, «транзит»,
	 * ]
	 */
	public function parts(array $port): array
	{
		[$body, $buttons, $extraLines] = $this->findings($port);

		return [
			'port' => $this->portCell($port),
			'aggregate' => $this->aggregateCell($port),
			'comment' => $this->commentCell($port),
			'connection' => $this->connectionCell($port, $body, $extraLines),
			'action' => $this->actionCell($port, $buttons),
		];
	}

	/** Имя порта ссылкой (на запись либо на её создание) и VLAN под ним */
	protected function portCell(array $port): string
	{
		$link = $port['link'];
		$html = is_object($link)
			? $this->view()->render('@app/views/ports/item',
				['model' => $link, 'return' => 'previous', 'modal' => true])
			: Html::a(Ports::$port_prefix.$port['port'], ['/ports/create',
				'return' => 'previous',
				'Ports[name]' => $port['port'],
				'Ports[comment]' => $port['comment'],
				'Ports[techs_id]' => $this->model->id,
			], ['class' => 'open-in-modal-form', 'data-reload-page-on-submit' => 1]);

		if (!count($port['vlans'])) return $html;

		//настроенные VLAN приходят с коммутатора (паспорт портов); если паспорта
		//нет - показываем те, где замечен трафик, и говорим об этом в подсказке.
		//Нетегированный выделен. У каждого номера - своя подсказка «что это за
		//сеть» из инвентаризации (VLAN → L2-домен → сети), чтобы не ходить в IPAM
		$hints = $this->vlanHints();
		$names = [];
		foreach ($port['vlans'] as $vlan) {
			//паспорт даёт структуру, таблица MAC - просто номер
			$number = is_array($vlan) ? $vlan['vlan'] : $vlan;
			$label = is_array($vlan) && $vlan['untagged']
				? '<b>'.Html::encode($number).'</b>' : Html::encode($number);
			$hint = $hints[(int)$number] ?? '';
			$names[] = $hint === '' ? $label
				: '<span qtip_ttip="'.Html::encode('VLAN '.$number.': '.$hint).'">'.$label.'</span>';
		}
		return $html.'<br><small class="text-secondary opacity-75" qtip_ttip="'
			.Html::encode($port['vlans_configured']
				? 'VLAN, настроенные на порту (жирным - нетегированный)'
				: 'VLAN, в которых на этом порту замечены адреса')
			.'">VLAN '.implode(', ', $names).'</small>';
	}

	/** @var array|null [номер VLAN => подсказка]: имя и сети из инвентаризации */
	private $vlanHintsCache = null;

	/**
	 * Подсказки VLAN площадки коммутатора (лениво, один запрос на панель).
	 * Сети привязаны к площадке цепочкой сеть → VLAN → L2-домен → помещение -
	 * берём домены поддерева площадки, на которой стоит коммутатор.
	 */
	protected function vlanHints(): array
	{
		if (!is_null($this->vlanHintsCache)) return $this->vlanHintsCache;
		$place = $this->model->place ?? null;
		if (!is_object($place)) return $this->vlanHintsCache = [];
		return $this->vlanHintsCache = \app\models\NetVlans::hintsForPlaces(
			\app\components\integrations\providers\MacSearchProvider::placeSubtree((int)$place->top->id));
	}

	/** Группа портов: записанная, замеченная коммутатором, кнопка ярлыка */
	protected function aggregateCell(array $port): string
	{
		$link = $port['link'];
		$declared = is_object($link) ? (string)$link->aggr : '';
		$scanned = (string)$port['aggregate'];

		$html = '';
		if (strlen($declared)) {
			$html .= Html::encode($declared);
			if ($scanned !== '' && $scanned !== $declared) {
				$html .= ' <span class="text-danger small" qtip_ttip="'
					.Html::encode('На коммутаторе порт собран в '.$scanned).'">≠ '
					.Html::encode($scanned).'</span>';
			} elseif ($scanned === '' && $this->scanned) {
				$html .= ' <span class="text-danger small" qtip_ttip="'
					.Html::encode('На коммутаторе порт ни в какую группу не собран').'">≠</span>';
			}
		}
		if (!strlen($scanned) || $scanned === $declared) return $html;

		//коммутатор говорит, что порт в группе, а в инвентаризации не так:
		//ставим ярлык всем её портам одним кликом
		$members = $this->aggregateMembers[$scanned] ?? [$port['port']];
		if (!strlen($declared)) $html .= '<span class="text-secondary">'.Html::encode($scanned).'</span>';
		return $html.Html::button('<i class="fas fa-object-group text-success"></i>', [
			'class' => 'btn btn-sm btn-link p-0 port-scan-accept',
			'qtip_ttip' => 'Пометить как '.$scanned.' порты: '.implode(', ', $members),
			'data-scan' => json_encode([
				'tech' => $this->model->id,
				'do' => 'aggregate',
				'aggregate' => $port['aggregate'],
				'members' => implode("\n", $members),
			]),
			'onclick' => 'portsScanApply(this)',
		]);
	}

	/** Пояснение из инвентаризации и описание, сделанное на самом коммутаторе */
	protected function commentCell(array $port): string
	{
		$link = $port['link'];
		$html = Html::encode(is_object($link) ? (string)$link->comment : $port['comment']);
		if (!strlen($port['description'])) return $html;

		//подпись, которую сделал сетевик на самом коммутаторе: латиница, обрывки -
		//но человеку полезно. Наш комментарий не трогаем и дополнять им не
		//предлагаем: это две разные записи об одном порте, и сводить их вправе
		//только человек
		return $html.'<br><small class="text-secondary opacity-75" qtip_ttip="'
			.Html::encode('Описание порта на самом коммутаторе').'">'
			.Html::encode($port['description']).'</small>';
	}

	/**
	 * Что опрос увидел на порту: предложения привязки, неопознанные адреса,
	 * соседи по LLDP. Всё это идёт в ту же ячейку, что и записанное - это два
	 * утверждения об одном кабеле, и читать их надо рядом.
	 *
	 * @return array [строка «под записанным» с переносом, кнопки предложений,
	 *   те же находки без ведущего переноса]
	 */
	protected function findings(array $port): array
	{
		$verdict = $port['verdict'];
		$extra = [];
		$buttons = [];
		//предложения привязки: строка "? что куда" здесь, кнопка - в последней
		//колонке. Найденное перечислять отдельно незачем - оно и есть предложение
		foreach ($port['proposals'] ?? [] as $index => $proposal) {
			[$line, $button] = $this->proposalLine($port, $proposal, $index);
			$extra[] = $line;
			$buttons[] = $button;
		}
		if (!count($port['proposals'] ?? [])
			&& in_array($verdict, ['replaced', 'added', 'foreign', 'seen'], true)) {
			$seen = [];
			foreach ($port['found'] as $device) {
				$seen[] = ModelWidget::widget(['model' => $device, 'options' => ['static_view' => true]]);
			}
			//ОС без оборудования - в общем списке находок, не отдельной секцией.
			//Единственное отличие от находки-оборудования: привязать нельзя
			//(порт соединяется с железом) - поэтому без кнопки, а починка
			//(указать АРМ в карточке ОС) - в подсказке
			foreach ($port['found_os'] ?? [] as $os) {
				$seen[] = '<span qtip_ttip="'.Html::encode('Это ОС: оборудование у неё '
					.'не указано, поэтому привязать порт нельзя. Укажите у ОС её АРМ, '
					.'и следующий опрос предложит связь').'">'
					.ModelWidget::widget(['model' => $os, 'options' => ['static_view' => true]])
					.'</span>';
			}
			//адрес, за которым в инвентаризации никого: показываем как есть -
			//может пригодиться, а прятать факт незачем
			foreach ($port['macs'] as $item) {
				if (count($item['objects'])) continue;
				$seen[] = '<span class="mac_address" qtip_ttip="'
					.Html::encode('Такой MAC-адрес в инвентаризации не найден').'">'
					.Html::encode(Techs::formatMacs($item['mac'])).'</span>';
			}
			if (count($seen)) $extra[] = '<small class="text-secondary">на порту видно:</small> '
				.implode(', ', $seen);
		}

		//сосед по LLDP/CDP - факт с коммутатора, а не догадка по адресам: именно он
		//и есть настоящая связь коммутатор-коммутатор
		if (count($port['neighbors'])) {
			$seenNeighbors = [];
			foreach ($port['neighbors'] as $neighbor) {
				$title = trim(($neighbor['remote_name'] ?? '').' '.($neighbor['remote_port'] ?? ''));
				$ttip = 'Протокол: '.($neighbor['protocol'] ?? '?')
					.($neighbor['remote_mac'] ? ', адрес '.$neighbor['remote_mac'] : '');
				//опознанный сосед - карточкой, а чем он представился - в
				//подсказке: голое имя/MAC, когда объект в инвентаризации есть,
				//прячет факт (адрес бывает записан на ОС, а не на самом АРМе)
				$object = $neighbor['device'] ?? $neighbor['os'] ?? null;
				if (is_object($object)) {
					$ttip .= ($title !== '' ? ', представился: '.$title : '')
						.(isset($neighbor['os'])
							? '; адрес записан на ОС, у которой не указано оборудование' : '');
					$seenNeighbors[] = '<span qtip_ttip="'.Html::encode($ttip).'">'
						.ModelWidget::widget(['model' => $object, 'options' => ['static_view' => true]])
						.'</span>';
					continue;
				}
				$seenNeighbors[] = '<span qtip_ttip="'.Html::encode($ttip).'">'
					.Html::encode($title ?: ($neighbor['remote_mac'] ?? '?')).'</span>';
			}
			$extra[] = '<small class="text-secondary">сосед'
				.(count($port['neighbors']) > 1 ? 'и' : '').':</small> '
				.implode(', ', $seenNeighbors);
		}

		//в занятой ячейке находки идут новой строкой под записанным, в пустой -
		//начинают её собой
		$extraLines = implode('<br>', $extra);
		return [strlen($extraLines) ? '<br>'.$extraLines : '', $buttons, $extraLines];
	}

	/**
	 * Соединение порта: записанное с наложенными на него находками.
	 *
	 * mode говорит виду, как это разложить: `port` - связь порт↔порт (в
	 * таблице у неё своя колонка с подписью порта той стороны), `tech` -
	 * устройство привязано без порта, `none` - записи нет вовсе.
	 */
	protected function connectionCell(array $port, string $body, string $extraLines): array
	{
		$link = $port['link'];
		$verdict = $port['verdict'];
		//записанное перечёркиваем только когда на порту достоверно другое
		$outdated = $verdict === 'replaced' ? ' class="text-secondary text-decoration-line-through"' : '';

		if (is_object($link) && is_object($link->linkPort)) {
			return ['mode' => 'port', 'comment' => Html::encode((string)$link->linkPort->comment),
				'body' => '<span'.$outdated.'>'.$this->chain($link->linkPort).'</span>'.$body];
		}
		if (is_object($link) && is_object($link->linkTech)) {
			return ['mode' => 'tech', 'comment' => '',
				'body' => '<span'.$outdated.'>'
					.ModelWidget::widget(['model' => $link->linkTech]).'</span>'.$body];
		}

		$html = '';
		//интерфейс, которого на корпусе нет: агрегат, VLAN-интерфейс, loopback.
		//Называть его свободным портом нельзя - воткнуть в него ничего не
		//получится, розетки не существует
		if (!($port['physical'] ?? true)) {
			$html = '<span class="text-secondary opacity-75" qtip_ttip="'
				.Html::encode('Интерфейс существует только в настройках коммутатора: '
					.'агрегат, VLAN-интерфейс, loopback - розетки для него нет')
				.'">нет на корпусе</span>';
		//ни записи, ни адресов: «свободен» сказать нельзя - кабель может быть
		//воткнут, а та сторона выключена. Линка нет - вот что известно
		} elseif ($verdict === 'free') {
			$html = '<span class="text-secondary opacity-75" qtip_ttip="'
				.Html::encode('Ничего не записано, и адресов на порту не видно. Это не значит, '
					.'что порт свободен: кабель может быть воткнут, а устройство выключено')
				.'">линка нет</span>';
		} elseif ($verdict === 'disabled') {
			$html = '<span class="text-secondary opacity-75">выключен на коммутаторе</span>';
		}
		//подпись состояния и находки - разными строками
		if (in_array($verdict, ['free', 'disabled'], true) && strlen($extraLines)) $html .= '<br>';
		return ['mode' => 'none', 'comment' => '', 'body' => $html.$extraLines];
	}

	/** Последняя колонка: подтверждение, кнопки предложений, «убрать», «транзит» */
	protected function actionCell(array $port, array $buttons): string
	{
		$verdict = $port['verdict'];

		if ($verdict === 'disabled') {
			return '<span class="text-secondary small" qtip_ttip="'
				.Html::encode('Порт выключен администратором на самом коммутаторе').'">выключен</span>';
		}
		if (isset(static::MARKS[$verdict]) && !count($buttons)) {
			[$icon, $hint] = static::MARKS[$verdict];
			return '<i class="'.$icon.'" qtip_ttip="'.Html::encode($hint).'"></i>';
		}
		if (count($buttons)) {
			//одно предложение на ПУСТОМ порту принимается и разом; из нескольких
			//выбирает человек, а замену записанного «принять все» не трогает -
			//это снятие плюс привязка
			return '<div class="'.(count($buttons) === 1 && $verdict === 'added' ? 'port-scan-accept' : '')
				.'">'.implode('', $buttons).'</div>';
		}
		if (isset(static::OFFERS[$verdict])) return $this->offerButton($port, static::OFFERS[$verdict]);
		if ($verdict !== 'transit') return '';

		//транзит обязан объяснить себя: откуда вывод и что с ним делать. Два
		//источника: записанная связь с другим коммутатором либо просто много адресов
		$why = $port['uplink']
			? 'Порт связан с коммутатором '.$port['uplink_peer'].': адреса за ним '
				.'('.(int)$port['count'].') принадлежат устройствам той стороны, '
				.'сравнивать их с этим портом бессмысленно'
			: 'На порту '.(int)$port['count'].' адресов - столько у одного '
				.'устройства не бывает, за портом сеть (коммутатор, хаб, точка '
				.'доступа). Порог: '.$this->transitFrom.'. Кто там стоит, '
				.'коммутатор не сказала (сосед по LLDP не найден): привяжите '
				.'коммутатор руками через форму порта. Адреса - в блоке '
				.'"показать данные с коммутатора"';
		return '<span class="badge bg-secondary" qtip_ttip="'.Html::encode($why).'">транзит</span>';
	}

	/** Кнопка «убрать»: нагрузка в data-атрибутах, поведение - в общем скрипте */
	protected function offerButton(array $port, array $offer): string
	{
		[$icon, $hint, $do, $confirm] = $offer;
		return Html::button('<i class="'.$icon.'"></i>', [
			'class' => 'btn btn-sm btn-link p-0',
			'qtip_ttip' => $hint,
			'data-scan' => json_encode(['tech' => $this->model->id, 'port' => $port['port'], 'do' => $do]),
			'data-confirm' => $confirm,
			'onclick' => 'portsScanApply(this)',
		]);
	}

	/**
	 * Узел на той стороне записанной связи: «Порт X: Устройство», а у порта
	 * без имени (устройство привязано без порта) - просто «Устройство».
	 * Один рендер на все хопы: первый и последующие выглядят одинаково.
	 */
	protected function hop(Ports $peer): string
	{
		if (strlen((string)$peer->name)) {
			return $this->view()->render('@app/views/ports/item',
				['model' => $peer, 'include_tech' => true, 'reverse' => true]);
		}
		return is_object($peer->tech)
			? ModelWidget::widget(['model' => $peer->tech, 'options' => ['static_view' => true]]) : '';
	}

	/**
	 * Записанная связь целиком: первый хоп, а если на той стороне устройство
	 * ровно с двумя объявленными портами и второй его порт тоже связан - следующий
	 * хоп тем же рендером, через « : Порт Y → ». Так телефон с ПК за ним читается
	 * одной строкой, как и предложение опроса.
	 */
	protected function chain(Ports $peer): string
	{
		$html = $this->hop($peer);
		$seen = [$peer->id => true];
		while (true) {
			$bridge = $peer->tech;
			if (!is_object($bridge) || count($bridge->portsTemplate) !== 2) break;
			$next = null;
			foreach ($bridge->ports as $other) {
				if ($other->id == $peer->id || !is_object($other->linkPort)) continue;
				$next = $other;
				break;
			}
			//второй порт не связан либо цепочка закольцевалась
			if (is_null($next) || isset($seen[$next->linkPort->id])) break;
			$html .= ' : '.Ports::$port_prefix.Html::encode($next->name).' → '.$this->hop($next->linkPort);
			$peer = $next->linkPort;
			$seen[$peer->id] = true;
		}
		return $html;
	}

	/** Порт на той стороне: один - подставляем, несколько - селект, пусто - без порта */
	protected static function peerPick(array $peers, string $field, int $index): array
	{
		if (count($peers) === 1) {
			return [Html::encode($peers[0]['name']), [
				$field => $peers[0]['id'], $field.'_name' => $peers[0]['id'] ? '' : $peers[0]['name']]];
		}
		//портов у устройства не объявлено - оно привязывается без порта, и в
		//строке это просто устройство, без «Порт …:»
		if (!count($peers)) return ['', []];

		//в связи участвуют две стороны, и гадать, в какой разъём воткнут кабель,
		//мы не имеем права - выбирает человек
		$options = [];
		foreach ($peers as $peer) {
			$options[] = Html::tag('option', Html::encode($peer['name']), [
				'value' => $peer['id'] ?: '', 'data-name' => $peer['id'] ? '' : $peer['name']]);
		}
		return [Html::tag('select', implode('', $options), [
			'class' => 'form-select form-select-sm d-inline-block w-auto py-0 port-scan-peer',
			'data-field' => $field, 'data-proposal' => $index, 'qtip_ttip' => 'Порт на той стороне',
		]), []];
	}

	/**
	 * Предложение привязки: строка "? что куда" для ячейки соединения и кнопка
	 * для последней колонки. Цепочка - два звена: порт → мост, мост → лист.
	 */
	protected function proposalLine(array $port, array $proposal, int $index): array
	{
		$device = $proposal['device'];
		$data = ['tech' => $this->model->id, 'port' => $port['port'], 'do' => 'attach',
			'device' => $device->id, 'proposal' => $index];

		//у моста в цепочке оба порта идут селектом-переключателем даже при
		//"очевидном" выборе: какой из двух смотрит в коммутатор, знает человек
		$peers = $proposal['peers'];
		if (is_array($proposal['chain'] ?? null) && count($peers) === 2) {
			$options = [];
			foreach ($peers as $peer) {
				$options[] = Html::tag('option', Html::encode($peer['name']), [
					'value' => $peer['id'] ?: '', 'data-name' => $peer['id'] ? '' : $peer['name']]);
			}
			$peerHtml = Html::tag('select', implode('', $options), [
				'class' => 'form-select form-select-sm d-inline-block w-auto py-0 port-scan-peer',
				'data-field' => 'peer', 'data-proposal' => $index, 'data-toggle-via' => 1,
				'qtip_ttip' => 'Порт моста к коммутатору; второй уйдёт к устройству за ним',
			]);
			$fields = [];
		} else {
			[$peerHtml, $fields] = static::peerPick($peers, 'peer', $index);
		}
		$data += $fields;
		$line = (strlen($peerHtml) ? Ports::$port_prefix.$peerHtml.': ' : '')
			.ModelWidget::widget(['model' => $device, 'options' => ['static_view' => true]]);
		$title = 'Привязать '.$device->name;

		if (is_array($proposal['chain'] ?? null)) {
			$chain = $proposal['chain'];
			$data['do'] = 'chain';
			$data['via'] = $chain['via']['id'];
			$data['via_name'] = $chain['via']['id'] ? '' : $chain['via']['name'];
			$data['leaf'] = $chain['leaf']->id;
			[$leafHtml, $leafFields] = static::peerPick($chain['leaf_peers'], 'leaf_peer', $index);
			$data += $leafFields;
			$line .= ' : '.Ports::$port_prefix.'<span class="port-scan-via" data-proposal="'.$index.'">'
				.Html::encode($chain['via']['name']).'</span> → '
				.(strlen($leafHtml) ? Ports::$port_prefix.$leafHtml.': ' : '')
				.ModelWidget::widget(['model' => $chain['leaf'], 'options' => ['static_view' => true]]);
			$title = 'Привязать '.$device->name.', а за ним - '.$chain['leaf']->name;
		}

		//цепочка - это «связать»: плюс обещал бы добавление, а тут записанное
		//может остаться на месте, меняется схема соединения
		$icon = is_array($proposal['chain'] ?? null) ? 'fas fa-link'
			: ($port['verdict'] === 'replaced' ? 'fas fa-redo' : 'fas fa-plus');
		$button = Html::button('<i class="'.$icon.' text-success"></i>', [
			'class' => 'btn btn-sm btn-link p-0 d-block',
			'qtip_ttip' => $title.($port['verdict'] === 'replaced' ? ' вместо записанного' : ''),
			'data-scan' => json_encode($data),
			'data-proposal' => $index,
			'onclick' => 'portsScanApply(this)',
		]);
		return ['<span class="text-success" qtip_ttip="'.Html::encode($title).'">?</span> '.$line, $button];
	}

	/** Вид, которым рендерятся вложенные куски (карточка порта, узел связи) */
	protected function view()
	{
		return Yii::$app->view;
	}
}
