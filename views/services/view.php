<?php

use app\components\assets\DynaGridWidgetAsset;
use app\components\DynaGridWidget;
use app\components\TabsWidget;
use app\models\Services;
use yii\helpers\Url;
use yii\web\View;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Services */
Url::remember();

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Services::$titles, 'url' => ['index']];
$model->recursiveBreadcrumbs($this,'parentService','nameWithoutParent');

$this->params['layout-container']='fluid mx-5';

YiiAsset::register($this);
DynaGridWidgetAsset::register($this);

?>
<div class="services-view">

    <?= $this->render('card',['model'=>$model]) ?>
	
</div>
<?php

$tabs=[];

if (count($model->children)||count($model->comps)||count($model->techs)) {
		
	DynaGridWidget::handleSave('services-comps-index');
	
	$tabs[]=[
		'id'=>'serviceComps',
		'label'=>'Оборудование и ОС <i title="настройки таблицы" data-bs-toggle="modal" data-bs-target="#services-comps-index-grid-modal" class="small fas fa-wrench fa-fw"></i>',
		'content'=><<<HTML
		<div id="serviceCompsList">
		
			<div class="spinner-border" role="status">
				<span class="visually-hidden">Loading...</span>
			</div>
		</div>
		<script>
			$(document).ready(function() {
				$.get("/web/services/os-list?id={$model->id}", function(data) {
				    jQuery("#serviceCompsList").hide().html(data);
				    setTimeout(function (){jQuery("#serviceCompsList").fadeToggle();ExpandableCardInitAll();},500)
				})
			})
		</script>
HTML,
	];
	
}

if (count($model->children)||count($model->incomingConnections)||count($model->outgoingConnections)) {
	DynaGridWidget::handleSave('service-connections-list');
	
	$tabs[]=[
		'id'=>'serviceConnections',
		'label'=>'Связи <i title="настройки таблицы" data-bs-toggle="modal" data-bs-target="#service-connections-list-grid-modal" class="small fas fa-wrench fa-fw"></i>',
		'content'=><<<HTML
		<div id="serviceConnectionsList">
		
			<div class="spinner-border" role="status">
				<span class="visually-hidden">Loading...</span>
			</div>
		</div>
		<script>
			$(document).ready(function() {
				$.get("/web/services/connections-list?id={$model->id}", function(data) {
				    jQuery("#serviceConnectionsList").hide().html(data);
				    setTimeout(function (){jQuery("#serviceConnectionsList").fadeToggle();ExpandableCardInitAll();},500)
				})
			})
		</script>
HTML,
	];
	
}

TabsWidget::addWikiLinks($tabs,$model->links);

echo TabsWidget::widget([
	'items'=>$tabs,
	'cookieName'=>'services-view-tab-'.$model->id,
	'options'=>[
		'itemsOptions'=>['class'=>'mx-5'],
		'class'=>'nav-pills',
	]
]);

//после перезагрузки PJAX элементов. Можно убрать в layout
$this->registerJs(<<<JS
	$(document).on('pjax:success', function() {
	    ExpandableCardInitAll();
	})
JS
, View::POS_END);