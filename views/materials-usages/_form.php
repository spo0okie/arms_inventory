<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsUsages */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="materials-usages-form">

    <?php $form = ArmsForm::begin([
	    'action' => $model->isNewRecord? Url::to(['materials-usages/create']): Url::to(['materials-usages/update','id'=>$model->id]),
		'model' => $model
    ]); ?>

    <div class="row">
        <div class="col-md-7">
	        <?= $form->field($model, 'materials_id')->select2() ?>
        </div>
        <div class="col-md-2">
	        <?= $form->field($model, 'count') ?>
        </div>
        <div class="col-md-3">
	        <?= $form->field($model, 'date')->date() ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
	        <?= $form->field($model, 'comment') ?>
        </div>
        <div class="col-md-6">
	        <?= $form->field($model, 'techs_id')->select2() ?>
        </div>
    </div>

	<?= $form->field($model, 'history')->text(['rows' => 6]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
