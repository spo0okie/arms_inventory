<?php

use app\components\LinkObjectWidget;
use app\components\TextFieldWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\AccessTypes */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1>
	<?= LinkObjectWidget::widget([
		'model'=>$model,
		'hideUndeletable'=>false,
	]) ?>
</h1>

<?php
	echo Html::encode($model->comment);
	
	$flags=[];
	foreach (['is_app','is_ip','is_phone','is_vpn'] as $attr) {
		if ($model->$attr) {
			$flags[]='<li>'.$model->getAttributeLabel($attr).'</li>';
		}
	}
	if (count($flags))
		echo '<ul>'.implode($flags).'</ul>';

if ($model->ip_params_def)
	echo "<strong>Сетевые параметры по умолчанию:</strong> {$model->ip_params_def}";

if (count($model->children)) {
	echo '<h4>'.$model->getAttributeLabel('children').'</h4>';
	echo '<ul>';
	foreach ($model->children as $child)
		echo '<li>'.$child->renderItem($this,['static_view'=>true]).'</li>';
	echo '</ul>';
}

if (strlen($model->notepad)) {
	echo '<p>'.TextFieldWidget::widget(['model'=>$model,'field'=>'notepad']).'</p>';
}
