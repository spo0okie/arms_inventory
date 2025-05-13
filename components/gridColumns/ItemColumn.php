<?php

namespace app\components\gridColumns;

use app\components\ExpandableCardWidget;
use app\components\ItemObjectWidget;
use app\helpers\ArrayHelper;
use app\models\ArmsModel;
use Closure;
use kartik\grid\DataColumn;
use yii\helpers\Html;

class ItemColumn extends DataColumn
{
	
	public function renderDataCell($model, $key, $index)
	{
		if ($this->contentOptions instanceof Closure) {
			$options = call_user_func($this->contentOptions, $model, $key, $index, $this);
		} else {
			$options = $this->contentOptions;
		}
		
		$options=ArrayHelper::recursiveOverride(['class'=>'text-wrap'], $options);
		
		return Html::tag('td', $this->renderDataCellContent($model, $key, $index), $options);
	}
	
	public function renderDataCellContent($model, $key, $index)
	{
		if ($this->options instanceof Closure) {
			$options = call_user_func($this->options, $model, $key, $index, $this);
		} else {
			$options = $this->options;
		}
		
		$options['model']=$model;
		/** @var ArmsModel $model */
		return $model->renderItem($this->grid->view, $options);
	}
	
}