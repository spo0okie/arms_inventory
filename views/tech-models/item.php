<?php
/**
 * Элемент модели оборудования
 * User: spookie
 * Date: 15.11.2018
 * Time: 21:55
 */

/* @var \app\models\TechModels $model */

if (!isset($static_view)) $static_view=true;

use yii\helpers\Html;
if (is_object($model)) {
	if ($short??false)
		$name=(strlen($model->short))?$model->short:$model->name;
	elseif ($long??false)
		$name=is_object($model->manufacturer)?($model->manufacturer->name.' '.$model->name):$model->name;
	elseif ($compact??false)
		$name=(is_object($model->manufacturer)?$model->manufacturer->name.' ':'')
			.(strlen($model->short)?$model->short:$model->name);
    else
		$name=$model->name;
    ?>

<span class="tech_model-item">
	<?= \app\components\LinkObjectWidget::widget([
		'model'=>$model,
		'name'=>$name,
		'static'=>$static_view
	]) ?>
</span>

<?php }