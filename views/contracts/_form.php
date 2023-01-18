<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use kartik\date\DatePicker;
use kartik\file\FileInput;


/* @var $this yii\web\View */
/* @var $model app\models\Contracts */
/* @var $form yii\widgets\ActiveForm */

if (!isset($modalParent)) $modalParent=null;
$js = <<<JS
    //меняем подсказку выбора родителя при смене списка контрагентов
    function fetchContractsFromPartners(){
        let partners=$("#contracts-partners_ids").val();
        console.log(partners);
        $.ajax({url: "/web/contracts/hint-parent?form=contracts&ids="+partners})
            .done(function(data){
            	$("#parent_id-hint").html(data);
            })
            .fail(function () {
                console.log("Ошибка получения данных parents!")
            });
        }
JS;
$this->registerJs($js, yii\web\View::POS_BEGIN);

?>

<div class="contracts-form">

    <input type="hidden" value="<?= $model->id ?>" id="contract_form_model_id" />
    <input type="hidden" value="" id="contract_form_save_mode" />

    <?php $form = ActiveForm::begin([
        'id'=>'contracts-edit-form',
	    'enableClientValidation' => false,
	    'enableAjaxValidation' => true,
	    'validateOnBlur' => true,
	    'validateOnChange' => true,
	    'validateOnSubmit' => true,
        'validationUrl' => $model->isNewRecord?['contracts/validate']:['contracts/validate','id'=>$model->id],
	    'options' => ['enctype' => 'multipart/form-data'],
        'action' => $model->isNewRecord?\yii\helpers\Url::to(['contracts/create']):\yii\helpers\Url::to(['contracts/update','id'=>$model->id]),
    ]); ?>

    <?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'name') ?>

    <div class="row">
        <div class="col-md-4" >
		    <?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'state_id',[
			    'data' => \app\models\ContractsStates::fetchNames(),
			    'options' => ['placeholder' => 'Выберите статус документа',],
			    'pluginOptions' => ['dropdownParent' => $modalParent,'allowClear' => true,],
		    ]) ?>
        </div>
        <div class="col-md-4" >
            <?= \app\helpers\FieldsHelper::DateField($form,$model, 'date'); ?>
        </div>
        <div class="col-md-4" >
			<?= \app\helpers\FieldsHelper::DateField($form,$model, 'end_date'); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-9" >
            <?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'parent_id', [
                'data' => array_diff_key(\app\models\Contracts::fetchNames(),[$model->id=>$model->id]),
                'options' => ['placeholder' => 'Основной документ не назначен',],
                'pluginOptions' => ['dropdownParent' => $modalParent,],
				'classicHint'=>\app\models\Contracts::fetchParentHint($model->partners_ids,'contracts'),
				'classicHintOptions'=>['id'=>'parent_id-hint']
            ]) ?>
        </div>
        <div class="col-md-3 d-inline-flex" >
			<span class="pt-3">
				<br>
            	<?= \app\helpers\FieldsHelper::CheckboxField($form,$model,'is_successor') ?>
			</span>
        </div>
    </div>

	<div class="row">
		<div class="col-md-7">
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'partners_ids', [
				'data' => \app\models\Partners::fetchNames(),
				'options' => ['onchange' => 'fetchContractsFromPartners();'],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'multiple' => true
				],
			]) ?>
		</div>
		<div class="col-md-1">
			<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'currency_id', [
				'data' => \app\models\Currency::fetchNames(),
				'options' => ['placeholder' => 'RUR'],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => false,
				],
			]) ?>
		</div>
		<div class="col-md-3">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model,'total') ?>
		</div>
		<div class="col-md-1">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model,'charge',[
				'classicHint'=>\app\models\Contracts::chargeCalcHtml('contracts','total','charge')
			]) ?>
		</div>
	</div>

	<?php

	if (!$model->isNewRecord)
		$scans=$model->scans;
	else
		$scans=[];
	$preview=[];
	$config=[];
	foreach ($scans as $scan) {
		/**
		 * @var $scan \app\models\Scans
		 */
	    $preview[]=$scan->thumbUrl;
	    $config[]=(object)[
            'caption'=>$scan->noidxFname,
            'downloadUrl'=>$scan->fullFname,
            'size'=>$scan->fileSize,
            'key'=>$scan->id
        ];
	}
	
	//var_dump($scans);
	//var_dump($preview);

    try {
		echo FileInput::widget([
			'name' => 'Scans[scanFile]',
			'language' => 'ru',
			'options' => [
				'accept' => 'image/*',
				'multiple' => true,
				'id' => 'contract_form_scans_input',
			],
			'pluginOptions' => [
                'initialPreview' => $preview,
				'initialPreviewAsData' => true,
				'initialPreviewConfig' => $config,
				'uploadUrl' => \yii\helpers\Url::to(['scans/create']),
				'deleteUrl' => \yii\helpers\Url::to(['scans/delete']),
				'uploadExtraData' => new \yii\web\JsExpression('function(previewId, index) {
	                return {"Scans[contracts_id]" : $(\'#contract_form_model_id\').val()};
	            }'),
			],
			'pluginEvents' => [
				'change' => 'function(event) {
				    console.log("f_Input.change");
				    scansFileListChange($(this)[0]);
                }',
				'filebatchselected' => 'function(event,files) {
				    console.log("f_Input.filebatchselected");
				    scansFileListChange($(this)[0]);
                }',
                'filebatchuploadcomplete' => 'function(event, files, extra) {
                    contractFormAfterScansUpload();
                }',
			]
		]);
	} catch (Exception $e) {
	} ?>

    <br/>

	<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'arms_ids', [
		'data' => \app\models\Arms::fetchNames(),
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'multiple' => true
		],
	]) ?>
	<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'techs_ids', [
		'data' => \app\models\Techs::fetchNames(),
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'multiple' => true
		],
	]) ?>
	<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'lics_ids', [
		'data' => \app\models\LicItems::fetchNames(),
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'multiple' => true
		],
	]) ?>
	
	<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'services_ids', [
		'data' => \app\models\Services::fetchNames(),
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'multiple' => true
		],
	]) ?>

	<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model,'comment',['lines' => 4,]) ?>

	<?php ActiveForm::end(); ?>


	<?php
	//у нас
	$js = <<<JS
$('#contracts-edit-form').on('beforeSubmit', function(){
    $.ajax({
        type: 'POST',
        url: $(this).attr('action'),
        data: $(this).serializeArray()
    })
    .done(function(data) {contractFromApplyChanges(data)})
    .fail(function () {alert('Ошибка отправки данных!')});
    return false; // prevent default form submission
});
JS;

$this->registerJs($js);
?>


    <div class="form-group">
		<?= Html::Button('Применить', [
			'class' => 'btn btn-success',
			//'id' => 'contracts-edit-form-apply',
			//'name' => 'apply',
			'onclick'=>"$('#contracts-edit-form').attr('data-submit-mode','apply').submit()",
		]) ?>

		<?= Html::Button('Сохранить', [
			'class' => 'btn btn-success',
			//'id' => 'contracts-edit-form-save',
			//'name' => 'save',
			'onclick'=>"$('#contracts-edit-form').attr('data-submit-mode','save').submit()",
		]) ?>
    </div>
</div>

