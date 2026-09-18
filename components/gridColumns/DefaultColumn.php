<?php

namespace app\components\gridColumns;

use app\components\ExpandableCardWidget;
use app\components\ModelFieldWidget;
use app\helpers\ArrayHelper;
use app\models\base\ArmsModel;
use Closure;
use kartik\grid\DataColumn;

class DefaultColumn extends DataColumn
{
	
	public function renderDataCell($model, $key, $index)
	{
		$cellOptions = $this->fetchContentOptions($model, $key, $index);

		//CSS-класс ячейки забираем до фильтрации: 'class' - не свойство виджета
		//и отсеется, а он несет подсветку ячейки (table-warning в журнале истории)
		$cellClass = $cellOptions['class'] ?? '';

		//убираем все свойства, которые не относятся к нашему рендеру
		$cellOptions = array_filter($cellOptions,
			fn($key) => property_exists(ModelFieldWidget::class, $key),
			ARRAY_FILTER_USE_KEY
		);

		$options=ArrayHelper::recursiveOverride([
			'model'=>$model,
			'field'=>$this->attribute,
			'item_options'=>[
				'static_view'=>true,
			],
			'card_options'=>[
				'cardClass' => 'p-1 text-wrap '.$cellClass,
				'outerTag' => 'td',
			],
			'show_empty'=>true,
			'title'=>false
		],$cellOptions);

		//класс колонки <attr>_col обязан дойти до ячейки всегда: когда contentOptions —
		//замыкание (или переопределяет cardClass), дефолт из DynaGridWidget::defaultColumn
		//теряется, а по нему колонки адресуются из CSS (AccessLevelSwitchWidget)
		$columnClass=str_replace('-','_',(string)$this->attribute).'_col';
		$cardClass=(string)($options['card_options']['cardClass']??'');
		if (!preg_match('/(^|\s)'.preg_quote($columnClass,'/').'(\s|$)/',$cardClass))
			$options['card_options']['cardClass']=trim($cardClass.' '.$columnClass);

		/** @var \app\models\base\ArmsModel $model */
		return ModelFieldWidget::widget($options);

	}
	
}