<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Places */

//qtip_ajxhrf="<?= \yii\helpers\Url::to(['/org-phones/ttip','id'=>$model->id])
//qtip_class="qtip-wide"
//	<?= Html::a('<span class="glyphicon glyphicon-pencil"/>',['/places/update','id'=>$model->id])

if (is_object($model)) { ?>
	<span class="services-item" qtip_ajxhrf="<?= \yii\helpers\Url::to(['/services/ttip','id'=>$model->id]) ?>">
		<?= Html::a($model->name,['/services/view','id'=>$model->id]) ?>
    	<?= Html::a('<span class="glyphicon glyphicon-pencil"/>',['/services/update','id'=>$model->id]) ?>
	</span>
<?php }