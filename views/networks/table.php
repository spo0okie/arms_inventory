<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\NetworksSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer=$this;



//формируем список столцов, если
if (!isset($columns)) $columns=['name','segment','comment','vlan','domain','usage'];

//формируем список столбцов для рендера
$render_columns=[];
foreach ($columns as $column) {
	
	switch ($column) {
		case 'name':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('item', ['model' => $data]);
				}
			];
			break;
		case 'segment':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/segments/item', ['model' => $data->segment]);
				}
			];
			break;
		case 'vlan':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/net-vlans/item', ['model' => $data->netVlan]);
				},
				'contentOptions' => [
					'class' => 'text-right'
				]
			];
			break;
		case 'domain':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					if (is_object($data->netVlan) && is_object($data->netVlan->netDomain))
						return $renderer->render('/net-domains/item', ['model' => $data->netVlan->netDomain]);
					return null;
				}
			];
			break;
		case 'usage':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('used', ['model' => $data]);
				},
				'contentOptions' => ['class' => 'usage_col']
			
			];
			break;
		case 'comment':
			$render_columns[] = "$column:ntext";
	}
}

echo GridView::widget([
	'dataProvider' => $dataProvider,
	'filterModel' => $searchModel,
	'formatter' => ['class' => 'yii\i18n\Formatter','nullDisplay' => ''],
	'columns' => $render_columns
]);