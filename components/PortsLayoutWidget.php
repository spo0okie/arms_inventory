<?php

namespace app\components;

use app\helpers\PortsHelper;
use app\models\Techs;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Раскладка портов: то же, что в таблице, но напротив своей розетки.
 *
 * Таблица отвечает на вопрос «что на порту Gi1/0/13», раскладка - на вопрос
 * «что в этой розетке»: инженер стоит перед корпусом и читает панель слева
 * направо, а не ищет строку в списке из 48. Данные те же самые
 * ({@see PortsRowRenderer}), меняется только их расстановка: строка таблицы
 * встаёт колонкой над своим портом (верхний ряд) или под ним (нижний).
 *
 * Колонки повёрнуты так, чтобы читаться при наклоне головы к своей половине
 * корпуса: у верхнего ряда текст идёт снизу вверх (начало - у розетки), у
 * нижнего сверху вниз (начало тоже у розетки). Ширина колонки фиксирована и
 * равна ширине слота - иначе подписи разъехались бы и перестали указывать на
 * свой порт; поэтому же слоты и подписи лежат в одной сетке, а не в двух
 * выровненных «на глаз».
 *
 * Рисуется только по стандартному корпусу - блоки в один или два ряда
 * ({@see available()}). Трёхрядных панелей не бывает у коммутаторов, а
 * гадать, какой ряд «верхний», а какой «нижний», не из чего.
 */
class PortsLayoutWidget extends Widget
{
	/**
	 * Как выглядит розетка в каждом состоянии: класс рамки и подпись состояния.
	 * Занятый порт закрашен, свободный пуст, спорный обведён - раскраска
	 * повторяет пометки таблицы, чтобы не заводить второй язык.
	 */
	const STATES = [
		'ok' => ['slot-ok', 'подключено то, что записано'],
		//без опроса слот не красим: пустая заливка значит «не знаем», а не «занят»
		'unknown' => ['', 'опроса не было'],
		'seen' => ['slot-seen', 'на порту есть адреса, объект не опознан'],
		'foreign' => ['slot-foreign', 'записанное не отозвалось'],
		'quiet' => ['slot-quiet', 'адресов не видно'],
		'replaced' => ['slot-replaced', 'на порту другое оборудование'],
		'added' => ['slot-added', 'обнаружено оборудование'],
		'transit' => ['slot-transit', 'за портом сеть'],
		'disabled' => ['slot-disabled', 'выключен на коммутаторе'],
		'self' => ['slot-self', 'за портом виден сам коммутатор'],
		'free' => ['', 'линка нет'],
	];

	/** @var Techs|null устройство, чью панель рисуем */
	public ?Techs $model = null;

	/** @var array строки о портах {@see \app\components\integrations\providers\MacSearchProvider::switchPorts()} */
	public array $rows = [];

	/** @var PortsRowRenderer|null рендерер содержимого (свой, если не задан) */
	public ?PortsRowRenderer $renderer = null;

	/** @var bool был ли опрос: без него вердикты не выносятся */
	public bool $scanned = false;

	/** @var int с какого числа адресов порт считается транзитным */
	public int $transitFrom = 4;

	/** @var string|null id контейнера (по умолчанию - от id устройства) */
	public ?string $containerId = null;

	/** @var int[] сколько строк вышло в каждой подписи (по ним - ширина колонки) */
	protected array $lines = [];

	/** Потолок: подпись длиннее просто не поместится в колонку, останется в подсказке */
	const MAX_LINES = 6;

	/**
	 * Ширина колонки в em - по САМИМ данным: колонка должна вместить типовую
	 * подпись целиком, иначе половина их обрезана. Считаем не по самой длинной,
	 * а по девятому дециля: один порт с цепочкой из трёх устройств иначе
	 * растянул бы весь корпус, и розетки разъехались бы на пол-экрана.
	 * Строка подписи - примерно 0.95em, плюс поля; уже номера порта не бывает.
	 */
	protected function columnWidth(): float
	{
		if (!count($this->lines)) return 2.6;

		$lines = $this->lines;
		sort($lines);
		$typical = $lines[(int)floor((count($lines) - 1) * 0.9)];
		return max(2.6, round(min($typical, static::MAX_LINES) * 0.95 + 0.5, 2));
	}

