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
		//в повёрнутой колонке шириной в один порт вторая строка ушла бы вбок:
		//внутри ячейки всё идёт одной строкой через разделитель
		$renderer->break = ' · ';

		//строка о порте по его имени: раскладка и таблица говорят об одном и том же
		$byName = [];
		foreach ($this->rows as $port) $byName[(string)$port['port']] = $port;

		$html = '';
		foreach ($blocks as $block) $html .= $this->renderBlock($block, $byName, $renderer);

		$id = $this->containerId ?: 'techs-ports-layout-'.$this->model->id;
		//корпус - одна горизонтальная лента: не влезло по ширине - прокручивается,
		//а не ломается на этажи, иначе подписи перестанут указывать на свой порт
		return Html::tag('div', $html, ['class' => 'ports-layout', 'id' => $id]);
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
			[$class, $state] = PortsMapWidget::STATES[$verdict] ?? PortsMapWidget::STATES['unknown'];
			$html .= Html::tag('span', Html::encode(PortsHelper::slotLabel((string)$name)), [
				'class' => 'ports-layout-slot'.$rowClass
					.' ports-map-slot border rounded text-center small '.$class,
				'qtip_ttip' => $name.': '.$state,
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
			//порядок не такой, как в таблице: пометка и кнопка идут сразу за
			//именем порта, у самой розетки. В колонке длинное соединение
			//уезжает от корпуса вдаль и может не поместиться на экран, а
			//действие должно быть под рукой всегда
			$pieces = array_filter([
				$parts['port'],
				$parts['action'],
				$parts['aggregate'],
				$parts['comment'],
				$connection['comment'],
				$connection['body'],
			], fn($piece) => strlen(trim((string)$piece)));

			$content = implode(' · ', $pieces);
			//подсказка - тот же текст без разметки: пометки-иконки в ней
			//превратились бы в пустоту между разделителями
			$plain = [];
			foreach ($pieces as $piece) {
				$text = trim(preg_replace('~\s+~u', ' ',
					html_entity_decode(strip_tags($piece), ENT_QUOTES, 'UTF-8')));
				if (strlen($text)) $plain[] = $text;
			}
			//класса port-scan-accept тут нет намеренно: «принять однозначные
			//разом» собирает кнопки по таблице, и вторая их копия в раскладке
			//применила бы каждую находку дважды
			//текст отдельным узлом: ячейка центрирует его по ширине колонки
			//(подпись обязана стоять ровно над своей розеткой), а он сам
			//отвечает за обрезку слишком длинного
			$html .= Html::tag('div', Html::tag('span', $content, ['class' => 'ports-layout-text']), [
				'class' => 'ports-layout-cell '.$side,
				'data-port' => $name,
				//колонка ограничена по длине - что не поместилось, читается
				//в подсказке (и целиком - в таблице)
				'qtip_ttip' => implode(' · ', $plain),
			]);
		}
		return $html;
	}
}
