<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\NetDomains */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1>
	<?= $this->render('item',compact('model')) ?>
	<?php  if(!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash"/>', ['net-domain/delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить этот элемент? Действие необратимо',
			'method' => 'post',
		],
	]) ?>
</h1>
<?= Yii::$app->formatter->asNtext($model->comment) ?>

