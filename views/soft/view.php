<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Soft */

$this->title = (is_object($model->manufacturer)?$model->manufacturer->name.' ':'').$model->descr;
$this->params['breadcrumbs'][] = ['label' => 'ПО', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="row">
	<div class="col-md-6">
		<?= $this->render('card',['model'=>$model]) ?>
	</div>
	<div class="col-md-6">
		<h4>Совместимые лицензии</h4>
		<?php foreach ($model->licGroups as $lic) echo $this->render('/lic-groups/item',['model'=>$lic]).'<br>' ?>
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
