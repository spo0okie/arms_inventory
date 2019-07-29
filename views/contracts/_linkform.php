<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */

$items=\app\models\Contracts::fetchNames();
asort($items);

?>

<div class="contract-search">

	<?php $form = ActiveForm::begin(['id'=>'contracts-link-form']); ?>


	<?= \kartik\select2\Select2::widget([
		'name'=>'contracts_id',
		'data'=>$items
	]) ?>

	<div class="form-group">
		<?= Html::submitButton('Привязать', ['class' => 'btn btn-primary']) ?>
	</div>

	<?php ActiveForm::end(); ?>

</div>
