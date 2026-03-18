<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

use app\components\widgets\page\ModelWidget;
/* @var $this yii\web\View */
/* @var $model app\models\OrgStruct */

?>

<h1>
	<?= Html::encode($model->name) ?>
</h1>

<?php
$chain=[];
if (is_object($model->partner)) {
	$chain[]=ModelWidget::widget(['model'=>$model->partner,'options'=>['static_view'=>true]]);
}
foreach ($model->chain as $item) $chain[]=$item->name;
echo implode(' → ',$chain);


