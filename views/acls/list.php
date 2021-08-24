<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $models app\models\Acls[] */


if (is_array($models) && count($models)) {
	echo '<h4>Предоставлен доступ</h4>';
	foreach ($models as $model) {
		echo $this->render('/acls/item',['model'=>$model]);
		echo '<br />';
	}
}
