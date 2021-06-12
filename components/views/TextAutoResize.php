<?php
/* @var $this yii\web\View */

/* @var $form yii\widgets\ActiveForm */
/* @var $model yii\db\ActiveRecord */
/* @var $field string */
/* @var $lines integer */



echo $form->field($model, $field)
	->textarea(['rows' => max($lines, count(explode("\n", $model->$field)))]);
$fieldId=strtolower(yii\helpers\StringHelper::basename($model::className()).'-'.$field);
$this->registerJs("$('#$fieldId').autoResize({extraSpace:15}).trigger('change.dynSiz');");

