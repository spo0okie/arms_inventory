<?php
/**
 * Рендер элемента расписания
 * Created by PhpStorm.
 * User: reviakin.a
 * Date: 18.10.2020
 * Time: 17:01
 */

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */

if (!isset($static_view)) $static_view=false;

if (!empty($model)) {?>
	
	<span class="schedules-item"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['schedules/ttip','id'=>$model->id]) ?>"
	>
		<?=  Html::a($model->name,['schedules/view','id'=>$model->id]) ?>
		<?=  $static_view?'':Html::a('<span class="glyphicon glyphicon-pencil"></span>',['schedules/update','id'=>$model->id,'return'=>'previous']) ?>
	</span>
<?php } else {
	echo '- расписание отсутствует -';
}
?>