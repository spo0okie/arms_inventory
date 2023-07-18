<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\OrgStruct */

?>

<h1>
	<?= Html::encode($model->name) ?>
</h1>

<?php
$chain=[];
if (is_object($model->partner)) {
	$chain[]=$this->render('/partners/item',['model'=>$model->partner,'static_view'=>true]);
}
foreach ($model->chain as $item) $chain[]=$item->name;
echo implode('→',$chain);