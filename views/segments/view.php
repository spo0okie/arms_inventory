<?php

use app\components\ShowArchivedWidget;
use app\components\TabsWidget;
use app\models\Segments;
use kartik\markdown\Markdown;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Segments */
/* @var $networksSearch app\models\NetworksSearch */
/* @var $networksProvider yii\data\ActiveDataProvider */
/* @var $servicesSearch app\models\ServicesSearch */
/* @var $servicesProvider yii\data\ActiveDataProvider */


//\yii\helpers\Url::remember();

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Segments::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
YiiAsset::register($this);

$segmentRender=Markdown::convert($model->history);
$segmentLines=count(explode("\n",trim($model->history)));
$segmentCompact=$segmentLines<=Yii::$app->params['networkInlineDescriptionLimit'];


if ($model->history && $segmentCompact) {
	$this->params['headerContent'] = '<div class="mx-4 pb-2">'
		. '<span class="float-end">' . ShowArchivedWidget::widget() . '</span>'
		. $this->render('card', ['model' => $model])
		. '</div>';
} else {
	$this->params['headerContent'] = '<div class="mx-4 pb-2">'
		. '<span class="float-end">' . ShowArchivedWidget::widget() . '</span>'
		. $this->render('header-compact', ['model' => $model])
		. '</div>';
}




//$cookieTabName='segments-view-tab-'.$model->id;
//$cookieTab=$_COOKIE[$cookieTabName]??'networks';
$tabs=[];
if ($model->history && !$segmentCompact) {
	$tabs[]=[
		'label'=>'Подробное описание',
		'id'=>'description',
		'content'=> Markdown::convert($model->history),
	];
}

$tabs[]=[
	'label'=>'Сети',
	'id'=>'networks',
	'content'=>'<h4>Сети входящие в этот сегмент</h4>'.
	$this->render('/networks/table',[
		'dataProvider'=>$networksProvider,
		'searchModel'=>$networksSearch,
		'columns'=>['name','comment','vlan','domain','usage']
	]),
];
$tabs[]=[
	'label'=>'Сервисы',
	'id'=>'services',
	'content'=>'<h4>Сервисы входящие в этот сегмент</h4>'.
		$this->render('/services/table',[
			'dataProvider'=>$servicesProvider,
			'searchModel'=>$servicesSearch,
			'columns'=>['name','sites','providingSchedule','supportSchedule','responsible','compsAndTechs']
		]),
];


TabsWidget::addWikiLinks($tabs,$model->links);	//добавляем из вики

$this->params['navTabs']=$tabs;
$this->params['tabsParams']=[
	'cookieName'=>'segments-view-tab-'.$model->id
];