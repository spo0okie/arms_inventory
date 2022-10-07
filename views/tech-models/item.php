<?php
/**
 * Элемент модели оборудования
 * User: spookie
 * Date: 15.11.2018
 * Time: 21:55
 */

/* @var \app\models\TechModels $model */

if (!isset($static_view)) $static_view=false;

use yii\helpers\Html;
if (is_object($model)) {
	if (isset($short)&&$short)
		$name=(strlen($model->short))?$model->short:$model->name;
	elseif (isset($long)&&$long)
		$name=$model->manufacturer->name.' '.$model->name;
	elseif (isset($compact)&&$compact)
		$name=$model->manufacturer->name.' '.(strlen($model->short)?$model->short:$model->name);
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