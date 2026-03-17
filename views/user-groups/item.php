<?php

use app\components\ItemObjectWidget;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\UserGroups */

//qtip_ajxhrf="<?= \yii\helpers\Url::to(['/org-phones/ttip','id'=>$model->id])
//qtip_class="qtip-wide"
//	<?= Html::a('<span class="fas fa-pencil-alt"/>',['/places/update','id'=>$model->id])

if (is_object($model)) {
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'name'=>$model->name,
		'ttipUrl'=>Url::to(['/user-groups/ttip','id'=>$model->id]),
	]);
}
