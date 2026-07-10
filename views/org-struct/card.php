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
//единая цепочка положения: организация (partner) → путь по оргструктуре (self-chain)
echo \app\components\ChainWidget::widget(['segments'=>[
	['object'=>$model->partner],
	['node'=>$model,'chain'=>true],
]]);


