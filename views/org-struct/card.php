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
foreach ($model->chain as $item) $chain[]=$item->name;
echo implode('→',$chain);