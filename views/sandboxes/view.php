<?php

use app\components\DynaGridWidget;
use app\components\TabsWidget;
use app\models\Comps;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Sandboxes */

//\yii\helpers\Url::remember();

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => app\models\Sandboxes::$titles, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
YiiAsset::register($this);

$badge='<span class="badge rounded-pill p-1 m-1 bg-secondary opacity-25">';

$tabs=[];

$tabs[]=[
	'id'=>'computers',
	'label'=>'ОС / ВМ '.$badge.count($model->comps).'</span>',
	'content'=>DynaGridWidget::widget([
		'id' => 'reqs-comps',
		'header' => false,
		'columns' => require $_SERVER['DOCUMENT_ROOT'].'/views/comps/columns.php',
		//'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
		'createButton' => Html::a('Добавить', ['comps/create','Comps'=>['sandbox_id'=>$model->id]], [
			'class' => 'btn btn-success open-in-modal-form',
			'data-reload-page-on-submit'=>1
		]),
		'dataProvider' => new ArrayDataProvider(['allModels'=>$model->comps]),
		'model' => new Comps()
	]),
];
?>
<div class="sandboxes-view">
	<?= $this->render('card',['model'=>$model]) ?>
</div>


<?php
TabsWidget::addWikiLinks($tabs,$model->links);

echo TabsWidget::widget([
	'items'=>$tabs,
	'cookieName'=>'services-view-tab-'.$model->id,
	'options'=>[
		'itemsOptions'=>['class'=>'mx-5'],
		'class'=>'nav-pills',
	]
]);
