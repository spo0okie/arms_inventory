<?php

/* @var $this yii\web\View */
/* @var $arrFooter array */
/* @var $searchModel app\models\MaterialsUsagesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer = $this;

//собираем суммы по валютам
$totals=[];
$charge=[];
foreach ($dataProvider->models as $model) {
	/**
	 * @var \app\models\MaterialsUsages $model
	 */
	
	if ($model->cost) {
		if (!isset($totals[$model->material->currency_id])) $totals[$model->material->currency_id]=0;
		if (!isset($charge[$model->material->currency_id])) $charge[$model->material->currency_id]=0;
		
		$totals[$model->material->currency_id]+=$model->cost;
		$charge[$model->material->currency_id]+=$model->charge;
	}
	
}

$arrFooter=['total'=>[],'charge'=>[]];
foreach (\app\models\Currency::find()->all() as $currency) {
	/**
	 * @var \app\models\Currency $currency
	 */
	if (isset($totals[$currency->id]) && $totals[$currency->id]) {
		$arrFooter['total'][]=number_format($totals[$currency->id],2,'.','&nbsp;').$currency->symbol;
	}
	if (isset($charge[$currency->id]) && $charge[$currency->id]) {
		$arrFooter['charge'][]=number_format($charge[$currency->id],2,'.','&nbsp;').$currency->symbol;
	}
}

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
