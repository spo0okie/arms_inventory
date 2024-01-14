<?php

use yii\helpers\Inflector;
use yii\helpers\StringHelper;

/* @var $this yii\web\View */
/* @var $generator yii\gii\generators\crud\Generator */

$urlParams = $generator->generateUrlParams();

echo "<?php\n";
?>

use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;

/* @var $this yii\web\View */
/* @var $model <?= ltrim($generator->modelClass, '\\') ?> */

if (!isset($static_view)) $static_view=false;


if (!empty($model)) {
	if (!isset($name)) $name=$model-><?= $generator->getNameAttribute() ?>;
	echo ItemObjectWidget::widget([
		'model'=>$model,
		'archived_class'=>'text-decoration-line-through',
		'link'=> LinkObjectWidget::widget([
		'model'=>$model,
		'modal'=>true,
		'noDelete'=>true,
		'static'=>$static_view,
		'name'=>$name,
		//'nameSuffix'=>'',
		'noSpaces'=>true
	]),
]);
} else echo "Отсутствует";