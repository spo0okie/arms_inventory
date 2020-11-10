<?php
/** Элемент софта
 * Created by PhpStorm.
 * User: spookie
 * Date: 04.11.2018
 * Time: 15:03
 */
use yii\helpers\Html;

if (!isset($static_view)) $static_view=false;
if (!isset($hitlist)) $hitlist=null;

/* @var $model \app\models\Soft */
?>

<spam class="soft-item"
	qtip_ajxhrf="<?= \yii\helpers\Url::to([
		'/soft/ttip',
		'id'=>$model->id,
		'hitlist'=>$hitlist
	])?>"
>
	<?= Html::a($model->descr,['/soft/view','id'=>$model->id]) ?>
	<?php if(!$static_view) echo Html::a('<span class="glyphicon glyphicon-pencil"/>',['/soft/update','id'=>$model->id]) ?>
</spam>