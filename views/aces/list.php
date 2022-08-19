<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $models app\models\Aces[] */


if (is_array($models) && count($models)) {
	echo '<h4>Имеет доступ к:</h4>';
	foreach ($models as $model) {
		if (is_object($model->acl->schedule)) {
			echo $this->render('/schedules/item',['model'=>$model->acl->schedule,'static_view'=>true]).':';
		} else {
			echo 'Доступ к '.$this->render('/acls/item',['model'=>$model->acl]).':';
		}
		echo '<p>'.$this->render('/aces/objects',['model'=>$model]).'</p>';
	}
	echo '<br />';
}
