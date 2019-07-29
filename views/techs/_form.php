<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */
/* @var $form yii\widgets\ActiveForm */

$hidden=' style="display:none" ';
switch (Yii::$app->request->get('type')) {
	case 'phone':
		$techModels=\app\models\TechModels::fetchPhones();
		break;
    case 'pc':
	    $techModels=\app\models\TechModels::fetchPCs();
	    break;
    default:
	    $techModels=\app\models\TechModels::fetchNames();
        break;
}

    $js = '
    //меняем подсказку выбора арм в при смене списка документов
    function fetchArmsFromDocs(){
        docs=$("#techs-contracts_ids").val();
        console.log(docs);
        $.ajax({url: "/web/contracts/hint-arms?form=techs&ids="+docs})
            .done(function(data) {$("#arms_id-hint").html(data);})
            .fail(function () {console.log("Ошибка получения данных!")});
        }';
    $this->registerJs($js, yii\web\View::POS_BEGIN);
?>

<div class="techs-form">

    <?php $form = ActiveForm::begin([
	    'id'=>'techs-edit-form',
	    'enableClientValidation' => false,
	    'enableAjaxValidation' => true,
	    'validateOnBlur' => true,
	    'validateOnChange' => true,
	    'validateOnSubmit' => true,
	    'validationUrl' => $model->isNewRecord?['techs/validate']:['techs/validate','id'=>$model->id],
	    //'options' => ['enctype' => 'multipart/form-data'],
	    'action' => $model->isNewRecord?\yii\helpers\Url::to(['techs/create']):\yii\helpers\Url::to(['techs/update','id'=>$model->id]),
    ]); ?>

    <div class="row">
        <div class="col-md-4" >
			<?= $form->field($model, 'num')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-4" >
			<?= $form->field($model, 'inv_num')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-4" >
			<?= $form->field($model, 'sn')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8" >
			<?= $form->field($model, 'model_id')->widget(Select2::className(), [
				'data' => $techModels,
				'options' => ['placeholder' => 'Выберите модель',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => false,
					'multiple' => false
				]
			]) ?>
        </div>
        <div class="col-md-4" >
			<?= $form->field($model, 'state_id')->widget(Select2::className(), [
				'data' => \app\models\TechStates::fetchNames(),
				'options' => ['placeholder' => 'Выберите состояние оборудования',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6" >
		    <?= $form->field($model, 'ip')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-6" >
		    <?= $form->field($model, 'mac')->textInput(['maxlength' => true]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6" >
			<?= $form->field($model, 'arms_id')->widget(Select2::className(), [
				'data' => \app\models\Arms::fetchNames(),
				'options' => ['placeholder' => 'Выберите АРМ',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginEvents' =>[
                    'change'=>'function(){
                        if ($("#techs-arms_id").val()) {
                            $("#tech-place-selector, #tech-users-selector").hide();
                            $("#tech-place-arm-hint").show();
                        } else {
                            $("#tech-place-selector, #tech-users-selector").show();
                            $("#tech-place-arm-hint").hide();
                        }
                    }'
                ],
				'pluginOptions' => [
					'allowClear' => true,
					'multiple' => false
				]
			])->hint(\app\models\Contracts::fetchArmsHint($model->contracts_ids,'techs'),['id'=>'arms_id-hint']) ?>
        </div>
        <div class="col-md-6" id="tech-place-selector" <?= ($model->arms_id)?$hidden:'' ?>>
		    <?= $form->field($model, 'places_id')->widget(Select2::className(), [
			    'data' => \app\models\Places::fetchNames(),
			    'options' => ['placeholder' => 'Выберите помещение',],
			    'toggleAllSettings'=>['selectLabel'=>null],
			    'pluginOptions' => [
				    'allowClear' => true,
				    'multiple' => false
			    ]
		    ]) ?>
        </div>
        <div class="col-md-6" id="tech-place-arm-hint" <?= ($model->arms_id)?'':$hidden ?>>
            <br />Когда оборудование прикреплено к АРМ, то место установки и ответственные сотрудники наследуются из этого АРМ
        </div>
    </div>

    <div class="row" id="tech-users-selector" <?= ($model->arms_id)?$hidden:'' ?>>
        <div class="col-md-6" >
	        <?= $form->field($model, 'user_id')->widget(Select2::className(), [
		        'data' => \app\models\Users::fetchWorking(),
		        'options' => ['placeholder' => 'Выберите сотрудника',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => true,
			        'multiple' => false
		        ]
	        ]) ?>

        </div>
        <div class="col-md-6" >
	        <?= $form->field($model, 'it_staff_id')->widget(Select2::className(), [
		        'data' => \app\models\Users::fetchWorking(),
		        'options' => ['placeholder' => 'Выберите сотрудника',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => true,
			        'multiple' => false
		        ]
	        ]) ?>
        </div>
    </div>



	<?php
	$js = <<<JS
//переводим сабмит на голый аякс
$('#techs-model_id, #techs-places_id, #techs-arms_id').on('change', function(){
    $.ajax({
        url: '/web/techs/inv-num?model_id='+
        $('#techs-model_id').val()
        +'&place_id='+
        $('#techs-places_id').val()
        +'&arm_id='+
        $('#techs-arms_id').val(),
        success: function(data) {
            $('#techs-num').val(data);
        }
    });
}); 
JS;

	if ($model->isNewRecord) $this->registerJs($js);
	?>

	<?= $form->field($model, 'contracts_ids')->widget(Select2::className(), [
		'data' => \app\models\Contracts::fetchNames(),
		'options' => [
            'placeholder' => 'Выберите документы',
			'onchange' => 'fetchArmsFromDocs();'
        ],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => true
		]
	])?>



	<?= $form->field($model, 'url')->textarea(['rows' => max(3,count(explode("\n",$model->url)))]) ?>
	<?php $this->registerJs("$('#techs-url').autoResize();"); ?>


	<?= $form->field($model, 'comment')->textarea(['rows' => max(4,count(explode("\n",$model->comment)))]) ?>
	<?php $this->registerJs("$('#techs-comment').autoResize();"); ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
