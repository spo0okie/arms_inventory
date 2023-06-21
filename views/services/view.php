<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Services */
\yii\helpers\Url::remember();

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\Services::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);

$wikiLinks=\app\components\WikiPageWidget::getLinks($model->links);
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
		'label'=>$name,
		'active'=>$cookieTab==$tabId,
		'content'=>\app\components\WikiPageWidget::Widget(['list'=>$model->links,'item'=>$name]),
		'headerOptions'=>['onClick'=>'document.cookie = "'.$cookieTabName.'='.$tabId.'"'],
	];
	$tabNumber++;
}

$tabId='serviceComps';
$tabs[]=[
	'label'=>'Задействованные ОС',
	'active'=>$cookieTab==$tabId,
	'headerOptions'=>['onClick'=>'document.cookie = "'.$cookieTabName.'='.$tabId.'"'],
	'content'=><<<HTML
		<div id="serviceCompsList"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>
		<script>$.get("/web/services/os-list?id={$model->id}", function(data) {jQuery("#serviceCompsList").html(data);})</script>
HTML,

];

echo \yii\bootstrap5\Tabs::widget([
	'items'=>$tabs,
	'options'=>[
		'class'=>'nav-pills',
	]
]);
