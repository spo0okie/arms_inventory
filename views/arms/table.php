<?php

use kartik\grid\GridView;

/**
 * Это рендер списка АРМов, вынесен отдельным файлом, т.к. нужен много где:
 * в списке АРМов
 *
 */


/* @var $this yii\web\View */
/* @var $searchModel app\models\ArmsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer = $this;
if (!isset($columns)) $columns=['attach','num','model','comp_id','comp_ip','sn','state','user_id','places_id'];

$manufacturers=\app\models\Manufacturers::fetchNames();

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
					return is_object($data->comp) ? str_replace(',','<br />',$data->comp->filteredIpsStr) : null;
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

		case 'user_position':
			$render_columns[] = [
				'attribute' => 'user_position',
				'format' => 'raw',
				'header' => 'Должность',
				'value' => function ($data) use ($renderer) {
					return is_object($data->user) ? $data->user->Doljnost : null;
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
		
		case 'departments_id':
			$render_columns[] = [
				'attribute' => 'departments_id',
				'format' => 'raw',
				'value' => function ($data) {
					return is_object($data->department) ? $data->department->name:null;
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
		
		case 'comp_hw':
			$render_columns[] = [
				'attribute' => 'comp_hw',
				'format' => 'raw',
				'value' => function ($data) use ($manufacturers) {
					if (is_object($data->comp)) {
						$render=[];
						foreach ($data->comp->getHardArray() as $item) {
							$render[]=$item->getName();
						}
						//тут делаем следующее: выводим в одну строку, а в тултипе выводим по строкам
						$single=implode(' ',$render);
						$multi=implode('<br />',$render);
						return "<span title='{$multi}'>$single</span>";
					}
					return null;
				}
			];
			break;
		
		case 'sn':
		case 'inv_num':
			$render_columns[] = $column;


	}
}

//try {
	echo GridView::widget([
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
		'columns' => $render_columns,
		'tableOptions' => ['class'=>'table-condensed table-striped table-bordered'],
	]);
//} catch (Exception $e) {
//	echo 'Ошибка вывода виджета таблицы<br/>';
// }

