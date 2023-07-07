<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\NetworksSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer=$this;



//формируем список столцов, если
if (!isset($columns)) $columns=['name','segment','comment','vlan','domain','usage'];

//формируем список столбцов для рендера
$render_columns=[
	'name'=> [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('item', ['model' => $data]);
		}
	],
	'segment'=> [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/segments/item', ['model' => $data->segment]);
		}
	],
	 'vlan' => [
		'value' => function ($data) use ($renderer) {
			return $renderer->render('/net-vlans/item', ['model' => $data->netVlan]);
		},
		'contentOptions' => [
			'class' => 'text-right'
		]
	],
	'domain'=>[
		'value' => function ($data) use ($renderer) {
			if (is_object($data->netVlan) && is_object($data->netVlan->netDomain))
				return $renderer->render('/net-domains/item', ['model' => $data->netVlan->netDomain]);
			return null;
		}
	],
	'usage'=>[
		'value' => function ($data) use ($renderer) {
			return $renderer->render('used', ['model' => $data]);
		},
		'contentOptions' => ['class' => 'usage_col']
			
	],
	'comment'
];

echo \app\components\DynaGridWidget::widget([
	'id'=>'networks-index',
	'panel'=>false,
	'dataProvider' => $dataProvider,
	'filterModel' => $searchModel,
	//'formatter' => ['class' => 'yii\i18n\Formatter','nullDisplay' => ''],
	'columns' => $render_columns,
	'defaultOrder'=>$columns,
]);