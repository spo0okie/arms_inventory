<?php

/* @var $this yii\web\View */
/* @var $models app\models\Aces[] */
/* @var $hintModel \app\models\base\ArmsModel модель-владелец списка: даёт тултип-«?» заголовка (атрибут acls) */

use app\components\ModelFieldWidget;
use app\components\widgets\page\ModelWidget;

if (is_array($models) && count($models)) {
	echo isset($hintModel) && is_object($hintModel)
		? ModelFieldWidget::renderFieldTitle($hintModel,'acls',null,'h4','Имеет доступ к:')
		: '<h4>Имеет доступ к:</h4>';
	foreach ($models as $model) {
		if (is_object($model->acl) && is_object($model->acl->schedule)) {
			echo ModelWidget::widget(['model'=>$model->acl->schedule, 'options'=>['static_view' => true]]) . ': ';
		}
		echo ModelWidget::widget(['model'=>$model->acl]);
		echo '<br />';
	}
	echo '<br />';
}


