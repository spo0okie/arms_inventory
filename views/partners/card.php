<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 04.01.2019
 * Time: 2:49
 */

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Partners */

if (!isset($static_view)) $static_view=false;
$deleteable=true; //тут переопределить возможность удаления элемента
?>
	<h1>
		<?= \app\components\LinkObjectWidget::widget([
			'model'=>$model,
			'name'=>$model->bname.' ('.$model->uname.')',
			'static'=>$static_view,
			//'confirm' => 'Удалить этот сервис? Это действие необратимо!',
			'hideUndeletable'=>false
		]) ?>
	</h1>
	<p>
		<?php if ($model->inn) { ?>
			ИНН: <?= $model->inn ?>
		<?php } ?>

		<?php if ($model->kpp) { ?>
			КПП: <?= $model->kpp ?>
		<?php } ?>
	</p>

	<?= $this->render('support',['model'=>$model]) ?>
