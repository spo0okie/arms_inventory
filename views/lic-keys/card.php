<?php

use app\components\LinkObjectWidget;
use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\LicKeys */
/* @var $linksData \yii\data\ArrayDataProvider */

if (!isset($static_view)) $static_view=false;

?>

<h1>
	Ключ <?= LinkObjectWidget::widget([
		'model' => $model,
		'name' => Html::encode($model->keyShort),
		'static' => $static_view,
		'hideUndeletable' => false,
		'confirmMessage' => 'Удалить этот лицензионный ключ? Убедись, что его есть где потом искать!',
	]) ?>
</h1>
	<h4>Группа лицензий:</h4>
	<?= $model->licItem->licGroup->renderItem($this,['static_view'=>$static_view]) ?>
	<br />

	<h4>Закупка:</h4>
	<?= $model->licItem->renderItem($this,['static_view'=>$static_view,'name'=>$model->licItem->descr]) ?>
	<br />

	<h4>Ключ:</h4>
	<?= $static_view?$model->keyShort:$model->key_text ?>
	<br />

	<?= $this->render('/attaches/model-list',compact(['model','static_view'])) ?>

	<h4>Комментарий:</h4>
	<?= \app\components\ModelFieldWidget::renderFieldValue($model,'comment') ?>


