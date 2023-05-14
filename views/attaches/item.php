<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Attaches $model */

if (!isset($static_view)) $static_view = false;

?>
<span class="attaches-item">
	<?= Html::a(
		'<i class="fas fa-file-download"></i> '.$model->name,
		$model->fullFname,[
		'target'=>'_blank'
	]) ?>
	<?=	\app\components\DeleteObjectWidget::widget([
		'model'=>$model,
		'confirmMessage'=>'Удалить приложенный файл? (действие необратимо)'
	])?>
</span>

