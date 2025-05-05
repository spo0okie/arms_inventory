<?php

namespace app\components\gridColumns;

use app\components\ExpandableCardWidget;
use app\components\ItemObjectWidget;
use app\helpers\ArrayHelper;
use app\models\ArmsModel;
use Closure;
use kartik\grid\DataColumn;

class ItemColumn extends DataColumn
{
	public function init()
	{
		//по умолчанию переносим текст
		if (!isset($this->contentOptions['class'])) $this->contentOptions['class']='text-wrap';
		parent::init();
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