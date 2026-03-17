<?php
/**
 * Элемент услуги связи
 * User: spookie
 * Date: 03.01.2019
 * Time: 01:21
 */

/* @var \app\models\ProvTel $model */

use app\components\ItemObjectWidget;
use yii\helpers\Url;
?>

<?= ItemObjectWidget::widget([
	'model'=>$model,
	'name'=>$model->name,
	'ttipUrl'=>Url::to(['/prov-tel/ttip','id'=>$model->id]),
]) ?>
