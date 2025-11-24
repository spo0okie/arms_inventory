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
		$cellOptions = $this->fetchContentOptions($model, $key, $index);
		
		//убираем все свойства, которые не относятся к нашему рендеру
		$cellOptions = array_filter($cellOptions,
			fn($key) => property_exists(ModelFieldWidget::class, $key),
			ARRAY_FILTER_USE_KEY
		);
		
		/** @var ArmsModel $model */
		return ModelFieldWidget::widget(ArrayHelper::recursiveOverride([
			'model'=>$model,
			'field'=>$this->attribute,
			'item_options'=>[
				'static_view'=>true,
			],
			'card_options'=>[
				'cardClass' => 'p-1 text-wrap '.($cellOptions['class']??''),
				'outerTag' => 'td',
			],
			'show_empty'=>true,
			'title'=>false
		],$cellOptions));
		
	}
	
}