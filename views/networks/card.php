<?php

/* @var $this yii\web\View */
/* @var $model app\models\Networks */

use app\components\LinkObjectWidget;
use app\components\StripedAlertWidget;

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1 class="text-monospace mb-0 pb-0">
	<?= LinkObjectWidget::widget([
		'model'=>$model,
		'name'=>$model->sname
	])?>
</h1>
<p class="mb-2"><?= Yii::$app->formatter->asNtext($model->comment) ?></p>

<?php if ($model->archived) { echo StripedAlertWidget::widget(['title'=>'СЕТЬ ПЕРЕНЕСЕНА В АРХИВ']); }?>
	<h4>
		<?php if (is_object($model->segment)) { ?>
			<span class="text-nowrap me-3">
				Сегмент: <?= $this->render('/segments/item',['model'=>$model->segment]) ?>
			</span>
		<?php } ?>
		
		<?php
			if (is_object($model->segment) && is_object($model->netVlan))
				echo '<span class="text-nowrap me-3"> // </span>';
		?>

		<?php if (is_object($model->netVlan)) { ?>
			<span class="text-nowrap me-3">
				VLAN: <?= $this->render('/net-vlans/item',['model'=>$model->netVlan]) ?>
			</span>
			<?php if (is_object($model->netVlan)) { ?>
				<span class="text-nowrap me-3"> // </span>
				<span class="text-nowrap ">
					L2 Домен: <?= $this->render('/net-domains/item',['model'=>$model->netDomain]) ?>
				</span>
			<?php } ?>
		<?php } ?>
	</h4>


	<?php if (count($model->orgInets)) {?>
		<h4>Относится к вводу интернет: <?php foreach ($model->orgInets as $inet)
				echo $this->render('/org-inet/item',['model'=>$inet])
			?></h4>
	<?php } ?>

<div class="d-flex flex-row mt-2 mb-3">
	<div class="pe-5">
		<h4>Шлюз</h4>
		<?= $model->readableRouter; ?>
	</div>
	<div class="pe-5">
		<h4>DHCP</h4>
		<?= Yii::$app->formatter->asNtext($model->text_dhcp) ?>
	</div>
	<div class="pe-0 flex-fill">
		<h4>Использовано:</h4>
		<?= $this->render('used',['model'=>$model]) ?>
	</div>
</div>

