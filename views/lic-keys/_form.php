<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\LicKeys */
/* @var $form yii\widgets\ActiveForm */
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
			'allowClear' => false,
			'multiple' => false
		]
	]) ?>

	<?= $form->field($model, 'key_text')->textarea(['rows' => max(1,count(explode("\n",$model->key_text)))]) ?>
	<?php $this->registerJs("$('#lickeys-key_text').autoResize().trigger('change.dynSiz');"); ?>

	<?= $form->field($model, 'arms_ids')->widget(Select2::className(), [
		'data' => \app\models\Arms::fetchNames(),
		'options' => ['placeholder' => 'Выберите АРМы',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => true
		]
	])->hint(\app\models\Contracts::fetchArmsHint(is_object($model->licItem)?$model->licItem->contracts_ids:null ,'lickeys'),['id'=>'arms_id-hint']) ?>


	<?= $form->field($model, 'comment')->textarea(['rows' => max(4,count(explode("\n",$model->comment)))]) ?>
	<?php $this->registerJs("$('#lickeys-comment').autoResize().trigger('change.dynSiz');"); ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
