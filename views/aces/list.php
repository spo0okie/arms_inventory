<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $models app\models\Aces[] */


if (is_array($models) && count($models)) {
	echo '<h4>Имеет доступ к:</h4>';
	foreach ($models as $model) {
		echo $this->render('/acls/item',['model'=>$model->acl]);
		echo '<br />';
	}
	echo '<br />';
}
