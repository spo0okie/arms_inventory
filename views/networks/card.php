<?php

/* @var $this yii\web\View */
/* @var $model app\models\Networks */

use app\components\LinkObjectWidget;

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1 class="text-monospace">
	<?= LinkObjectWidget::widget([
		'model'=>$model,
		'name'=>$model->sname
	])?>
</h1>
<?= Yii::$app->formatter->asNtext($model->comment) ?>
<?php if ($model->archived) { ?>
		<div class="d-flex w-100 my-2">
			<div class="flex-fill alert-striped"></div>
			<div class="text-center mx-2">
				<span class="fas fa-exclamation-triangle"></span>
				СЕТЬ ПЕРЕНЕСЕНА В АРХИВ
				<span class="fas fa-exclamation-triangle"></span>
			</div>
			<div class="flex-fill alert-striped"></div>
		</div>
<?php }?>
	<h4>
		<?php if (is_object($model->segment)) { ?>
			Сегмент: <?= $this->render('/segments/item',['model'=>$model->segment]) ?>
		<?php } ?>
		
		<?php if (is_object($model->segment) && is_object($model->netVlan)) echo ' // '; ?>

		<?php if (is_object($model->netVlan)) { ?>
		VLAN: <?= $this->render('/net-vlans/item',['model'=>$model->netVlan]) ?>
		<?php } ?>
	</h4>

<?php if (is_object($model->netVlan)) { ?>
	<h4>L2 Домен: <?= $this->render('/net-domains/item',['model'=>$model->netDomain]) ?></h4>
<?php } ?>

	<?php if (count($model->orgInets)) {?>
		<h4>Относится к вводу интернет: <?php foreach ($model->orgInets as $inet)
				echo $this->render('/org-inet/item',['model'=>$inet])
			?></h4>
	<?php } ?>

	<div class="row mb-3">
		<div class="col-md-6">
			<h4>Шлюз</h4>
			<?= $model->readableRouter; ?>
		</div>
		<div class="col-md-6">
			<h4>DHCP</h4>
			<?= Yii::$app->formatter->asNtext($model->text_dhcp) ?>
		</div>
	</div>
	<div class="row mb-3">
		<div class="col-md-12">
			<h4>Использовано:</h4>
			<?= $this->render('used',['model'=>$model]) ?>
		</div>
	</div>

