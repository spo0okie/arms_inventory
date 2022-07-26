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
/* @var $model app\models\OrgPhones */

if (!isset($static_view)) $static_view=false;

?>
<h2>

	<?= $model->title ?>
	<?php if (!$static_view) { ?>
		<?= Html::a('<span class="fas fa-pencil-alt"/>', ['org-phones/update', 'id' => $model->id]) ?>
		<?= Html::a('<span class="fas fa-trash"/>', ['org-phones/delete', 'id' => $model->id], [
			'data' => [
				'confirm' => 'Удалить этот городской телефон? Это действие необратимо!',
				'method' => 'post',
			],
		]) ?>
	<?php } ?>
</h2>

<p>	<?= \Yii::$app->formatter->asNtext($model->untitledComment) ?> </p>
<p>
	Стоимость: <?= Yii::$app->formatter->asCurrency((int)$model->cost) ?>
	<?php if ($model->charge){ ?>
		(в т.ч. НДС: <?= Yii::$app->formatter->asCurrency($model->charge) ?>)
	<?php } ?>
	/мес
</p>

<div class="row">
	<div class="col-md-4">
		<h5>Место подключения:</h5>
	</div>
	<div class="col-md-8">
		<?= $this->render('/places/item',['model'=>$model->place ,'static_view'=>$static_view]) ?>
	</div>
</div>

<div class="row">
	<div class="col-md-4">
		<h5><?= $model->getAttributeLabel('account')?> </h5>
	</div>
	<div class="col-md-8">
		<?= $model->account ?>
	</div>
</div>
<hr />
