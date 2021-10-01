<?php
/* @var $this yii\web\View */

/* @var $form yii\bootstrap5\ActiveForm */
/* @var $model yii\db\ActiveRecord */
/* @var $field string */
/* @var $lines integer */

if (!isset($hint)) {
	echo $form->field($model, $field)
		->textarea(['rows' => max($lines, count(explode("\n", $model->$field)))]);
} else {
	echo $form->field($model, $field)
		->textarea(['rows' => max($lines, count(explode("\n", $model->$field)))])
		->hint($hint);
}
$fieldId=strtolower(yii\helpers\StringHelper::basename($model::className()).'-'.$field);
$this->registerJs("$('#$fieldId').autoResize({extraSpace:25}).trigger('change.dynSiz');");

