<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\SoftSearch */
/* @var $form yii\widgets\ActiveForm */
/* @var $addItems string */
/* @var $items array */

if (!isset($modalParent)) $modalParent=null;
if (count($items)) {
	asort($items);
?>

	<div class="soft-search">
	
		<?php $form = ActiveForm::begin([
			//'action' => ['soft/update','return'=>'previous','items'=>$addItems],
			'enableClientValidation' => false,
			'enableAjaxValidation' => false,
			'enableClientScript' => false,
			'method' => 'get',
			'id'=>'select-soft-update-form-'.rand(),
			'options'=>[
				'onsubmit'=>'window.location.replace("'.\yii\helpers\Url::to([
					'soft/update',
					'return'=>'previous',
					'items'=>$addItems
				]).'&id="+$("#select-soft-update-id").val()); return false;',
			],
		]); ?>
		
		<?= Select2::widget([
			'id'=>'select-soft-update-id',
			'name'=>'id',
			'data'=>$items,
			'options'=>[
				'placeholder' => 'Выберите продукт',
			],
			'pluginOptions' => [
				'dropdownParent' => $modalParent,
			],
		]) ?>
		
		<div class="form-group">
			<?= Html::submitButton('Добавить', ['class' => 'btn btn-primary']) ?>
		</div>
	
		<?php ActiveForm::end(); ?>
		
	</div>
<?php } else { ?>
	<div class="alert alert-striped">
		У этого производителя еще не заведено ни одного продукта.
		Сначала создайте один, потом к нему можно будет добавлять варианты написания
	</div>
<?php }