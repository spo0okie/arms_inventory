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
		<?= Html::encode($model->bname.' ('.$model->uname.')') ?>
		<?= $static_view?'':(Html::a('<span class="fas fa-pencil-alt"></span>',['partners/update','id'=>$model->id])) ?>
		<?php if(!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash"/>', ['partners/delete', 'id' => $model->id], [
			'data' => [
				'confirm' => 'Удалить этот элемент? Действие необратимо',
				'method' => 'post',
			],
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
