<?php

namespace app\components\gridColumns;

use app\components\ExpandableCardWidget;
use app\helpers\ArrayHelper;
use Closure;
use kartik\grid\DataColumn;

class ExpandableCardColumn extends DataColumn
{
	
	public function renderDataCell($model, $key, $index)
	{
		if ($this->contentOptions instanceof Closure) {
			$options = call_user_func($this->contentOptions, $model, $key, $index, $this);
		} else {
			$options = $this->contentOptions;
		}
		
		$options=ArrayHelper::merge([
			'cardClass' => 'p-1 text-wrap',
			'outerTag' => 'td',
		],$options);
		$options['content']=$this->renderDataCellContent($model, $key, $index);
		
		return ExpandableCardWidget::widget($options);
	}
	
}