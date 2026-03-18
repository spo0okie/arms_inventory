<?php

use app\components\UrlListWidget;
use app\models\Services;
use kartik\grid\GridView;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $searchModel app\models\ServicesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $models Services[] */

if (!isset($columns)) $columns=['name','sites','segment','providingSchedule','supportSchedule','responsible','compsAndTechs'];

$renderer=$this;

//Суммы
$totalSites=[];
$totalSegments=[];
$totalSupport=[];
$totalComps=[];
$totalTechs=[];

foreach ($dataProvider->models as $model) {
	/* @var $model Services */
	
	if (is_object($model->segmentRecursive)) {
		$totalSegments[$model->segmentRecursive->id]=$model->segmentRecursive;
	}
	
	if (is_object($model->responsibleRecursive)) if (!isset($totalSupport[$model->responsibleRecursive->id]))
		$totalSupport[$model->responsibleRecursive->id]=ModelWidget::widget(['model'=>$model->responsibleRecursive,'options'=>['short'=>true]]);

	if (is_array($model->supportRecursive))
		foreach ($model->supportRecursive as $user) if (!isset($totalSupport[$user->id]))
			$totalSupport[$user->id]=ModelWidget::widget(['model'=>$user,'options'=>['short'=>true]]);
	
	if (is_array($model->comps))
		foreach ($model->comps as $comp) if (!isset($totalComps[$comp->id]))
			$totalComps[$comp->id]=ModelWidget::widget(['model'=>$comp,'options'=>['short'=>true]]);

	if (is_array($model->techs))
		foreach ($model->techs as $tech) if (!isset($totalTechs[$tech->id]))
		$totalTechs[$tech->id]=ModelWidget::widget(['model'=>$tech,'options'=>['short'=>true]]);
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
					return ModelWidget::widget(['model'=>$data,'options'=>['crop_site'=>true]]);
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
					return ModelWidget::widget(['model'=>$data->segmentRecursive,'options'=>['crop_site'=>true]]);
					
					/*return is_null($data->segment)?
						'<span class="fas fa-ban-circle"></span>Без польз. доступа':
						ModelWidget::widget(['model'=>$data->segment,'options'=>['crop_site'=>true]]);*/
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
					return UrlListWidget::Widget(['list'=>$data->links]).' '.$data->description;
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
					if (is_object($data->responsibleRecursive)) $output[] = '<strong>'.ModelWidget::widget(['model'=>$data->responsibleRecursive,'options'=>['short'=>true]]).'</strong>';
					if (is_array($data->supportRecursive)) foreach ($data->supportRecursive as $user)
						$output[] = ModelWidget::widget(['model'=>$user,'options'=>['short'=>true]]);
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
						$output[] = ModelWidget::widget(['model'=>$arm,'options'=>['short'=>true]]);
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
						$output[] = ModelWidget::widget(['model'=>$comp,'options'=>['short'=>true]]);
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
						$output[] = ModelWidget::widget(['model'=>$tech,'options'=>['short'=>true]]);
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
						$output[] = ModelWidget::widget(['model'=>$comp,'options'=>['short'=>true]]);
					if (is_array($data->techs)) foreach ($data->techs as $tech)
						$output[] = ModelWidget::widget(['model'=>$tech,'options'=>['short'=>true]]);
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
						$output[] = ModelWidget::widget(['model'=>$place,'options'=>['short'=>true]]);
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
						$output[] = ModelWidget::widget(['model'=>$site,'options'=>['short'=>true]]);
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
						$renderer->render('/schedules/schedules/item',['model'=>$data->$columnRecursive,'static_view'=>true])
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


