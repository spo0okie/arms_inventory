<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var app\models\Attaches $model */
/** @var yii\widgets\ActiveForm $form */
/** @var \app\models\ArmsModel $linkModel */
/** @var string $link */

if (!isset($active)) $active=false;

$switchCode=<<<JS
	$('#add-attaches-form-button, #add-attaches-form-form').toggle();
	return false;
JS;


echo Html::a('Загрузить','#',[
	'onclick'=>$switchCode,
	'id'=>'add-attaches-form-button',
	'style'=>$active?'display:none':null,
]);

?>

<div class="add-attaches-form text-center" id="add-attaches-form-form" <?= !$active?'style="display:none"':'' ?>>

    <?php $form = ActiveForm::begin(['action'=>[
		'attaches/create',
		'return'=>'previous',
		'Attaches['.$link.']'=>$linkModel->id
	]]); ?>

	<div class="input-group">
		<?= $form->field($model, 'uploadedFile')->fileInput(['class'=>'form-control'])->label(false) ?>
		<div class="input-group-append">
			<?= Html::submitButton('Загрузить', ['class' => 'btn btn-success']) ?>
		</div>
		<div class="input-group-append">
			<?= Html::Button('Отмена', ['class' => 'btn btn-danger','onclick'=>$switchCode]) ?>
		</div>
	</div>

    <?php ActiveForm::end(); ?>

</div>
