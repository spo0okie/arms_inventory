<?php

use app\helpers\ArrayHelper;
use app\helpers\FieldsHelper;
use app\models\Places;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model app\models\Places */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

$places= Places::fetchNames();
$places['']='';
asort($places);
if ($model->parent_id)

?>

<div class="places-form">

    <?php $form = ActiveForm::begin(); ?>

	<div class="row">
		<div class="col-5">
			<?= $form->field($model, 'parent_id')->widget(Select2::class, [
				'data' => Places::fetchNames(),
				'options' => ['placeholder' => 'Выберите родительское помещение',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>
		<div class="col-5">
			<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
		</div>
		<div class="col-2">
			<?= $form->field($model, 'short')->textInput(['maxlength' => true]) ?>
		</div>
	</div>


	<div class="row">
		<div class="col-8">
			<?= $form->field($model, 'addr')->textInput(['maxlength' => true,'placeholder'=>($model->parent_id)?$model->parent->addr:'']) ?>
		</div>
		<div class="col-2">
			<?= $form->field($model, 'prefix')->textInput(['maxlength' => true,'placeholder'=>($model->parent_id)?$model->parent->prefix:'']) ?>
		</div>
		<div class="col-2">
			<?= $form->field($model, 'map_id')->widget(Select2::class, [
				'data' => ArrayHelper::map($model->scans,'id',function ($item) {
					return Html::img($item->thumbUrl,['style'=>'height:20px;']);
				}),
				'options' => ['placeholder' => 'Карта отсутствует',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false,
					'escapeMarkup'=> new JsExpression("function(m) { return m; }")
				]
			]) ?>
		</div>
	</div>



	<?= FieldsHelper::TextAutoresizeField($form,$model,'comment',['lines'=>3]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

	<p class="mt-3">
		<span onclick="$('#places_advanced_settings').toggle()" class="href">Дополнительные свойства</span>
	</p>
	<div id="places_advanced_settings" style="display: none">
		<?= FieldsHelper::TextAutoresizeField($form,$model,'map',['lines'=>3]) ?>
	</div>
	
    <?php ActiveForm::end(); ?>

</div>
