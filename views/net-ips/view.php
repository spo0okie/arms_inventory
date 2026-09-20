<?php

use app\components\AccessLevelSwitchWidget;
use app\components\integrations\PanelsWidget;
use app\components\ShowArchivedWidget;
use app\components\TabsWidget;
use app\components\widgets\page\CornerWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\NetIps */

$this->title = $model->sname;
//крошки собираются автоматически в layout (views/layouts/main.php)
\yii\web\YiiAsset::register($this);

//шапка: слева адрес, привязки, DNS-имена, пробросы; справа — сводка сети адреса
$this->params['headerContent'] =
	'<div class="row">'
		.'<div class="col-md-8">'
			.$this->render('card',['model'=>$model,'header'=>true])
			.PanelsWidget::widget(['model'=>$model])
		.'</div>'
		.'<div class="col-md-4">'
			.CornerWidget::widget(['model'=>$model,'archivedOptions'=>['reload'=>true]])
			.$this->render('network',['model'=>$model])
			//уровень показа доступов во вкладках ниже (docs/dev/access-chains.md, §5)
			.'<div class="mt-3">'.AccessLevelSwitchWidget::widget().'</div>'
		.'</div>'
	.'</div>';

//доступы — как на страницах сервиса и сегмента: гриды ACE с переключателем уровней
//(информационный / сетевой) и колонкой «Маршрут»
$showArchived=ShowArchivedWidget::isOn();
$tabs=[];
$tabs[]=TabsWidget::asyncDynagridPropertyTab($model,'acls',$showArchived,
	filter: ['ips_resource_ids'=>[$model->id]],
	linkClass: 'aces',
	staticContent: Html::a('Добавить входящий доступ',[
		//форма создания ACL групповая: ресурсы — мультиселекты *_ids
		'/acls/create','Acls'=>['ips_ids'=>[$model->id]]
	],[
		'class'=>'badge text-bg-success m-0 open-in-modal-form',
		'data-reload-page-on-submit'=>1
	])
);
$tabs[]=TabsWidget::asyncDynagridPropertyTab($model,'aces',$showArchived,
	filter: ['ips_subject_ids'=>[$model->id]],
	linkClass: 'aces',
	staticContent: Html::a('Добавить исходящий доступ',[
		'/acls/create','Aces'=>['ips'=>$model->text_addr]
	],[
		'class'=>'badge text-bg-success m-0 open-in-modal-form',
		'data-reload-page-on-submit'=>1
	])
);

$this->params['navTabs']=$tabs;
$this->params['tabsParams']=[
	'cookieName'=>'net-ips-view-tab-'.$model->id,
];
