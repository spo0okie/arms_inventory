<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;



/* @var $this yii\web\View */
/* @var $model app\models\TechModels */

\yii\helpers\Url::remember();

$renderer=$this;
$this->title = $model->manufacturer->name.' '.$model->name;
$this->params['breadcrumbs'][] = ['label' => \app\models\TechTypes::$title, 'url' => ['/tech-types/index']];
$this->params['breadcrumbs'][] = ['label' => $model->type->name, 'url' => ['/tech-types/view','id'=>$model->type_id]];
$this->params['breadcrumbs'][] = $this->title;
$static_view=false;
?>
<div class="tech-models-view">

    <h1>
		<?= \app\components\LinkObjectWidget::widget([
			'model'=>$model->type,
			'ttipUrl'=>false,
			'static'=>true
		]) ?>
        <?= \app\components\LinkObjectWidget::widget([
			'model'=>$model,
			'name'=>$model->manufacturer->name.' '.$model->name,
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
			<h4><?= Html::a('Изображения',['uploads','id'=>$model->id]) ?></h4>
			<?php
			if (is_array($scans=$model->scans)&&count($scans)) foreach ($scans as $scan)
				echo $this->render('/scans/thumb',['model'=>$scan,'contracts_id'=>$model->id,'static_view'=>true]);
			?>
			<p>
			<h4>Ссылки:</h4>
			<?= \app\components\UrlListWidget::Widget(['list'=>$model->links]) ?>
			</p>

			<br />

		</div>
	</div>
	


    <?php if (count($model->techs)) {?>
        <h4>Экземпляры оборудования:</h4>
	    <?= $this->render('/techs/table', [
		    'searchModel'   => $techSearchModel,
		    'dataProvider'  => $techDataProvider,
		    'columns'       => ['num','mac','ip','state','user','place','inv_num'],
	    ]) ?>
    <?php } ?>

	<?php if (count($model->arms)) {?>
        <h4>АРМ этой модели:</h4>
		<?= DynaGridWidget::widget([
			'id' => 'tech-types-arms-index',
			'header' => '',
			'columns' => require __DIR__.'/../arms/columns.php',
			'defaultOrder' => ['attach','num','model','comp_id','comp_ip','sn','state','user_id','places_id'],
			//'createButton' => Html::a('Создать АРМ', ['create'], ['class' => 'btn btn-success']),
			//'hintButton' => \app\components\HintIconWidget::widget(['model' => '\app\models\Arms', 'cssClass' => 'btn']),
			'dataProvider' => $armsDataProvider,
			'filterModel' => $armsSearchModel,
		]) ?>
	<?php } ?>

</div>
