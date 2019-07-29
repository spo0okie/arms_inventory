<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\Arms */
/* @var $form yii\widgets\ActiveForm */

/*
$users=\app\models\Users::listWorking();
$users['']='- сотрудник не назначен -';
asort($users);
*/

$places=\app\models\Places::fetchNames();
$places['']='- помещение не назначено -';
asort($places);

if ($model->isNewRecord) {
    $formActionSave=\yii\helpers\Url::to(['arms/create','return'=>'previous']);
    $formActionApply=\yii\helpers\Url::to(['arms/create-apply']);
} else {
	$formActionSave=\yii\helpers\Url::to(['arms/update','id'=>$model->id,'return'=>'previous']);
	$formActionApply=\yii\helpers\Url::to(['arms/update-apply','id'=>$model->id]);
}

?>

<div class="arms-form">

    <?php $form = ActiveForm::begin([
        'enableClientValidation' => false,
        'enableAjaxValidation' => true,
        'id' => 'arms-form',
        'validationUrl' => $model->isNewRecord?['arms/validate']:['arms/validate','id'=>$model->id],
    ]); ?>

    <div class="row">
        <div class="col-md-3" >
		    <?= $form->field($model, 'num')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-3" >
		    <?= $form->field($model, 'inv_num')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-3" >
	        <?= $form->field($model, 'sn')->textInput(['maxlength' => true]) ?>
        </div>
        <div class="col-md-3" >
		    <?= $form->field($model, 'is_server')->checkbox() ?>
        </div>

	    <?php
        $js = <<<JS
        $("#arms-is_server").on('change',function(){
        
            // to submit only if the checkbox is checked otherwise 
            // you can remove the check and just use the submit statement
            if($(this).is(':checked')){
                $("#arms-user_settings, #arms-responsible_settings_block").hide();
                $("#arms-server_hint_block").show();
            } else {
                $("#arms-user_settings, #arms-responsible_settings_block").show();
                $("#arms-server_hint_block").hide();
            }
        });
JS;

	    $this->registerJs($js, \yii\web\View::POS_READY);
	    ?>
    </div>
	<?php if ($model->isNewRecord) { ?>
        <p>
            Свободные номера АРМов:
			<?php
			$nums=\app\models\Arms::fetchNextNums();
			foreach ($nums as $num) { ?>
                <span class="arms-num-selector" onclick="$('#arms-num').val('<?= $num ?>')">
                    <?= $num ?>
                </span>
			<?php }
			?>
        </p>
	<?php } ?>

    <div class="row">
        <div class="col-md-4" >
	        <?= $form->field($model, 'model_id')->widget(Select2::className(), [
		        'data' => \app\models\TechModels::fetchPCs(),
		        'options' => ['placeholder' => 'Выберите модель',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => false,
			        'multiple' => false
		        ]
	        ]) ?>
        </div>
        <div class="col-md-3" >
		    <?= $form->field($model, 'comp_id')->dropDownList(\yii\helpers\ArrayHelper::map($model->comps,'id','name')) ?>
            <p id="os_attach_selector">
			    <?= $this->render('compsAttach',['arm_id'=>$model->id,'user_id'=>$model->user_id]); ?>
            </p>
        </div>
        <div class="col-md-2" >
		    <?= $form->field($model, 'state_id')->widget(Select2::className(), [
			    'data' => \app\models\TechStates::fetchNames(),
			    'options' => ['placeholder' => 'Статус рабочего места',],
			    'toggleAllSettings'=>['selectLabel'=>null],
			    'pluginOptions' => [
				    'allowClear' => false,
				    'multiple' => false
			    ]
		    ]) ?>
        </div>
        <div class="col-md-3" >
		    <?= $form->field($model, 'comment')->textInput(['maxlength'=>true]) ?>
        </div>
    </div>


    <div class="row" id="arms-user_settings" <?= $model->is_server?'style="display:none"':'' ?>>
        <div class="col-md-6" >
	        <?= $form->field($model, 'user_id')->widget(Select2::className(), [
		        'data' => \app\models\Users::fetchWorking(),
		        'options' => ['placeholder' => 'сотрудник не назначен',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => true,
			        'multiple' => false
		        ]
	        ]) ?>

        </div>
        <div class="col-md-6" >
	        <?= $form->field($model, 'head_id')->widget(Select2::className(), [
		        'data' => \app\models\Users::fetchWorking(),
		        'options' => ['placeholder' => 'сотрудник не назначен',],
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
	        <?= $form->field($model, 'it_staff_id')->widget(Select2::className(), [
		        'data' => \app\models\Users::fetchWorking(),
		        'options' => ['placeholder' => 'сотрудник не назначен',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => true,
			        'multiple' => false
		        ]
	        ]) ?>
        </div>
        <div class="col-md-6" id="arms-responsible_settings_block" <?= $model->is_server?'style="display:none"':'' ?>>
            <?= $form->field($model, 'responsible_id')->widget(Select2::className(), [
                'data' => \app\models\Users::fetchWorking(),
                'options' => ['placeholder' => 'ответственным является сотрудник ИТ','id'=>'arms-responsible_settings'],
                'toggleAllSettings'=>['selectLabel'=>null],
                'pluginOptions' => [
                    'allowClear' => true,
                    'multiple' => false
                ]
            ]) ?>
        </div>
        <div class="col-md-6" id="arms-server_hint_block" <?= $model->is_server?'':'style="display:none"' ?>>
            <br /><span class="glyphicon glyphicon-exclamation-sign"></span> У сервера нет пользователя или иных ответственных кроме сотрудников ИТ отдела. У него есть сервисы, которые на нем вертятся.
        </div>
    </div>

    <?= $form->field($model, 'places_id')->dropDownList($places) ?>





	<?= $form->field($model, 'contracts_ids')->widget(Select2::className(), [
		'data' => \app\models\Contracts::fetchNames(),
		'options' => ['placeholder' => 'Выберите документы',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>

	<?= $form->field($model, 'lic_items_ids')->widget(Select2::className(), [
		'data' => \app\models\LicItems::fetchNames(),
		'options' => ['placeholder' => 'Выберите лицензии',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>

	<?= $form->field($model, 'lic_groups_ids')->widget(Select2::className(), [
		'data' => \app\models\LicGroups::fetchNames(),
		'options' => ['placeholder' => 'Назначте группы',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>

    <div class="form-group">
	    <?= Html::submitButton('Применить', ['class' => 'btn btn-success','name' => 'apply','formaction' => $formActionApply,]) ?>
	    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success','name' => 'save', 'formaction' => $formActionSave,]) ?>
    </div>

	<?= $form->field($model, 'history')->textarea(['rows' => max(10,count(explode("\n",$model->history)))]) ?>
    <?php $this->registerJs("$('#arms-history').autoResize();"); ?>

    <?php ActiveForm::end(); ?>

</div>
