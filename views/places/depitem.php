<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 04.11.2019
 * Time: 0:18
 */

/* @var $this yii\web\View */
/* @var $models \app\models\Arms */

$i=0;
foreach ($models as $arm) {
	if ($i++) echo '<br />';
	
	echo $this->render('/arms/item',['model'=>$arm,'static_view'=>true]);
	
	if (is_object($arm->user)) {
		echo '('.$this->render('/users/item', ['model' => $arm->user,'short'=>true]).')';
	}
}