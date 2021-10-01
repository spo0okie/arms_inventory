<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use kartik\date\DatePicker;

/* @var $this yii\web\View */
/* @var $model app\models\LicItems */
/* @var $form yii\widgets\ActiveForm */

$js = '
    //меняем подсказку выбора арм в при смене списка документов
    function fetchArmsFromDocs(){
        docs=$("#licitems-contracts_ids").val();
        console.log(docs);
        $.ajax({url: "/web/contracts/hint-arms?form=licitems&ids="+docs})
            .done(function(data) {$("#arms_id-hint").html(data);})
            .fail(function () {console.log("Ошибка получения данных!")});
        }';
$this->registerJs($js, yii\web\View::POS_BEGIN);

?>

<div class="lic-items-form">

    <?php $form = ActiveForm::begin([
	    'action' => $model->isNewRecord?\yii\helpers\Url::to(['lic-items/create']):\yii\helpers\Url::to(['lic-items/update','id'=>$model->id]),
	    'enableClientValidation' => false,
	    'enableAjaxValidation' => true,
	    'validationUrl' => $model->isNewRecord?['lic-items/validate']:['lic-items/validate','id'=>$model->id],

    ]); ?>

	<?= $form->field($model, 'descr')->textInput(['maxlength' => true]) ?>

    <div class="row">
        <div class="col-md-6" >
            <?= $form->field($model, 'lic_group_id')->widget(Select2::className(), [
                'data' => \app\models\LicGroups::fetchNames(),
                'options' => ['placeholder' => 'Выберите группу',],
                'toggleAllSettings'=>['selectLabel'=>null],
                'pluginOptions' => [
                    'allowClear' => false,
                    'multiple' => false
                ]
            ]) ?>
        </div>
        <div class="col-md-6" >
	        <?= $form->field($model, 'count')->textInput() ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6" >
	        <?= $form->field($model, 'active_from')->widget(DatePicker::classname(), [
		        'options' => ['placeholder' => 'Введите дату ...'],
		        'pluginOptions' => [
			        'autoclose'=>true,
					'weekStart' => '1',
			        'format' => 'yyyy-mm-dd'
		        ]
	        ]); ?>
        </div>
        <div class="col-md-6" >
	        <?= $form->field($model, 'active_to')->widget(DatePicker::classname(), [
		        'options' => ['placeholder' => 'Введите дату ...'],
		        'pluginOptions' => [
			        'autoclose'=>true,
					'weekStart' => '1',
			        'format' => 'yyyy-mm-dd'
		        ]
	        ]); ?>
        </div>
    </div>

	<?= $form->field($model, 'arms_ids')->widget(Select2::className(), [
		'data' => \app\models\Arms::fetchNames(),
		'options' => ['placeholder' => 'Выберите АРМы',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => true
		]
	])->hint(\app\models\Contracts::fetchArmsHint($model->contracts_ids,'licitems'),['id'=>'arms_id-hint']) ?>

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
	]) ?>
	
	<?= \app\components\TextAutoResizeWidget::widget([
		'form' => $form,
		'model' => $model,
		'attribute' => 'comment',
		'lines' => 4,
	]) ?>


    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
