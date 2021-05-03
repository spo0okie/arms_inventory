<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $models \app\models\Services[] */


$renderer=$this;

if (!isset($columns)) $columns=['name','sites','segment','providingSchedule','supportSchedule','responsible','comps'];

//формируем список столбцов для рендера
$render_columns=[];
foreach ($columns as $column) {
	
	switch ($column) {
		case 'name':
			$render_columns[] = [
				'attribute' => 'name',
				//'header' => 'Инв. номер',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/services/item', ['model' => $data,'crop_site'=>true]);
				},
				'contentOptions' => ['class' => $column . '_col']
			];
			break;
		
		case 'segment':
			$render_columns[] = [
				'attribute' => 'segment',
				//'header' => 'Инв. номер',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/segments/item', ['model' => $data->segment,'crop_site'=>true]);
					
					/*return is_null($data->segment)?
						'<span class="glyphicon glyphicon-ban-circle"></span>Без польз. доступа':
						$renderer->render('/segments/item', ['model' => $data->segment,'crop_site'=>true]);*/
				},
				'contentOptions' => ['class' => $column . '_col']
			];
			break;
		
		case 'description':
			$render_columns[] = [
				'attribute' => $column,
				//'header' => 'Инв. номер',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return \app\components\UrlListWidget::Widget(['list'=>$data->links]).' '.$data->description;
				},
				'contentOptions' => ['class' => $column . '_col']
			];
			break;
		
		case 'responsible':
			$render_columns[] = [
				'attribute' => $column,
				'header' => 'Отв., поддержка.',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					$output = [];
					if (is_object($data->responsible)) $output[] = '<strong>'.$renderer->render('/users/item', ['model' => $data->responsible,'short'=>true]).'</strong>';
					if (is_array($data->support)) foreach ($data->support as $user)
						$output[] = $renderer->render('/users/item', ['model' => $user,'short'=>true]);
					return count($output) ? implode(', ', $output) : null;
				},
				'contentOptions' => ['class' => $column . '_col']
			];
			break;
		
		
		case 'arms':
			$render_columns[] = [
				'attribute' => $column,
				//'header' => 'Инв. номер',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					$output = [];
					if (is_array($data->arms)) foreach ($data->arms as $arm)
						$output[] = $renderer->render('/arms/item', ['model' => $arm,'short'=>true]);
					return count($output) ? implode(', ', $output) : null;
				},
				'contentOptions' => ['class' => $column . '_col']
			];
			break;
		
		case 'comps':
			$render_columns[] = [
				'attribute' => $column,
				//'header' => 'Инв. номер',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					$output = [];
					if (is_array($data->comps)) foreach ($data->comps as $comp)
						$output[] = $renderer->render('/comps/item', ['model' => $comp,'short'=>true]);
					return count($output) ? implode(', ', $output) : null;
				},
				'contentOptions' => ['class' => $column . '_col']
			];
			break;
		
		case 'places':
			$render_columns[] = [
				'attribute' => $column,
				//'header' => 'Инв. номер',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					$output = [];
					if (is_array($data->places)) foreach ($data->places as $place)
						$output[] = $renderer->render('/places/item', ['model' => $place,'short'=>true]);
					return count($output) ? implode(', ', $output) : null;
				},
				'contentOptions' => ['class' => $column . '_col']
			];
			break;
		
		case 'sites':
			$render_columns[] = [
				'attribute' => $column,
				//'header' => 'Инв. номер',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					$output = [];
					if (is_array($data->sites)) foreach ($data->sites as $site)
						$output[] = $renderer->render('/places/item', ['model' => $site,'short'=>true]);
					return count($output) ? implode(' ', $output) : null;
				},
				'contentOptions' => ['class' => $column . '_col']
			];
			break;
		
		
		case 'providingSchedule':
		case 'supportSchedule':
			$render_columns[] = [
				'attribute' => $column,
				//'header' => 'Инв. номер',
				'format' => 'raw',
				'value' => function ($data) use ($column) {
					return is_object($data->$column) ? $data->$column->name : null;
				},
				'contentOptions' => ['class' => $column . '_col']
			];
			break;
		
		default:
			$render_columns[] = $column;
		
	}
}

echo GridView::widget([
	'dataProvider' => $dataProvider,
	'filterModel' => $searchModel,
	'columns' => $render_columns,
]);