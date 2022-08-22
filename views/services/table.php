<?php

use yii\helpers\Html;
use kartik\grid\GridView;
use yii\widgets\Pjax;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $models \app\models\Services[] */

if (!isset($columns)) $columns=['name','sites','segment','providingSchedule','supportSchedule','responsible','compsAndTechs'];

$renderer=$this;

//Суммы
$totalSites=[];
$totalSegments=[];
$totalSupport=[];
$totalComps=[];
$totalTechs=[];

foreach ($dataProvider->models as $model) {
	/* @var $model \app\models\Services */
	
	if (is_object($model->segmentRecursive)) {
		$totalSegments[$model->segmentRecursive->id]=$model->segmentRecursive;
	}
	
	if (is_object($model->responsibleRecursive)) if (!isset($totalSupport[$model->responsibleRecursive->id]))
		$totalSupport[$model->responsibleRecursive->id]=$renderer->render('/users/item', ['model' => $model->responsibleRecursive,'short'=>true]);

	if (is_array($model->supportRecursive))
		foreach ($model->supportRecursive as $user) if (!isset($totalSupport[$user->id]))
			$totalSupport[$user->id]=$renderer->render('/users/item', ['model' => $user,'short'=>true]);;
	
	if (is_array($model->comps))
		foreach ($model->comps as $comp) if (!isset($totalComps[$comp->id]))
			$totalComps[$comp->id]=$renderer->render('/comps/item', ['model' => $comp,'short'=>true]);

	if (is_array($model->techs))
		foreach ($model->techs as $tech) if (!isset($totalTechs[$tech->id]))
		$totalTechs[$tech->id]=$renderer->render('/techs/item', ['model' => $tech,'short'=>true]);
}

$totalSupportRendered=implode(', ',$totalSupport);
if (count($totalSupport)>10)
	$totalSupportRendered='<span onclick="{$(this).hide();$(\'#segmentsSupportTotal\').show()}">Показать '.count($totalSupport).' человек</span>'.
		'<span id="segmentsSupportTotal" style="display: none">'.$totalSupportRendered.'</span>';

$totalCompsAndTechsRendered=implode(', ',array_merge($totalComps,$totalTechs));
if ((count($totalComps)+count($totalTechs))>10)
	$totalCompsAndTechsRendered='<span onclick="$(this).hide();$(\'#segmentsCompsAndTechsTotal\').show()">Показать '.(count($totalComps)+count($totalTechs)).' элементов</span>'.
		'<span id="segmentsCompsAndTechsTotal" style="display: none">'.$totalCompsAndTechsRendered.'</span>';



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
					return $renderer->render('/segments/item', ['model' => $data->segmentRecursive,'crop_site'=>true]);
					
					/*return is_null($data->segment)?
						'<span class="fas fa-ban-circle"></span>Без польз. доступа':
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
					if (is_object($data->responsibleRecursive)) $output[] = '<strong>'.$renderer->render('/users/item', ['model' => $data->responsibleRecursive,'short'=>true]).'</strong>';
					if (is_array($data->supportRecursive)) foreach ($data->supportRecursive as $user)
						$output[] = $renderer->render('/users/item', ['model' => $user,'short'=>true]);
					return count($output) ? implode(', ', $output) : null;
				},
				'contentOptions' => ['class' => $column . '_col'],
				'footer'=>$totalSupportRendered,
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
		
		case 'techs':
			$render_columns[] = [
				'attribute' => $column,
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					$output = [];
					if (is_array($data->techs)) foreach ($data->techs as $tech)
						$output[] = $renderer->render('/techs/item', ['model' => $tech,'short'=>true]);
					return count($output) ? implode(', ', $output) : null;
				},
				'contentOptions' => ['class' => $column . '_col']
			];
			break;
		
		case 'compsAndTechs':
			$render_columns[] = [
				'attribute' => $column,
				'header' => 'Оборуд./ПК',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					$output = [];
					if (is_array($data->comps)) foreach ($data->comps as $comp)
						$output[] = $renderer->render('/comps/item', ['model' => $comp,'short'=>true]);
					if (is_array($data->techs)) foreach ($data->techs as $tech)
						$output[] = $renderer->render('/techs/item', ['model' => $tech,'short'=>true]);
					return count($output) ? implode(', ', $output) : null;
				},
				'contentOptions' => ['class' => $column . '_col'],
				'footer' => $totalCompsAndTechsRendered,
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
					if (is_array($data->sitesRecursive)) foreach ($data->sitesRecursive as $site)
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
				'value' => function ($data) use ($column,$renderer) {
					$columnRecursive="${column}Recursive";
					return is_object($data->$columnRecursive)?
						$renderer->render('/schedules/item',['model'=>$data->$columnRecursive,'static_view'=>true])
						:null;
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
	'showFooter' => true,
]);