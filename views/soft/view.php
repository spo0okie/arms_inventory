<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Soft */
/* @var $searchModel app\models\SoftSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $licProvider yii\data\ActiveDataProvider */

$this->title = (is_object($model->manufacturer)?$model->manufacturer->name.' ':'').$model->descr;
$this->params['breadcrumbs'][] = ['label' => 'ПО', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$keysCount=0;

foreach ($licProvider->models as $lic) {
	$keysCount+=$lic->activeCount;
}

?>
<div class="row">
	<div class="col-md-6">
		<?= $this->render('card',['model'=>$model]) ?>
	</div>
	<div class="col-md-6">
		<h4>Совместимые лицензии</h4>
		<?= DynaGridWidget::widget([
			'id' => 'soft-lic-groups-list',
			'columns' => include $_SERVER['DOCUMENT_ROOT'].'/views/lic-groups/columns.php',
			//'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
			'dataProvider' => $licProvider,
			'panel'=>false
		]) ?>
		<?= count($licProvider->models)?('<span class="h5">Итого активных лицензий :'.$keysCount.'</span>'):'' ?>
	</div>
</div>

<h4>Установки</h4>
<?= DynaGridWidget::widget([
	'id' => 'soft-comps-list',
	'columns' => include $_SERVER['DOCUMENT_ROOT'].'/views/comps/columns.php',
	'defaultOrder' => ['name','ip','mac','os','updated_at','arm_id','places_id','raw_version'],
	'dataProvider' => $dataProvider,
	'filterModel' => $searchModel,
	'panel'=>false
]) ?>
