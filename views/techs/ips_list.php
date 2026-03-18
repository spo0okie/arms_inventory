<?php

use app\components\widgets\page\ModelWidget;
/**
 * Список адресов машины
 * User: aareviakin
 * Date: 01.03.2019
 * Time: 19:18
 */
/* @var $this yii\web\View */
/* @var $model app\models\Techs */

if (!isset($static_view)) $static_view=false;

?>

	<h4>IP адрес(а):</h4>
 
<?php
	$output=[];
	foreach ($model->netIps as $ip) {
		$output[]=ModelWidget::widget(['model'=>$ip]);
	}
	echo implode('<br />',$output);



