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
		<?= Html::a('<span class="glyphicon glyphicon-pencil"/>', ['update', 'id' => $model->id]) ?>
		<?= Html::a('<span class="glyphicon glyphicon-trash"/>', ['delete', 'id' => $model->id], [
			'data' => [
				'confirm' => 'Удалить этот городской телефон? Это действие необратимо!',
				'method' => 'post',
			],
		]) ?>
	<?php } ?>
</h1>

<p>	<?= \Yii::$app->formatter->asNtext($model->comment) ?> </p>


<h4>Место подключения:</h4>
<?= $this->render('/places/item',['model'=>$model->place ,'static_view'=>$static_view]) ?>

<h4><?= $model->getAttributeLabel('contracts_id')?> </h4>
<p><?= $this->render('/contracts/tree-map',['model'=>$model->contract,'static_view'=>$static_view,'map'=>$static_view?'chain-up':'full']) ?></p>

<h4><?= $model->getAttributeLabel('account')?> </h4>
<p><?= $model->account ?></p>
<hr />

<h4>Оператор связи:</h4>
<?= $this->render('/prov-tel/item',['model'=>$model->provTel ,'static_view'=>$static_view]) ?>

<?= $this->render('/prov-tel/card',['model'=>$model->provTel,'static_view'=>$static_view]) ?>
