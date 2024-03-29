<?php

use app\helpers\FieldsHelper;
use app\models\Segments;
use kartik\markdown\MarkdownEditor;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Networks */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

if (!empty($model->dhcp)) $model->text_dhcp=(new PhpIP\IPv4($model->dhcp))->humanReadable();
if (!empty($model->router)) $model->text_router=(new PhpIP\IPv4($model->router))->humanReadable();


?>

<div class="networks-form">
	
	<?php $form = ActiveForm::begin([
		'enableClientValidation' => false,   //чтобы отключить валидацию через JS в браузере
		'enableAjaxValidation' => true,       //чтобы включить валидацию на сервере ajax запросы
		'id' => 'networks-form',
		'validationUrl' => $model->isNewRecord?['networks/validate']:['networks/validate','id'=>$model->id], //URL валидации на стороне сервера
	]); ?>
	<div class="row">
		<div class="col-md-6">
			<div class="row">
				<div class="col-md-6">
					<?= FieldsHelper::TextInputField($form,$model, 'text_addr') ?>
				</div>
				<div class="col-md-6">
					<?= FieldsHelper::TextInputField($form,$model, 'name') ?>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<?= FieldsHelper::Select2Field($form,$model,  'segments_id', [
						'data' => Segments::fetchNames(),
						'options' => [
							'placeholder' => 'Выберите Сегмент ИТ',
						],
						'pluginOptions' => [
							'dropdownParent' => $modalParent,
							'allowClear' => true,
							'multiple' => false
						]
					]) ?>
				</div>
				<div class="col-md-6">
					<?= FieldsHelper::Select2Field($form,$model, 'vlan_id', [
						'data' => app\models\NetVlans::fetchNames(),
						'options' => [
							'placeholder' => 'Выберите VLAN',
						],
						'pluginOptions' => [
							'dropdownParent' => $modalParent,
							'allowClear' => true,
							'multiple' => false
						]
					]) ?>
				</div>
			</div>
			<?= FieldsHelper::TextInputField($form,$model,  'comment') ?>
		</div>
		<div class="col-md-3">
			<?= FieldsHelper::TextInputField($form,$model,  'text_router') ?>
			<?= FieldsHelper::TextAutoresizeField($form,$model,  'text_dhcp',['lines'=>2]) ?>
		</div>
		<div class="col-md-3">
			<?= FieldsHelper::TextAutoresizeField($form,$model, 'ranges',['lines'=>2]) ?>
			<?= FieldsHelper::TextAutoresizeField($form,$model, 'links') ?>
			<?= FieldsHelper::CheckboxField($form,$model, 'archived') ?>
		</div>
	</div>

	<?= $form->field($model, 'notepad')->widget(MarkdownEditor::class, [
		'showExport'=>false
	]) ?>
    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
