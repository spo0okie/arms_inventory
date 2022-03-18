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
<h1>

	<?= $model->fullNum ?>
	<?php if (!$static_view) { ?>
		<?= Html::a('<span class="fas fa-pencil-alt"/>', ['update', 'id' => $model->id]) ?>
		<?= Html::a('<span class="fas fa-trash"/>', ['delete', 'id' => $model->id], [
			'data' => [
				'confirm' => 'Удалить этот городской телефон? Это действие необратимо!',
				'method' => 'post',
			],
		]) ?>
	<?php } ?>
</h1>

<p>	<?= \Yii::$app->formatter->asNtext($model->comment) ?> </p>
<p>
	Стоимость: <?= Yii::$app->formatter->asCurrency((int)$model->cost) ?>
	<?php if ($model->charge){ ?>
		(в т.ч. НДС: <?= Yii::$app->formatter->asCurrency($model->charge) ?>)
	<?php } ?>
	/мес
</p>


<h4>Место подключения:</h4>
<?= $this->render('/places/item',['model'=>$model->place ,'static_view'=>$static_view]) ?>

<h4><?= $model->getAttributeLabel('services_id')?> </h4>
<p><?= $this->render('/services/item',['model'=>$model->service]) ?></p>

<h4>Документ(ы)-основание</h4>
<p>
	<?php
	foreach ($model->contracts as $contract)
		if (is_object($contract)) echo $this->render('/contracts/tree-map',['model'=>$contract,'static_view'=>$static_view,'map'=>$static_view?'chain-up':'full'])
	?>
</p>

<h4><?= $model->getAttributeLabel('account')?> </h4>
<p><?= $model->account ?></p>
<hr />

<h4>Поставщики услуги связи:</h4>
<?= is_object($model->partner)?$this->render('/partners/card',['model'=>$model->partner,'static_view'=>$static_view]):'' ?>
