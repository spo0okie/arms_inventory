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

if (!isset($hide_usages)) $hide_usages=false;
if (!isset($hide_places)) $hide_places=false;

?>
<div class="materials-ttip ttip-card">
	<?php for ($i=0;$i<count($models);$i++) {
		echo $this->render('card',[
			'model'=>$models[$i],
			'static_view'=>true,
			'hide_places'=>$hide_places,
			'hide_usages'=>$hide_usages
		]);
		if ($i<count($models)-1) echo '<hr />';
	} ?>
</div>
