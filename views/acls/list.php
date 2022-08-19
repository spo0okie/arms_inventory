<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $models app\models\Acls[] */


if (is_array($models) && count($models)) {
	echo '<h4>Предоставлен доступ</h4>';
	foreach ($models as $model) {
		$items=[];
		foreach ($model->aces as $ace) {
			//echo $ace->id;
			$items[]=$this->render('/aces/objects',['model'=>$ace]);
		}
		echo '<h5>';
		if (is_object($model->schedule)) {
			echo $this->render('/schedules/item',['model'=>$model->schedule,'static_view'=>true]).':';
		} else {
			echo 'Доступ к '.$this->render('/acls/item',['model'=>$model]).':';
		}
		echo '</h5>';

		echo '<div class="px-1">'.implode('<br/>',$items).'</div>';
		echo '<br />';
	}
}
