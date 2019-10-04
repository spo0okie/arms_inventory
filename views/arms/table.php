<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ArmsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer = $this;
if (!isset($columns)) $columns=['attach','num','model','comp_id','comp_ip','sn','state','user_id','places_id'];

//формируем список столбцов для рендера
$render_columns=[];
foreach ($columns as $column) {

	switch ($column) {
		case 'num':
			$render_columns[] = [
				'attribute' => 'num',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/arms/item', ['model' => $data]);
				}
			];
			break;

		case 'model':
			$render_columns[] = [
				'attribute' => 'model',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return is_object($data->techModel) ? $renderer->render('/tech-models/item', ['model' => $data->techModel, 'static' => true]) : null;
				}
			];
			break;

		case 'comp_id':
			$render_columns[] = [
				'attribute' => 'comp_id',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return is_object($data->comp) ? $renderer->render('/comps/item', ['model' => $data->comp]) : null;
				}
			];
			break;

		case 'comp_ip':
			$render_columns[] = [
				'attribute' => 'comp_ip',
				'format' => 'raw',
				'header' => 'IP Адрес',
				'value' => function ($data) {
					return is_object($data->comp) ? $data->comp->filteredIpsStr : null;
				}
			];
			break;

		case 'user_id':
			$render_columns[] = [
				'attribute' => 'user_id',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return is_object($data->user) ? $renderer->render('/users/item', ['model' => $data->user]) : null;
				}
			];
			break;

		case 'places_id':
			$render_columns[] = [
				'attribute' => 'places_id',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return is_object($data->place) ? $renderer->render('/places/item', ['model' => $data->place, 'full' => 1]) : null;
				}
			];
			break;

		case 'attach':
			$render_columns[] = [
				'attribute' => 'attach',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return is_object($data->place) ? $renderer->render('/arms/item-attachments',['model'=>$data]) : '';
				}
			];
			break;

		case 'state':
			$render_columns[] = [
				'attribute' => 'state',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/tech-states/item', ['model' => $data->state]);
				}
			];
			break;

		case 'sn':
			$render_columns[] = $column;


	}
}

//try {
	echo GridView::widget([
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'columns' => $render_columns,
	]);
//} catch (Exception $e) {
	echo 'Ошибка вывода виджета таблицы<br/>';
// }

