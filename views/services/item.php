<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Services */


if (is_object($model)) {
	$icon=$model->is_end_user?
		'<span class="glyphicon glyphicon-user"></span>':
		'<span class="glyphicon glyphicon-cog"></span>';
	$icon='<small>'.$icon.'</small>&nbsp;';
	?>
	<span class="services-item" qtip_ajxhrf="<?= \yii\helpers\Url::to(['/services/ttip','id'=>$model->id]) ?>">
		<?= Html::a($icon.$model->name,['/services/view','id'=>$model->id]	) ?>
    	<?= Html::a('<span class="glyphicon glyphicon-pencil"></span>',['/services/update','id'=>$model->id]) ?>
	</span>
<?php }