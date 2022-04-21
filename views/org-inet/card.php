<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 04.01.2019
 * Time: 3:25
 */



use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\OrgInet */

if (!isset($static_view)) $static_view=false;

?>


<h1>

	<?= $model->name ?>
	<?php if (!$static_view) { ?>
		<?= Html::a('<span class="fas fa-pencil-alt"/>', ['update', 'id' => $model->id]) ?>
		<?= Html::a('<span class="fas fa-trash"/>', ['delete', 'id' => $model->id], [
			'data' => [
				'confirm' => 'Удалить этот ввод интернет? Это действие необратимо!',
				'method' => 'post',
			],
		]) ?>
	<?php } ?>
</h1>

<p>	<?= \Yii::$app->formatter->asNtext($model->comment) ?> </p>
<?php if($model->cost) { ?>
	<p>
		Стоимость: <?= Yii::$app->formatter->asCurrency($model->cost) ?>
		<?php if ($model->charge){ ?>
			(в т.ч. НДС: <?= Yii::$app->formatter->asCurrency($model->charge) ?>)
		<?php } ?>
		/мес
	</p>
<?php }

if ($model->network) { ?>
	<h4>Сетевые адреса:</h4>
	<p>
		<?= $this->render('/networks/item',['model'=>$model->network]) ?>
	</p>
<?php } else { ?>
	<h4>Динамический адрес</h4>
<?php } ?>

<h4>Место подключения:</h4>
<?= $this->render('/places/item',['model'=>$model->place ,'static_view'=>$static_view]) ?>


<h4><?= $model->getAttributeLabel('account')?> </h4>
<p><?= $model->account ?></p>

<h3>Заметки:</h3>
<?= \Yii::$app->formatter->asNtext($model->history) ?>
