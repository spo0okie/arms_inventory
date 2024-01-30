<?php

use app\components\DynaGridWidget;
use app\components\HistoryWidget;
use app\components\LinkObjectWidget;
use app\components\UrlListWidget;
use app\models\TechsSearch;
use app\models\TechTypes;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;


/* @var $this yii\web\View */
/* @var $model app\models\TechModels */
/* @var $dataProvider ActiveDataProvider */
/* @var $searchModel TechsSearch */

Url::remember();

$renderer=$this;
$this->title = $model->nameWithVendor;
$this->params['breadcrumbs'][] = ['label' => TechTypes::$title, 'url' => ['/tech-types/index']];
$this->params['breadcrumbs'][] = ['label' => $model->type->name, 'url' => ['/tech-types/view','id'=>$model->type_id]];
$this->params['breadcrumbs'][] = $this->title;
$static_view=false;
?>
<div class="tech-models-view">
<div class="float-end text-end">
	<small class="float-end opacity-75"><?= HistoryWidget::widget(['model'=>$model]) ?></small>
	<h1><?= Html::a('<i class="fas fa-images"></i>',['uploads','id'=>$model->id],[
		'class'=>'float-end',
		'qtip_ttip'=>'Редактировать изображения/фото этой модели оборудования',
		'qtip_side'=>'top'
	]) ?></h1>

</div>
    <h1>
		<?= LinkObjectWidget::widget([
			'model'=>$model->type,
			'ttipUrl'=>false,
			'static'=>true
		]) ?>
        <?= LinkObjectWidget::widget([
			'model'=>$model,
			'name'=>$model->nameWithVendor,
			'confirmMessage' => 'Действительно удалить описание этой модели оборудования?',
			'undeletableMessage'=>'Описание этой модели оборудования нельзя удалить в настоящий момент,<br> т.к. в БД есть экземпляры оборудования этой модели',
		]) ?>
	</h1>
	
	<div class="row">
		<div class="col-md-6">
			<p>
				<?= Yii::$app->formatter->asNtext($model->comment) ?>
			</p>
			
		</div>
		<div class="col-md-6">
			<?php
			if (is_array($scans=$model->scans)&&count($scans)) foreach ($scans as $scan)
				echo $this->render('/scans/thumb',['model'=>$scan,'contracts_id'=>$model->id,'static_view'=>true]);
			?>
			<h4>Ссылки:</h4>
			<p class="mb-2">
			<?= UrlListWidget::Widget(['list'=>$model->links]) ?>
			</p>
			<?= $this->render('/attaches/model-list',compact(['model','static_view'])) ?>

		</div>
	</div>
	


    <h4>Экземпляры АРМ/оборудования:</h4>
	<?= DynaGridWidget::widget([
		'id' => 'tech-types-arms-index',
		'header' => '',
		'columns' => require __DIR__.'/../techs/columns.php',
		'defaultOrder' => ['attach','num','model','comp_id','comp_ip','sn','state','user_id','places_id'],
		'createButton' => Html::a('Добавить', [
			'/techs/create',
			'Techs[model_id]'=>$model->id
		], [
			'class' => 'btn btn-success open-in-modal-form',
			'data-reload-page-on-submit'=>1
		]),
		//'hintButton' => \app\components\HintIconWidget::widget(['model' => '\app\models\Arms', 'cssClass' => 'btn']),
		'dataProvider' => $dataProvider,
		'filterModel' => $searchModel,
	]) ?>

</div>
