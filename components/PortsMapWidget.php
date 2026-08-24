<?php

namespace app\components;

use app\helpers\PortsHelper;
use app\models\Techs;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Карта портов устройства: передняя панель в том виде, в каком она есть.
 *
 * Отвечает на вопрос, на который таблица не отвечает: «где физически порт
 * Gi1/0/13 — в тринадцатой розетке или в четырнадцатой?». Геометрия берётся
 * у модели оборудования ({@see \app\models\TechModels::$ports_layout}: корпус
 * один на все экземпляры), имена — у экземпляра (после стекирования они
 * меняются, а корпус нет).
 *
 * Состояния портов приходят из опроса ({@see MacSearchProvider::switchPorts()}):
 * занят, линка нет, транзит, выключен, расхождение. Без опроса карта тоже
 * рисуется — просто без раскраски: даже пустая, она показывает раскладку.
 */
class PortsMapWidget extends Widget
{
	/** @var Techs|null устройство, чью панель рисуем */
	public ?Techs $model = null;

	/** @var array порты с состояниями {@see MacSearchProvider::switchPorts()} */
	public array $ports = [];

	/** @var string|null id таблицы портов - клик по слоту подсвечивает строку */
	public ?string $tableId = null;

	/**
	 * Как выглядит слот в каждом состоянии: класс рамки, подпись для подсказки.
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

	public function run()
	{
		if (!is_object($this->model)) return '';

		$layout = is_object($this->model->model) ? $this->model->model->portsLayout : [];
		if (!count($layout)) return '';    //корпус не описан - рисовать нечего

		//имена в объявленном порядке: у экземпляра свои, если он их объявил
		$names = array_keys($this->model->portsTemplate);
		//у устройства с одним-двумя портами карта ничего не добавляет к списку
		if (count($names) < (int)(Yii::$app->params['ports.mapMinPorts'] ?? 4)) return '';
		$blocks = PortsHelper::layoutSlots($layout, $names);
		if (!count($blocks)) return '';

		//состояние порта по имени: карта и таблица говорят об одном и том же
		$states = [];
		foreach ($this->ports as $port) $states[(string)$port['port']] = $port;

		$html = '';
		foreach ($blocks as $block) {
			$html .= $this->renderBlock($block, $states);
		}

		//без переноса блоков: корпус - одна горизонтальная лента, длинная
		//прокручивается (overflow в arms.css), а не ломается на этажи
		return '<div class="ports-map d-flex flex-row flex-nowrap align-items-end gap-3 mb-2">'
			.$html.'</div>';
	}

	/** Один блок корпуса: подпись и сетка слотов */
	protected function renderBlock(array $block, array $states): string
	{
		$rows = '';
		foreach ($block['grid'] as $row) {
			$slots = '';
			foreach ($row as $name) {
				$slots .= is_null($name) ? $this->emptySlot() : $this->slot((string)$name, $states);
			}
			$rows .= '<div class="d-flex flex-row gap-1">'.$slots.'</div>';
		}

		return '<div class="d-flex flex-column gap-1">'
			.$rows
			.(strlen($block['title'])
				? '<div class="text-secondary small text-center">'.Html::encode($block['title']).'</div>'
				: '')
			.'</div>';
	}

	/** Слот порта: номер, раскраска состояния и подсказка «что за ним» */
	protected function slot(string $name, array $states): string
	{
		$port = $states[$name] ?? null;
		$verdict = $port['verdict'] ?? 'unknown';
		[$class, $state] = static::STATES[$verdict] ?? static::STATES['unknown'];

		//в слоте помещается только номер, поэтому имя целиком - в подсказке
		$label = PortsHelper::slotLabel($name);
		$hint = $name.': '.$state;
		if (is_object($port['linked'] ?? null)) $hint .= ' — '.$port['linked']->name;
		elseif (count($port['found'] ?? [])) $hint .= ' — '.$port['found'][0]->name;
		if (strlen($port['description'] ?? '')) $hint .= ' «'.$port['description'].'»';

		return Html::tag('span', Html::encode($label), [
			'class' => 'ports-map-slot border rounded text-center small '.$class,
			'style' => 'width:2.2em;line-height:1.6em;cursor:default',
			'qtip_ttip' => $hint,
			'data-port' => $name,
		]);
	}

	/** Пустое место в сетке: на корпусе там ничего нет */
	protected function emptySlot(): string
	{
		return '<span class="ports-map-slot" style="width:2.2em;line-height:1.6em">&nbsp;</span>';
	}
}
