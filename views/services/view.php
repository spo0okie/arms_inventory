<?php

use app\components\assets\DynaGridWidgetAsset;
use app\components\DynaGridWidget;
use app\components\TabsWidget;
use app\components\TextFieldWidget;
use app\models\Services;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;
use yii\web\YiiAsset;
use app\components\ShowArchivedWidget;


/* @var $this yii\web\View */
/* @var $model app\models\Services */
Url::remember();

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => Services::$titles, 'url' => ['index']];
$model->recursiveBreadcrumbs($this,'nameWithoutParent');

$this->params['layout-container']='fluid mx-5';

YiiAsset::register($this);
DynaGridWidgetAsset::register($this);

?>
<div class="services-view">

    <?= $this->render('card',['model'=>$model]) ?>
	<?php if (strlen($model->notebook)) { ?>
		<h4>Записная книжка:</h4>
		<p>
			<?= TextFieldWidget::widget(['model'=>$model,'field'=>'notebook']) ?>
		</p>
		<br />
	<?php } ?>
</div>
<?php

$tabs=[];
$showArchived = ShowArchivedWidget::isOn();
DynaGridWidget::handleSave('services-index');
$tabs[]=[
	'id'=>'serviceChildren',
	'label'=>'Состав сервиса <span class="count"></span>'
		.'<i title="настройки таблицы" data-bs-toggle="modal" data-bs-target="#services-index-grid-modal" class="small fas fa-wrench fa-fw"></i>',
	'content'=>Html::a('Добавить субсервис',[
		'create','Services'=>['parent_id'=>$model->id]
	],[
		'class'=>'badge text-bg-success m-0'
	]).TabsWidget::ajaxLoadItems(
		'serviceChildren',
		"/web/services/children-tree?id={$model->id}&showArchived={$showArchived}"
	)
];



DynaGridWidget::handleSave('services-comps-index');
$tabs[]=[
	'id'=>'serviceComps',
	'label'=>'Оборудование и ОС <span class="count"></span> <i title="настройки таблицы" data-bs-toggle="modal" data-bs-target="#services-comps-index-grid-modal" class="small fas fa-wrench fa-fw"></i>',
	'content'=>TabsWidget::ajaxLoadItems(
		'serviceComps',
			"/web/services/os-list?id={$model->id}&showArchived={$showArchived}"
	)
];
	


DynaGridWidget::handleSave('service-aces-list');
$tabs[]=[
	'id'=>'serviceAces',
	'label'=>'Доступ отсюда <span class="count"></span> <i title="настройки таблицы" data-bs-toggle="modal" data-bs-target="#service-aces-list-grid-modal" class="small fas fa-wrench fa-fw"></i>',
	'content'=>Html::a('Добавить исходящий доступ',[
		'/acls/create','Aces'=>['services_ids'=>[$model->id]]
	],[
		'class'=>'badge text-bg-success m-0 open-in-modal-form',
		'data-reload-page-on-submit'=>1
	]).TabsWidget::ajaxLoadItems(
		'serviceAces',
		"/web/services/aces-list?id={$model->id}&showArchived={$showArchived}"
	)
];


DynaGridWidget::handleSave('service-acls-list');
$tabs[]=[
	'id'=>'serviceAcls',
	'label'=>'Доступы сюда <span class="count"></span> <i title="настройки таблицы" data-bs-toggle="modal" data-bs-target="#service-acls-list-grid-modal" class="small fas fa-wrench fa-fw"></i>',
	'content'=>Html::a('Добавить входящий доступ',[
		'/acls/create','Acls'=>['services_id'=>$model->id]
	],[
		'class'=>'badge text-bg-success m-0 open-in-modal-form',
		'data-reload-page-on-submit'=>1
	]).TabsWidget::ajaxLoadItems(
		'serviceAcls',
		"/web/services/acls-list?id={$model->id}&showArchived={$showArchived}"
	)

];


TabsWidget::addWikiLinks($tabs,$model->linksRecursive);

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