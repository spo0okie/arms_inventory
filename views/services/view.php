<?php

use app\components\assets\DynaGridWidgetAsset;
use app\components\DynaGridWidget;
use app\components\WikiPageWidget;
use app\models\Services;
use yii\bootstrap5\Tabs;
use yii\helpers\Url;
use yii\web\View;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Services */
Url::remember();

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Services::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
YiiAsset::register($this);
DynaGridWidgetAsset::register($this);

$wikiLinks= WikiPageWidget::getLinks($model->links);
$cookieTabName='services-view-tab-'.$model->id;
$cookieTab=$_COOKIE[$cookieTabName]??(count($wikiLinks)?'wiki0':'serviceComps');



?>
<div class="services-view">

    <?= $this->render('card',['model'=>$model]) ?>
	
</div>
<?php

$tabs=[];
$tabNumber=0;
foreach ($wikiLinks as $name=>$url) {
	$tabId='wiki'.$tabNumber;
	$tabs[]=[
		'label'=>($name==$url)?'Wiki':$name,
		'active'=>$cookieTab==$tabId,
		'content'=> WikiPageWidget::Widget(['list'=>$model->links,'item'=>$name]),
		'headerOptions'=>['onClick'=>'document.cookie = "'.$cookieTabName.'='.$tabId.'"'],
	];
	$tabNumber++;
}

if (count($model->children)||count($model->comps)||count($model->techs)) {
		
	DynaGridWidget::handleSave('services-comps-index');
	
	$tabId='serviceComps';
	$tabs[]=[
		'label'=>'Оборудование и ОС <i title="настройки таблицы" data-bs-toggle="modal" data-bs-target="#services-comps-index-grid-modal" class="small fas fa-wrench fa-fw"></i>',
		'encode'=>false,
		'active'=>$cookieTab==$tabId,
		'headerOptions'=>['onClick'=>'document.cookie = "'.$cookieTabName.'='.$tabId.'"'],
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

echo Tabs::widget([
	'items'=>$tabs,
	'options'=>[
		'class'=>'nav-pills',
	]
]);

$this->registerJs(<<<JS
	$(document).on('pjax:success', function() {
	    ExpandableCardInitAll();
	})
JS
, View::POS_END);