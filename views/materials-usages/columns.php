<?php

/* @var $this yii\web\View */
/* @var $arrFooter array */

$renderer = $this;

return [
	[
		'attribute'=>'place',
		'format'=>'raw',
		'value' => function($data) use($renderer){
			$place=$data->material->place??null;
			return is_object($place)?$place->renderItem($renderer,['full'=>true]):null;
		}
	],
	[
		'attribute'=>'material',
		'format'=>'raw',
		'value' => function($data) use($renderer){
			return $renderer->render('/materials/item',['model'=>$data->material,'from'=>false]);
		}
	],
	[
		'attribute'=>'count',
		'format'=>'raw',
		'value' => function($data) use($renderer){
			return $data->count.' '.($data->material->type->units??'type_error');
		}
	],
	[
		'attribute'=>'cost',
		'format'=>'raw',
		'contentOptions' => ['class' => 'text-right'],
		'footerOptions' => ['class' => 'text-right'],
		'value'=>function($data) use ($renderer) {
			if ($data->cost) {
				return number_format($data->cost,2,'.','&nbsp;').$data->currency->symbol;
			} return '';
		},
		'footer'=>implode('<br />',$arrFooter['total']),
	],
	[
		'attribute'=>'charge',
		'format'=>'raw',
		'contentOptions' => ['class' => 'text-right'],
		'footerOptions' => ['class' => 'text-right'],
		'value'=>function($data) use ($renderer) {
			if ($data->charge) {
				return number_format($data->charge,2,'.','&nbsp;').$data->currency->symbol;
			} return '';
		},
		'footer'=>implode('<br />',$arrFooter['charge']),
	],
	[
		'attribute'=>'to',
		'format'=>'raw',
		'value' => function($data) use($renderer){
			return $renderer->render('/materials-usages/item',['model'=>$data,'to'=>true,'date'=>false]);
		}
	],
	'date',
];
