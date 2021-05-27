<?php

use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\TechsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$renderer = $this;

/**
 * если я когда то забуду что я тут нагородил:
 * передаем список с именами столбцов массивом
 * потом на основании него генерим список столбцов с функциями рендера для Грида
 * и передаем его в грид
 */

//формируем список столцов, если
if (!isset($columns)) $columns=['num','model','sn','mac','ip'];

//формируем список столбцов для рендера
$render_columns=[];
foreach ($columns as $column) {

	switch ($column) {
		case 'num':
			$render_columns[] = [
				'attribute' => 'num',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/techs/item', ['model' => $data]);
				}
			];
			break;

		case 'model':
			$render_columns[] = [
				'attribute' => 'model',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return is_object($data->model) ? $renderer->render('/tech-models/item', ['model' => $data->model, 'long' => true]) : null;
				}
			];
			break;

		case 'place':
			$render_columns[] = [
				'attribute' => 'place',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/places/item', ['model' => $data->effectivePlace, 'full' => true]);
				}
			];
			break;

		case 'user':
			$render_columns[] = [
				'attribute' => 'user',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/users/item', ['model' => $data->user]);
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

		case 'attach':
			$render_columns[] = [
				'attribute' => 'attach',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/techs/att-contracts', ['model' => $data]);
				}
			];
			break;

		case 'sn':
		case 'inv_num':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/techs/sn', ['model' => $data]);
				}
			];
			break;
		case 'comment':
			if (
				!empty($searchModel->type_id)
				&&
				(is_object($type=\app\models\TechTypes::findOne($searchModel->type_id)))
			) {
				$comment=$type->comment_name;
			} else {
				$comment=$searchModel->attributeLabels()['comment'];
			}
			
			$render_columns[] = [
				'attribute' => $column,
				'header'=> $comment,
				'format' => 'ntext',
				'value' => function ($data) use ($searchModel){return $data->comment.' '.$searchModel->model_id;}
			];
			break;
		case 'mac':
		case 'ip':
			$render_columns[] = $column;
			break;
	}
}

//try {
	echo GridView::widget([
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'columns' => $render_columns,
		'tableOptions' => ['class'=>'table-condensed table-striped table-bordered arms_index'],
		'resizableColumns'=>false,
	]);
//} catch (Exception $e) {
//    echo 'Ошибка вывода виджета таблицы<br/>';
//}
