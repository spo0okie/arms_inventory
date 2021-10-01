<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
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

	$no_specs_hint=\app\models\TechModels::$no_specs_hint;
    $js = <<<JS
    //меняем подсказку выбора арм в при смене списка документов
    function fetchArmsFromDocs(){
        docs=$("#techs-contracts_ids").val();
        console.log(docs);
        $.ajax({url: "/web/contracts/hint-arms?form=techs&ids="+docs})
        .done(function(data) {
            $("#arms_id-hint").html(data);
        })
        .fail(function () {console.log("Ошибка получения данных!")});
    }

    
    //меняем подсказки для разных типов оборудования
    function fetchCommentFromModel(){
        model_id=$("#techs-model_id").val();
        console.log(model_id);
        $.ajax({url: "/web/tech-models/hint-comment?id="+model_id})
        .done(function(data) {
            $('label[for="techs-comment"]').text(data['name']);
            $("#comment-hint").html(data['hint']);
        })
        .fail(function () {console.log("Ошибка получения данных!")});
		$.ajax({url: "/web/tech-models/hint-template?id="+model_id})
			.done(function(data) {
				if (data=="$no_specs_hint") {
					$("#techs-specs_settings").hide();
				} else {
					$("#techs-hint").html(data);
					$("#techs-specs_settings").show();
				}
			})
			.fail(function () {console.log("Ошибка получения данных!")});
		$.ajax({url: "/web/tech-models/hint-description?id="+model_id})
			.done(function(data) {
				$("#model-hint").html(data);
			})
			.fail(function () {console.log("Ошибка получения данных!")});
    }
    
JS;
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
	    'action' => $model->isNewRecord?\yii\helpers\Url::to(['techs/create']):\yii\helpers\Url::to(['techs/update','id'=>$model->id,'return'=>'previous']),
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
        <div class="col-md-6" >
			<?= $form->field($model, 'model_id')->widget(Select2::className(), [
				'data' => $techModels,
				'options' => [
			        'placeholder' => 'Выберите модель',
					'onchange' => 'fetchCommentFromModel();'
                ],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => false,
					'multiple' => false
				]
			]) ?>
        </div>
        <div class="col-md-3" >
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
        <div class="col-md-3" >
		    <?= $form->field($model, 'comment')->textInput(['maxlength' => true])
                ->hint(\app\models\TechModels::fetchTypeComment($model->model_id)['hint'],['id'=>'comment-hint'])
                ->label(app\models\TechModels::fetchTypeComment($model->model_id)['name']) ?>
        </div>
    </div>

	<div class="row " id="techs-specs_settings"
		<?= (is_object($model) && is_object($model->model) && $model->model->individual_specs)?'':'style="display:none"' ?>
	>
		<div class="col-md-4" >
			<?= $form->field($model, 'specs')->textarea(['rows' => max(6,count(explode("\n",$model->history)))]) ?>
		</div>
		<div class="col-md-4" >
			<label class="control-label" >
				Подсказка для заполнения спеки
			</label>
			<br />
			<div id="specs-hint" class="hint-block">
				<?php
				if(is_object($model) && is_object($model->model))
					echo Yii::$app->formatter->asNtext($model->model->type->comment)
				?>
			</div>
		</div>
		<div class="col-md-4" >
			<label class="control-label" >
				Описание модели
			</label>

			<div id="model-hint" class="hint-block">
				Эти данные не нужно вносить в индивидуальную спеку:<br />
				<?php
				if(is_object($model) && is_object($model->model))
					echo Yii::$app->formatter->asNtext($model->model->comment)
				?>
			</div>
		</div>
	</div>
	
	
	
    <div class="row">
        <div class="col-md-6" >
			<?= \app\components\TextAutoResizeWidget::widget([
				'form' => $form,
				'model' => $model,
				'attribute' => 'ip',
				'lines' => 1,
			]) ?>


		</div>
        <div class="col-md-6" >
			<?= \app\components\TextAutoResizeWidget::widget([
				'form' => $form,
				'model' => $model,
				'attribute' => 'mac',
				'lines' => 1,
			]) ?>
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
	
	
	
	<?= \app\components\TextAutoResizeWidget::widget([
		'form' => $form,
		'model' => $model,
		'attribute' => 'url',
		'lines' => 3,
	]) ?>
	
	
	<?= \app\components\TextAutoResizeWidget::widget([
		'form' => $form,
		'model' => $model,
		'attribute' => 'history',
		'lines' => 4,
	]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
