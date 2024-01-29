<?php

use app\helpers\FieldsHelper;
use app\models\Contracts;
use app\models\ContractsStates;
use app\models\Currency;
use app\models\LicItems;
use app\models\Partners;
use app\models\Scans;
use app\models\Services;
use app\models\Techs;
use app\models\Users;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\file\FileInput;
use yii\helpers\Url;
use yii\web\JsExpression;


/* @var $this yii\web\View */
/* @var $model app\models\Contracts */
/* @var $form yii\widgets\ActiveForm */

if (!isset($modalParent)) $modalParent=null;
$js = <<<JS
    //меняем подсказку выбора родителя при смене списка контрагентов
    // noinspection JSUnusedLocalSymbols
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
        'action' => $model->isNewRecord? Url::to(['contracts/create']): Url::to(['contracts/update','id'=>$model->id]),
    ]); ?>
	
	<?= Yii::$app->params['docs.name.instruction']?Yii::$app->params['docs.name.instruction']:'' ?>

	<div class="row">
		<div class="col-<?= Yii::$app->params['docs.pay_id.enable']?'10':'12' ?>">
			<?= FieldsHelper::TextInputField($form,$model, 'name') ?>
		</div>
		<?php if (Yii::$app->params['docs.pay_id.enable']) { ?>
			<div class="col-2">
				<?= FieldsHelper::TextInputField($form,$model, 'pay_id') ?>
			</div>
		<?php } ?>
	</div>

    <div class="row">
        <div class="col-md-2" >
		    <?= FieldsHelper::Select2Field($form,$model, 'state_id',[
			    'data' => ContractsStates::fetchNames(),
			    'options' => ['placeholder' => 'Выберите статус документа',],
			    'pluginOptions' => ['dropdownParent' => $modalParent,'allowClear' => true,],
		    ]) ?>
        </div>
        <div class="col-md-2" >
            <?= FieldsHelper::DateField($form,$model, 'date'); ?>
        </div>
        <div class="col-md-2" >
			<?= FieldsHelper::DateField($form,$model, 'end_date'); ?>
        </div>
		<div class="col-md-6">
			<?= FieldsHelper::Select2Field($form,$model, 'users_ids', [
				'data' => Users::fetchNames(),
				'hintModel'=>'Users',
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'multiple' => true
				],
			]) ?>
		</div>
    </div>

    <div class="row">
        <div class="col-md-8" >
            <?= FieldsHelper::Select2Field($form,$model, 'parent_id', [
                'data' => array_diff_key(Contracts::fetchNames(),[$model->id=>$model->id]),
				'hintModel'=>'Contracts',
                'options' => ['placeholder' => 'Основной документ не назначен',],
                'pluginOptions' => ['dropdownParent' => $modalParent,],
				'classicHint'=> Contracts::fetchParentHint($model->partners_ids,'contracts'),
				'classicHintOptions'=>['id'=>'parent_id-hint']
            ]) ?>
        </div>
		<div class="col-md-1 mt-3" >
			<?= FieldsHelper::CheckboxField($form,$model,'is_successor') ?>
		</div>
		<div class="col-md-1" >
			<?= FieldsHelper::TextInputField($form,$model,'techs_delivery') ?>
		</div>
		<div class="col-md-1" >
			<?= FieldsHelper::TextInputField($form,$model,'materials_delivery') ?>
		</div>
		<div class="col-md-1" >
			<?= FieldsHelper::TextInputField($form,$model,'lics_delivery') ?>
		</div>
    </div>

	<div class="row">
		<div class="col-md-7">
			<?= FieldsHelper::Select2Field($form,$model, 'partners_ids', [
				'data' => Partners::fetchNames(),
				'options' => ['onchange' => 'fetchContractsFromPartners();'],
				'itemsHintsUrl'=>'auto',
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'multiple' => true
				],
			]) ?>
		</div>
		<div class="col-md-1">
			<?= FieldsHelper::Select2Field($form,$model, 'currency_id', [
				'data' => Currency::fetchNames(),
				'options' => ['placeholder' => 'RUR'],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => false,
				],
			]) ?>
		</div>
		<div class="col-md-3">
			<?= FieldsHelper::TextInputField($form,$model,'total') ?>
		</div>
		<div class="col-md-1">
			<?= FieldsHelper::TextInputField($form,$model,'charge',[
				'classicHint'=> Contracts::chargeCalcHtml('contracts','total','charge')
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
		 * @var $scan Scans
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
				'overwriteInitial' => false,
				'uploadUrl' => Url::to(['scans/create']),
				'deleteUrl' => Url::to(['scans/delete']),
				'uploadExtraData' => new JsExpression('function(previewId, index) {
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

	<?= FieldsHelper::Select2Field($form,$model, 'techs_ids', [
		'data' => Techs::fetchNames(),
		'hintModel'=>'Techs',
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'multiple' => true
		],
	]) ?>
	<?= FieldsHelper::Select2Field($form,$model, 'lics_ids', [
		'data' => LicItems::fetchNames(),
		'hintModel'=>'LicItems',
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'multiple' => true
		],
	]) ?>
	
	<?= FieldsHelper::Select2Field($form,$model, 'services_ids', [
		'data' => Services::fetchNames(),
		'hintModel'=>'Services',
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'multiple' => true
		],
	]) ?>

	<?= FieldsHelper::TextAutoresizeField($form,$model,'comment',['lines' => 4,]) ?>

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

