<?php

use app\components\TabsWidget;
use app\components\widgets\page\CornerWidget;
use app\components\widgets\page\ModelWidget;
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
//крошки собираются автоматически в layout (views/layouts/main.php)
YiiAsset::register($this);

$segmentRender=Markdown::convert($model->history??'');
$segmentLines=count(explode("\n",trim($model->history??'')));
$segmentCompact=$segmentLines<=Yii::$app->params['networkInlineDescriptionLimit'];


$this->params['headerContent'] = '<div class="mx-4 pb-2">'
	.CornerWidget::widget(['model'=>$model,'archivedOptions'=>['reload'=>true]])
	.ModelWidget::widget([
		'model' => $model,
		'view'=>($model->history && $segmentCompact)?'card':'header-compact'
	])
	.'</div>';




//$cookieTabName='segments-view-tab-'.$model->id;
//$cookieTab=$_COOKIE[$cookieTabName]??'networks';
$tabs=[];
if ($model->history && !$segmentCompact) {
	$tabs[]=[
		'label'=>'Подробное описание',
		'id'=>'description',
		'content'=> Markdown::convert($model->history??''),
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


//доступы сегмента: входящие — ACL с ресурсом «этот сегмент» (все их записи, включая
//несегментных субъектов — матрица их игнорирует), исходящие — ACE с субъектом «этот сегмент»
$showArchived=(bool)Yii::$app->request->get('showArchived',false);
$tabs[]=TabsWidget::asyncDynagridPropertyTab($model,'acls',$showArchived,
	filter: ['segments_resource_ids'=>[$model->id]],
	linkClass: 'aces',
	staticContent: \yii\helpers\Html::a('Добавить входящий доступ',[
		'/acls/create','Acls'=>['segments_ids'=>[$model->id]]
	],[
		'class'=>'badge text-bg-success m-0 open-in-modal-form',
		'data-reload-page-on-submit'=>1
	])
);
$tabs[]=TabsWidget::asyncDynagridPropertyTab($model,'aces',$showArchived,
	filter: ['segments_subject_ids'=>[$model->id]],
	linkClass: 'aces',
	staticContent: \yii\helpers\Html::a('Добавить исходящий доступ',[
		'/acls/create','Aces'=>['segments_ids'=>[$model->id]]
	],[
		'class'=>'badge text-bg-success m-0 open-in-modal-form',
		'data-reload-page-on-submit'=>1
	])
	.' '.\yii\helpers\Html::a('Матрица межсегментного доступа',['/segments/matrix'],['class'=>'badge text-bg-secondary m-0'])
);

TabsWidget::addWikiLinks($tabs,$model->links);	//добавляем из вики

$this->params['navTabs']=$tabs;
$this->params['tabsParams']=[
	'cookieName'=>'segments-view-tab-'.$model->id
];
