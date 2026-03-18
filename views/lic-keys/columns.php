<?php

/* @var $this yii\web\View */

use app\components\ExpandableCardWidget;

use app\components\widgets\page\ModelWidget;
$renderer=$this;
return [
	[
		'attribute' => 'lic_item',
		'format' => 'raw',
		'value' => function ($data) use ($renderer) {
			return  ModelWidget::widget(['model'=>$data->licItem]);
		}
	],
	[
		'attribute' => 'key_text',
		'format' => 'raw',
		'value' => function ($data) use ($renderer) {
			return  ModelWidget::widget(['model'=>$data]);
		}
	],
	[
		'attribute' => 'links',
		'format' => 'raw',
		'value' => function ($item) use ($renderer) {
			$output = '';
			foreach ($item->arms as $arm)
				$output .= ' ' . ModelWidget::widget(['model'=>$arm,'options'=>['icon'=>true,'static_view'=>true]]);
			foreach ($item->comps as $comp)
				$output .= ' ' . ModelWidget::widget(['model'=>$comp,'options'=>['icon'=>true,'static_view'=>true]]);
			foreach ($item->users as $user)
				$output .= ' ' . ModelWidget::widget(['model'=>$user,'options'=>['icon'=>true,'static_view'=>true]]);
			return $output;
		}
	],
	'comment',
];


