<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Soft */

if (!isset($static_view)) $static_view=false;
?>



<h1>
	<?= $this->render('/manufacturers/item',['model'=>$model->manufacturer]) ?>
	<?= $this->render('item',['model'=>$model,'static_view'=>$static_view]) ?>
	<?php if (!$static_view) echo Html::a('<span class="glyphicon glyphicon-trash"></span>',
		['delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Are you sure you want to delete this item?',
			'method' => 'post',
		],
	]) ?>
</h1>
<p><?= $model->comment?></p>
<br />


<?php if (isset($hitlist) && ($hitlist!=='null')) { ?>
	<h4>Список regexp совпадений:</h4>
	<p>
		<?= Yii::$app->formatter->asNtext($hitlist) ?>
	</p>
	<br />
<?php } ?>

<h4>Основные компоненты входящие в продукт</h4>
<p><?= Yii::$app->formatter->asNtext($model->items) ?></p>
<br />

<h4>Дополнительные компоненты входящие в продукт</h4>
<p><?= Yii::$app->formatter->asNtext($model->additional) ?></p>
<br />

<h4>Членство в списках ПО</h4>
<p>
	<?php if (is_array($model->softLists)&&count($model->softLists)) foreach ($model->softLists as $item) { ?>
		<?= \yii\helpers\Html::a($item->descr,['soft-lists/view','id'=>$item->id]) ?><br/>
	
	<?php } else { ?>
		Отсутствуют
	<?php } ?>
</p>
