<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\UserGroups */

//qtip_ajxhrf="<?= \yii\helpers\Url::to(['/org-phones/ttip','id'=>$model->id])
//qtip_class="qtip-wide"
//	<?= Html::a('<span class="fas fa-pencil-alt"/>',['/places/update','id'=>$model->id])

if (is_object($model)) { ?>
	<span class="user-groups-item" qtip_ajxhrf="<?= \yii\helpers\Url::to(['/user-groups/ttip','id'=>$model->id]) ?>" >
		<?= Html::a($model->name,['/user-groups/view','id'=>$model->id]) ?>
	    <?= Html::a('<span class="fas fa-pencil-alt"/>',['/user-groups/update','id'=>$model->id]) ?>
    </span>
<?php }