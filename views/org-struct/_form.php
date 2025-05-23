<?php

use app\components\Forms\ArmsForm;
use app\helpers\FieldsHelper;
use app\models\OrgStruct;
use app\models\Partners;
use kartik\depdrop\DepDrop;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\OrgStruct */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="org-struct-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model,
	]); ?>
	
	<?= FieldsHelper::Select2Field($form,$model,'org_id',[
			'data'=> Partners::fetchNames()
	])?>
	
	<?= $form->field($model, 'parent_hr_id')->widget(DepDrop::class, [
		'data' => OrgStruct::fetchOrgNames($model->org_id),
		'type' => DepDrop::TYPE_SELECT2,
		'options' => [
			'placeholder' => 'Родительское подразделение',
		],
		'select2Options' => [
			'pluginOptions' => [
				'dropdownParent' => $modalParent,
				'allowClear' => true,
				'multiple' => false,
			],
		],
		'pluginOptions' => [
			'depends'=>['orgstruct-org_id'],
			'url'=> Url::to(['/org-struct/dep-drop']),
		]
	]) ?>
	
	<?= $form->field($model, 'name') ?>

    <?= $form->field($model, 'hr_id') ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
