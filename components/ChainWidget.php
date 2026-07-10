<?php

namespace app\components;

use app\helpers\ArrayHelper;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Единый рендер «цепочки положения» — последовательности узлов через
 * разделитель (Организация / подразделение / дочернее подразделение / должность).
 *
 * Собирает цепочку из НЕСКОЛЬКИХ сегментов (возможно из разных атрибутов и
 * разных моделей), поэтому это виджет-композиция, а не тип одного атрибута:
 * визуально единая цепочка часто склеена из разнородных атрибутов (в карточке
 * пользователя — `org` + разворот `orgStruct` + `Doljnost`).
 *
 * Каждый сегмент задаётся одной из форм:
 *  - ['model'=>M,'field'=>F]              значение атрибута + скрытая «?»
 *                                         (ModelFieldWidget::renderFieldValueHinted)
 *  - ['model'=>M,'field'=>F,'chain'=>true] разворот предков объекта M.F + «?» атрибута
 *  - ['node'=>OBJ,'chain'=>true]          разворот предков самого объекта OBJ (без «?»)
 *  - ['object'=>OBJ]                      готовый объект через renderItem (без «?»)
 *  - ['text'=>STR]                        произвольный текст (без «?»)
 * Пустые сегменты отбрасываются, поэтому «смежные узлы» (partner, должность и т.п.)
 * добавляются/убираются свободно.
 *
 * Разделитель `glue` единый и для разворота предков, и между сегментами —
 * иначе цепочка не читается как единая (дефолт ' / ', кастомизируется).
 */
class ChainWidget extends Widget
{
	/** @var array список сегментов (см. описание класса) */
	public $segments=[];
	/** @var string разделитель звеньев */
	public $glue=' / ';

	public function run()
	{
		$parts=[];
		foreach ($this->segments as $seg) {
			$rendered=$this->renderSegment($seg);
			if (strlen($rendered)) $parts[]=$rendered;
		}
		return implode($this->glue,$parts);
	}

	private function renderSegment($seg): string
	{
		//разворот цепочки предков узла
		if (!empty($seg['chain'])) {
			$node=$seg['node'] ?? (
				isset($seg['model'],$seg['field'])
					? ArrayHelper::getValue($seg['model'],$seg['field'])
					: null
			);
			return $this->renderChain($node,$seg);
		}

		//типизированный атрибут со скрытой подсказкой «?»
		if (isset($seg['model'],$seg['field']))
			return ModelFieldWidget::renderFieldValueHinted($seg['model'],$seg['field']);

		//готовый объект-ссылка
		if (isset($seg['object']) && is_object($seg['object']))
			return $seg['object']->renderItem($this->view,['static_view'=>true]);

		//произвольный текст
		if (isset($seg['text']) && strlen((string)$seg['text']))
			return Html::encode((string)$seg['text']);

		return '';
	}

	/**
	 * Разворот предков узла (root → leaf), каждый через renderItem, склейка glue.
	 * Предки берутся из getChain() узла (если есть), иначе обходом parent.
	 * Для field-сегмента добавляется одна скрытая «?» на весь сегмент-атрибут.
	 */
	private function renderChain($node,$seg): string
	{
		if (!is_object($node)) return '';

		$nodes=$node->hasMethod('getChain') ? $node->chain : $this->walkParents($node);
		$tokens=[];
		foreach ($nodes as $n)
			if (is_object($n)) $tokens[]=$n->renderItem($this->view,['static_view'=>true]);
		$html=implode($this->glue,$tokens);
		if ($html==='') return '';

		if (isset($seg['model'],$seg['field'])) {
			$icon=AttributeTooltip::icon(
				AttributeTooltip::build($seg['model'],$seg['field'],AttributeTooltip::MODE_VIEW),
				true
			);
			if ($icon!=='') $html.=' '.$icon;
		}
		return $html;
	}

	/** Фолбэк-обход предков через связь parent (для деревьев без getChain). */
	private function walkParents($node): array
	{
		$nodes=[];
		$n=$node;
		while (is_object($n)) {
			$nodes[]=$n;
			$n=$n->hasMethod('getParent') ? ($n->parent ?? null) : null;
		}
		return array_reverse($nodes);
	}
}
