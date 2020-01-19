<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 22.08.2019
 * Time: 12:08
 */

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $models app\models\Materials[] */
?>
<div class="materials-ttip ttip-card">
	<?php for ($i=0;$i<count($models);$i++) {
		$model=$models[$i];
		echo $this->render('card',['model'=>$model,'static_view'=>true]);
		if ($i<count($models)-1) echo '<hr />';
	} ?>
</div>
