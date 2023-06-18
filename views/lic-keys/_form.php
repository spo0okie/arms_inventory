<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\LicKeys */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

$js = '
    //меняем подсказку выбора арм в при смене закупки
    function fetchArmsFromDocs(){
        id=$("#lickeys-lic_items_id").val();
        console.log(id);
        $.ajax({url: "/web/lic-items/hint-arms?form=lickeys&id="+id})
            .done(function(data) {$("#arms_id-hint").html(data);})
            .fail(function () {console.log("Ошибка получения данных!")});
        }';
$this->registerJs($js, yii\web\View::POS_BEGIN);

?>

<div class="lic-keys-form">

	<?php $form = ActiveForm::begin([
		'action' => $model->isNewRecord?\yii\helpers\Url::to(['lic-keys/create']):\yii\helpers\Url::to(['lic-keys/update','id'=>$model->id]),
	]); ?>

	<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'lic_items_id', [
		'data' => \app\models\LicItems::fetchNames(),
		'options' => [
            'placeholder' => 'Выберите закупку',
			'onchange' => 'fetchArmsFromDocs();'
        ],
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'allowClear' => false,
		]
	]) ?>

	<?= \app\helpers\FieldsHelper::TextAutoresizeField($form, $model, 'key_text',['lines' => 1,]) ?>

	<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'arms_ids', [
		'data' => \app\models\Techs::fetchArmNames(),
		'options' => ['placeholder' => 'Выберите АРМы',],
		'classicHint' => \app\models\Contracts::fetchArmsHint(is_object($model->licItem)?$model->licItem->contracts_ids:null ,'lickeys'),
		'classicHintOptions'=> ['id'=>'arms_id-hint'],
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'allowClear' => true,
			'multiple' => true
		],
		'pluginEvents' =>['change'=>'function(){$("#linkComment").show("highlight",1600)}'],
	]); ?>

	<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'users_ids', [
		'data' => \app\models\Users::fetchWorking(),
		'options' => ['placeholder' => 'Выберите пользователей',],
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'allowClear' => true,
			'multiple' => true
		],
		'pluginEvents' =>['change'=>'function(){$("#linkComment").show("highlight",1600)}'],
	]) ?>
	
	<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'comps_ids', [
		'data' => \app\models\Comps::fetchNames(),
		'options' => ['placeholder' => 'Выберите операционные системы',],
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'allowClear' => true,
			'multiple' => true
		],
		'pluginEvents' =>['change'=>'function(){$("#linkComment").show("highlight",1600)}'],
	]) ?>
	
	<?= $form->field($model, 'linkComment',['options'=>['style'=>'display:none','id'=>'linkComment']])->textInput(['maxlength' => true]) ?>



	<?= \app\helpers\FieldsHelper::TextAutoresizeField($form, $model, 'comment',['lines' => 4,]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
