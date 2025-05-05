<?php

namespace app\components\gridColumns;

use app\components\ExpandableCardWidget;
use app\components\ModelFieldWidget;
use app\helpers\ArrayHelper;
use app\models\ArmsModel;
use Closure;
use kartik\grid\DataColumn;

class DefaultColumn extends DataColumn
{
	
	public function renderDataCell($model, $key, $index)
	{
		if ($this->contentOptions instanceof Closure) {
			$options = call_user_func($this->contentOptions, $model, $key, $index, $this);
		} else {
			$options = $this->contentOptions;
		}
		/** @var ArmsModel $model */
		$options=ArrayHelper::merge([
			'model'=>$model,
			'field'=>$this->attribute,
			'card_options'=>[
				'cardClass' => 'p-1 text-wrap',
				'outerTag' => 'td',
			],
			'show_empty'=>true,
			'title'=>false
		],$options);
		
		return ModelFieldWidget::widget($options);
	}
	
}