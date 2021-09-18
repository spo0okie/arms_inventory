<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\AccessTypes */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1>
	<?= Html::encode($model->name) ?>
	<?= $static_view?'':(Html::a('<span class="fas fa-pencil-alt"></span>',['access-types/update','id'=>$model->id])) ?>
	<?php  if(!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash"/>', ['access-types/delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить этот элемент? Действие необратимо',
			'method' => 'post',
		],
	]) ?>
</h1>

<?= DetailView::widget([
    'model' => $model,
    'attributes' => [
        'id',
        'code',
        'name',
        'comment',
        'notepad:ntext',
    ],
]) ?>

