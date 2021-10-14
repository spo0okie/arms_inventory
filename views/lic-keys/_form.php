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

	<?= $form->field($model, 'lic_items_id')->widget(Select2::className(), [
		'data' => \app\models\LicItems::fetchNames(),
		'options' => [
            'placeholder' => 'Выберите закупку',
			'onchange' => 'fetchArmsFromDocs();'
        ],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'allowClear' => false,
			'multiple' => false
		]
	]) ?>
	
	<?= \app\components\TextAutoResizeWidget::widget([
		'form' => $form,
		'model' => $model,
		'attribute' => 'key_text',
		'lines' => 1,
	]) ?>

	<?= $form->field($model, 'arms_ids')->widget(Select2::className(), [
		'data' => \app\models\Arms::fetchNames(),
		'options' => ['placeholder' => 'Выберите АРМы',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'allowClear' => true,
			'multiple' => true
		]
	])->hint(\app\models\Contracts::fetchArmsHint(is_object($model->licItem)?$model->licItem->contracts_ids:null ,'lickeys'),['id'=>'arms_id-hint']) ?>
	
	
	<?= \app\components\TextAutoResizeWidget::widget([
		'form' => $form,
		'model' => $model,
		'attribute' => 'comment',
		'lines' => 4,
	]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
