<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use kartik\date\DatePicker;
use kartik\file\FileInput;


/* @var $this yii\web\View */
/* @var $model app\models\Contracts */
/* @var $form yii\widgets\ActiveForm */


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

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <div class="row">
        <div class="col-md-4" >
		    <?= $form->field($model, 'state_id')->widget(Select2::className(), [
			    'data' => \app\models\ContractsStates::fetchNames(),
			    'options' => ['placeholder' => 'Выберите статус документа',],
			    'toggleAllSettings'=>['selectLabel'=>null],
			    'pluginOptions' => [
				    'allowClear' => true,
				    'multiple' => false
			    ]
		    ]) ?>
        </div>
        <div class="col-md-4" >
            <?= $form->field($model, 'date')->widget(DatePicker::classname(), [
                'options' => ['placeholder' => 'Введите дату ...'],
                'pluginOptions' => [
                    'autoclose'=>true,
                    'format' => 'yyyy-mm-dd'
                ]
            ]); ?>
        </div>
        <div class="col-md-4" >
		    <?= $form->field($model, 'end_date')->widget(DatePicker::classname(), [
			    'options' => ['placeholder' => 'Введите дату ...'],
			    'pluginOptions' => [
				    'autoclose'=>true,
				    'format' => 'yyyy-mm-dd'
			    ]
		    ]); ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-9" >
            <?= $form->field($model, 'parent_id')->widget(Select2::className(), [
                'data' => array_diff_key(\app\models\Contracts::fetchNames(),[$model->id=>$model->id]),
                'options' => ['placeholder' => 'Основной документ не назначен',],
                'toggleAllSettings'=>['selectLabel'=>null],
                'pluginOptions' => [
                    'allowClear' => true,
                    'multiple' => false
                ]
            ]) ?>
        </div>
        <div class="col-md-3" >
            <br/>
            <?= $form->field($model,'is_successor')->checkbox() ?>
        </div>
    </div>

	<div class="row">
		<div class="col-md-8">
			<?= $form->field($model, 'partners_ids')->widget(Select2::classname(), [
				'data' => \app\models\Partners::fetchNames(),
				'options' => ['placeholder' => 'Начните набирать название для поиска'],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => true
				],
			]) ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model,'total')->textInput() ?>
		</div>
		<div class="col-md-1">
			<?= $form->field($model,'charge')->textInput()->hint(\app\models\Contracts::chargeCalcHtml('contracts','total','charge')) ?>
		</div>
	</div>

	<?php

	if (!$model->isNewRecord) $scans=$model->scans;
	else $scans=[];
	$preview=[];
	$config=[];
	foreach ($scans as $scan) {
	    $preview[]=$scan->thumbUrl;
	    $config[]=(object)[
            'caption'=>$scan->noidxFname,
            'downloadUrl'=>$scan->fullFname,
            'size'=>$scan->fileSize,
            'key'=>$scan->id
        ];
	}

    try {
		echo FileInput::widget([
			'name' => 'Scans[scanFile]',
			'language' => 'ru',
			'options' => [
				'accept' => 'image/*',
				'id' => 'contract_form_scans_input',
				'multiple' => true
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
                    var file = $(this)[0].files[0];
                    if (file){
                        console.log(file.name);
                        scansFileNameChange(file.name);
                    }
                }',
				'filebatchselected' => 'function(event,files) {
				    console.log("f_Input.filebatchselected");
                    var file = files[0];
                    //var file = $(this)[0].files[0];
                    if (file){
                        console.log(file.name);
                        scansFileNameChange(file.name);
                    }
                }',
                'filebatchuploadcomplete' => 'function(event, files, extra) {
                    contractFormAfterScansUpload();
                }',
			]
		]);
	} catch (Exception $e) {
	} ?>

    <br/>

	<?= $form->field($model, 'arms_ids')->widget(Select2::classname(), [
		'data' => \app\models\Arms::fetchNames(),
		'options' => ['placeholder' => 'Начните набирать название для поиска'],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => true
		],
	]) ?>
	<?= $form->field($model, 'techs_ids')->widget(Select2::classname(), [
		'data' => \app\models\Techs::fetchNames(),
		'options' => ['placeholder' => 'Начните набирать название для поиска'],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => true
		],
	]) ?>
    <?= $form->field($model, 'lics_ids')->widget(Select2::classname(), [
        'data' => \app\models\LicItems::fetchNames(),
        'options' => ['placeholder' => 'Начните набирать название для поиска'],
        'toggleAllSettings'=>['selectLabel'=>null],
        'pluginOptions' => [
            'allowClear' => true,
            'multiple' => true
        ],
    ]) ?>
	
	<?= \app\widgets\TextAutoResizeWidget::widget([
		'form' => $form,
		'model' => $model,
		'attribute' => 'comment',
		'lines' => 4,
	]) ?>

	<?php ActiveForm::end(); ?>



 <?php
$js = <<<JS
//переводим сабмит на голый аякс
$('#contracts-edit-form').on('beforeSubmit', function(){
    var yiiform = $(this);
    //console.log('contracts-submit: '+yiiform.attr('action'));
    $.ajax({
        type: 'POST',
        url: yiiform.attr('action'),
        data: yiiform.serializeArray(),
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
			'id' => 'contracts-edit-form-apply',
			'name' => 'apply',
			'onclick'=>"contractFormSaveClick(this.name)",
		]) ?>

		<?= Html::Button('Сохранить', [
			'class' => 'btn btn-success',
			'id' => 'contracts-edit-form-save',
			'name' => 'save',
			'onclick'=>"contractFormSaveClick(this.name)",
		]) ?>
    </div>
</div>

