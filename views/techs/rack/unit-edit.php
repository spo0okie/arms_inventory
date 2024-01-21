<?php

use app\helpers\FieldsHelper;
use app\models\Techs;
use app\models\ui\RackUnitForm;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;


/* @var $this yii\web\View */
/* @var $model app\models\Techs */
/* @var $rackUnitForm RackUnitForm */
/* @var $unit int */
/* @var $front boolean */
if (!isset($modalParent)) $modalParent=null;

$title=$model->num . ' / Unit ' . $unit;

if (
	($model->model->front_rack_two_sided)	//если передняя двусторонняя
||
	($model->model->back_rack_two_sided)	//если задняя двусторонняя
||
	($model->model->contain_front_rack && $model->model->contain_back_rack)	//или есть обе
) {
	$title.=' (' . ($front?'спереди':'сзади') .')';
}

$hidden='style="display:none"';

?>


<h1><?= $title ?></h1>
<div class="techs-form">

    <?php $form = ActiveForm::begin([
	    'id'=>'techs-rack-unit-form',
	    'enableClientValidation' => false,
	    'enableAjaxValidation' => true,
	    'validateOnBlur' => true,
	    'validateOnChange' => true,
	    'validateOnSubmit' => true,
	    'validationUrl' => ['techs/rack-unit-validate'],
	    //'action' => ['techs/rack-unit','id'=>$model->id,'unit'=>$unit,'front'=>$front],
    ]); ?>
	
	<?= $form->field($rackUnitForm,'tech_rack_id')->hiddenInput()->label(false)->hint(false) ?>
	<?= $form->field($rackUnitForm,'back')->hiddenInput()->label(false)->hint(false) ?>
	<?= $form->field($rackUnitForm,'pos')->hiddenInput()->label(false)->hint(false) ?>

	<div class="row">
		<div class="col-6">
			<?= FieldsHelper::CheckboxField($form,$rackUnitForm,'insert_tech',[
				'onchange'=>'{
						if ($("#rackunitform-insert_tech").is(":checked")) {
							$("#tech-installed-param").show();
							$("#tech-set-label").hide();
							$("#rackunitform-insert_label").prop("checked", false);
						} else {
							$("#tech-installed-param").hide();
						}
					}'
			]) ?>
		</div>
		<div class="col-6">
			<?= FieldsHelper::CheckboxField($form,$rackUnitForm,'insert_label',[
				'onchange'=>'{
						if ($("#rackunitform-insert_label").is(":checked")) {
							$("#tech-set-label").show();
							$("#tech-installed-param").hide();
							$("#rackunitform-insert_tech").prop("checked", false);
						} else {
							$("#tech-set-label").hide();
						}
					}'
			]) ?>
		</div>
	</div>
	<div id="tech-installed-param" <?= $rackUnitForm->insert_tech?'':$hidden ?>>
		<?= FieldsHelper::Select2Field($form,$rackUnitForm,'tech_id',[
			'data'=>Techs::fetchNames()
		]) ?>
		<div class="row">
			<div class="col-md-4" id="tech-installed-pos">
				<?= FieldsHelper::TextInputField($form,$rackUnitForm,'tech_installed_pos') ?>
			</div>
			<div class="col-md-5 mt-4">
				<?= FieldsHelper::CheckboxField($form,$rackUnitForm,'tech_full_length',[
					'onchange'=>'{
						if ($("#rackunitform-tech_full_length").is(":checked")) {
							$("#tech-installed-pos-end").show();
						} else {
							$("#tech-installed-pos-end").hide();
						}
					}'
				]) ?>
			</div>
			<div class="col-md-3" <?= $rackUnitForm->tech_full_length?'':$hidden ?> id="tech-installed-pos-end">
				<?= FieldsHelper::TextInputField($form,$rackUnitForm,'tech_installed_pos_end') ?>
			</div>
			
		</div>
	</div>
	<div id="tech-set-label" <?= $rackUnitForm->insert_label?'':$hidden ?>>
		<?= FieldsHelper::TextInputField($form,$rackUnitForm,'label') ?>
	</div>


	<div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
