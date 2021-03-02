<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\NetIps */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1 class="text-monospace">
	<?= Html::encode($model->sname) ?>
	<?= $static_view?'':(Html::a('<span class="glyphicon glyphicon-pencil"></span>',['net-ips/update','id'=>$model->id])) ?>
	<?php  if(!$static_view&&$deleteable) echo Html::a('<span class="glyphicon glyphicon-trash"/>', ['net-ips/delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить этот элемент? Действие необратимо',
			'method' => 'post',
		],
	]) ?>
</h1>

<?php
echo empty($model->name)?'':'<h4>'.Yii::$app->formatter->asNtext($model->name).'</h4>';
echo empty($model->comment)?'':Yii::$app->formatter->asNtext($model->comment);

$objects=[];

if (is_array($model->comps) && count ($model->comps)) {
	foreach ($model->comps as $comp) $objects[]=$this->render('/comps/item',['model'=>$comp]);
}

if (is_array($model->techs) && count ($model->techs)) {
	$techs=[];
	foreach ($model->techs as $tech) $objects[]=$this->render('/techs/item',['model'=>$tech]);
}

if (count($objects)) echo '<h4>привязан к:'.implode(', ',$objects).'</h4><br />';

?>
<h4>Сеть:</h4>
<?= $this->render('/networks/item',['model'=>$model->network]) ?>