	/**
	 * Годится ли устройство для раскладки: описан корпус, портов достаточно,
	 * и все его блоки - в один-два ряда.
	 */
	public static function available(?Techs $model): bool
	{
		if (!is_object($model) || !is_object($model->model)) return false;

		$layout = $model->model->portsLayout;
		if (!count($layout)) return false;
		foreach ($layout as $block) if ((int)$block['rows'] > 2) return false;

		return count($model->portsTemplate) >= (int)(Yii::$app->params['ports.mapMinPorts'] ?? 4);
	}

	public function run()
	{
		if (!static::available($this->model)) return '';

		$layout = $this->model->model->portsLayout;
		$blocks = PortsHelper::layoutSlots($layout, array_keys($this->model->portsTemplate));
		if (!count($blocks)) return '';

		$renderer = $this->renderer
			?: new PortsRowRenderer($this->model, $this->rows, $this->scanned, $this->transitFrom);

		//строка о порте по его имени: раскладка и таблица говорят об одном и том же
		$byName = [];
		foreach ($this->rows as $port) $byName[(string)$port['port']] = $port;

		$this->lines = [];
		$html = '';
		foreach ($blocks as $block) $html .= $this->renderBlock($block, $byName, $renderer);

		$id = $this->containerId ?: 'techs-ports-layout-'.$this->model->id;
		//корпус - одна горизонтальная лента: не влезло по ширине - прокручивается,
		//а не ломается на этажи, иначе подписи перестанут указывать на свой порт
		return Html::tag('div', $html, ['class' => 'ports-layout', 'id' => $id,
			//ширину колонки диктуют САМИ данные: строки ячейки ложатся рядом, и
			//колонка обязана вместить самую многострочную из них. Иначе либо
			//половина подписей обрезана (узкая колонка), либо корпус растянут
			//впустую (широкая)
			'style' => '--ports-layout-slot:'.$this->columnWidth().'em']);
	}

	/**
	 * Один блок корпуса: пояснения верхнего ряда, ряды розеток, пояснения
	 * нижнего ряда и подпись.
	 *
	 * Строки у ВСЕХ блоков общие (`grid-template-rows: subgrid` от ленты):
	 * корпуса стоят рядом, и ряд розеток одного блока обязан быть на той же
	 * высоте, что у соседнего - иначе панель читается как две разные железки.
	 * Поэтому строка каждой части задана явно: у однорядного блока (SFP)
	 * розетки идут в ту же строку, что верхний ряд двухрядного, а не съезжают
	 * вверх. Колонка одна на слот и его пояснения - развести их нечем.
	 */
	protected function renderBlock(array $block, array $byName, PortsRowRenderer $renderer): string
	{
		$grid = $block['grid'];
		$columns = count($grid[0] ?? []);
		if (!$columns) return '';

		$cells = '';
		//пояснения верхнего ряда - над ним, читаются снизу вверх
		$cells .= $this->renderCells($grid[0] ?? [], $byName, $renderer, 'up');
		foreach ($grid as $index => $row) $cells .= $this->renderSlots($row, $byName, (int)$index);
		//у двухрядного корпуса нижний ряд объясняется снизу, у однорядного
		//хватает верхних пояснений: вторая копия тех же данных - шум
		if (count($grid) > 1) {
			$cells .= $this->renderCells($grid[1], $byName, $renderer, 'down');
		}
		if (strlen($block['title'])) {
			$cells .= Html::tag('div', Html::encode($block['title']),
				['class' => 'ports-layout-title text-secondary small text-center']);
		}

		return Html::tag('div', $cells, ['class' => 'ports-layout-block',
			'style' => '--ports-layout-columns:'.$columns]);
	}

