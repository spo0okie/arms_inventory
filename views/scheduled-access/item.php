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
if (!isset($empty)) $empty='- расписание отсутствует -';

if (!empty($model)) {
	if (!isset($name)) $name=$model->name;
	?>
	
	<span class="schedules-item"
		  qtip_ajxhrf="<?= \yii\helpers\Url::to(['scheduled-access/ttip','id'=>$model->id]) ?>"
	>
		<?=  Html::a($name,['scheduled-access/view','id'=>$model->id]) ?>
		<?=  $static_view?'':Html::a('<span class="fas fa-pencil-alt"></span>',['scheduled-access/update','id'=>$model->id,'return'=>'previous']) ?>
	</span>
<?php } else {
	echo $empty;
}
?>