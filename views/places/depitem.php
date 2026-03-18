<?php

use app\components\widgets\page\ModelWidget;
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 04.11.2019
 * Time: 0:18
 */

/* @var $this yii\web\View */
/* @var $models \app\models\OldArms */

$i=0;

foreach ($models['techs'] as $tech) {
	if ($i++) echo '<br />';
	
	echo ModelWidget::widget(['model'=>$tech,'options'=>['static_view'=>true]]);
	
	if (is_object($tech->user)) {
		echo '('.ModelWidget::widget(['model'=>$tech->user,'options'=>['short'=>true]]).')';
	}
}