	/** Ряд розеток: те же слоты, что и на карте портов, но во всю ширину колонки */
	protected function renderSlots(array $row, array $byName, int $index = 0): string
	{
		//номер ряда - в классе: строку в общей сетке задаёт стиль, а не порядок
		//вывода (у однорядных блоков строк меньше, чем у двухрядных)
		$rowClass = ' ports-layout-row-'.($index + 1);

		$html = '';
		foreach ($row as $name) {
			if (is_null($name)) {
				$html .= Html::tag('span', '&nbsp;', ['class' => 'ports-layout-slot'.$rowClass]);
				continue;
			}
			$port = $byName[(string)$name] ?? null;
			$verdict = $port['verdict'] ?? 'unknown';
			[$class] = static::STATES[$verdict] ?? static::STATES['unknown'];
			//без подсказки: всё, что она говорила, стоит рядом с розеткой в её
			//же колонке - а всплывающий блок закрывал соседние подписи
			$html .= Html::tag('span', Html::encode(PortsHelper::slotLabel((string)$name)), [
				'class' => 'ports-layout-slot'.$rowClass
					.' ports-map-slot border rounded text-center small '.$class,
				'data-port' => $name,
			]);
		}
		return $html;
	}

	/**
	 * Ряд пояснений: строка таблицы, поставленная на попа над своим портом
	 * (`up`) или под ним (`down`).
	 */
	protected function renderCells(array $row, array $byName, PortsRowRenderer $renderer, string $side): string
	{
		$html = '';
		foreach ($row as $name) {
			$port = is_null($name) ? null : ($byName[(string)$name] ?? null);
			if (is_null($port)) {
				//порта нет на корпусе либо о нём нет строки: пустая колонка,
				//чтобы соседи не сдвинулись
				$html .= Html::tag('div', '', ['class' => 'ports-layout-cell '.$side]);
				continue;
			}

			$parts = $renderer->parts($port);
			$connection = $parts['connection'];
			//порядок - как колонки таблицы, слева направо: это те же самые
			//ячейки, только перенесённые к своей розетке и поставленные на попа
			$pieces = array_filter([
				$parts['port'],
				$parts['aggregate'],
				$parts['comment'],
				$connection['comment'],
				$connection['body'],
				$parts['action'],
			], fn($piece) => strlen(trim((string)$piece)));

			//каждая ячейка таблицы - отдельный кусок колонки: идут они друг за
			//другом ВДОЛЬ колонки, а свою вторую строку (VLAN под именем порта,
			//описание под пояснением, находки под связью) разворачивают ВБОК.
			//Ровно как в таблице, только повёрнуто: ширина колонки при этом
			//равна двум строкам, а не сумме всех
			$lines = 1;
			foreach ($pieces as $index => $piece) {
				$lines = max($lines, preg_match_all('~<br\s*/?>~i', $piece) + 1);
				//«ёлочка» вторую строку схлопывает: довёрнутая, она легла бы
				//поверх подписи соседнего порта. Разделитель для неё выводится
				//сразу, показывает его стиль
				$pieces[$index] = Html::tag('span', preg_replace('~<br\s*/?>~i',
					'<br><span class="ports-layout-joint"> · </span>', $piece),
					['class' => 'ports-layout-part']);
			}
			$this->lines[] = $lines;
			//между кусками - отступ (стилем), как промежуток между колонками
			//таблицы: точка тут лишняя, куски и так читаются по отдельности
			$content = implode('', $pieces);
			//класса port-scan-accept тут нет намеренно: «принять однозначные
			//разом» собирает кнопки по таблице, и вторая их копия в раскладке
			//применила бы каждую находку дважды
			//текст отдельным узлом: ячейка центрирует его по ширине колонки
			//(подпись обязана стоять ровно над своей розеткой), а он сам
			//отвечает за обрезку слишком длинного. Подсказки нет намеренно:
			//всё, что она показала бы, и так написано рядом с розеткой, а
			//всплывающий блок закрывал соседние колонки
			$html .= Html::tag('div', Html::tag('span', $content, ['class' => 'ports-layout-text']), [
				'class' => 'ports-layout-cell '.$side,
				'data-port' => $name,
			]);
		}
		return $html;
	}
}
