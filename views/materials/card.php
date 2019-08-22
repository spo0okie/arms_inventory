<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 22.08.2019
 * Time: 12:08
 */

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\bootstrap\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Materials */

if (!isset($static_view)) $static_view=false;
$deleteable=!count($model->childs) && !count($model->usages);

?>
<div class="materials-view">

	<h1>
		<?= Html::encode($model->type->name.': '. $model->model) ?>
		<?= Html::a('<span class="glyphicon glyphicon-pencil">', ['update', 'id' => $model->id]) ?>
		<?= $deleteable?Html::a('<span class="glyphicon glyphicon-trash">', ['delete', 'id' => $model->id], [
			'data' => [
				'confirm' => 'Are you sure you want to delete this item?',
				'method' => 'post',
			],
		]):'' ?>

	</h1>

	<p>	<?= \Yii::$app->formatter->asNtext($model->comment) ?> </p>
	<p>
		Поступило <?= $model->date?> <b><?= $model->count?><?= $model->type->units?></b>. Остаток <b><?= $model->rest?><?= $model->type->units?></b>
		<?php if (!$static_view && ($model->rest >0)) { ?> <a onclick="$('#material_new_usage_modal').modal('toggle')" class="href btn btn-primary">использовать</a> <?php } ?>
		<?php if (!$static_view && ($model->rest >1)) { ?> <a onclick="$('#material_new_material_modal').modal('toggle')" class="href btn btn-primary">разделить</a> <?php } ?>
	</p>

	<br>

	<?php if (!empty($model->contracts_ids)) { ?>
		<h4><?= $model->getAttributeLabel('contracts_ids')?> </h4>
		<p>
			<?php foreach ($model->contracts as $contract) { ?>
				<?= $this->render('/contracts/item',['model'=>$contract]) ?>
			<?php } ?>
		</p>
		<br/>
	<?php } ?>


	<h4>Местонахождение</h4>
	<p><?= $this->render('/places/item',['model'=>$model->place,'full'=>true]) ?></p>
	<br/>

	<h4>Ответственный</h4>
	<p><?= $this->render('/users/item',['model'=>$model->itStaff]) ?></p>
	<br/>

	<?php if (!empty($model->parent_id)) { ?>
		<h4>Частично перемещено из</h4>
		<p><?= $this->render('/materials/item',['model'=>$model->parent,'full'=>true]) ?> </p>
		<br/>
	<?php } ?>

	<?php if (!empty($model->childs)) { ?>
		<h4>Частично перемещено в</h4>
		<p>
			<?php foreach ($model->childs as $child) { ?>
				<?= $this->render('/materials/item',['model'=>$child,'from'=>true]) ?> (<?= $child->count?><?= $model->type->units?>) <br />
			<?php } ?>
		</p>
		<br/>
	<?php } ?>

	<?php if (!empty($model->usages)) { ?>
		<h4>Частично израсходовано в</h4>
		<p>
			<?php foreach ($model->usages as $usage) { ?>
				<?= $this->render('/materials-usages/item',['model'=>$usage,'count'=>true,'to'=>true]) ?> <br />
			<?php } ?>
		</p>
		<br/>
	<?php } ?>

	<?php if(!$static_view) {
		//моздаем кнопочку добавления к продукту и открываем модальную форму выбора продукта
		Modal::begin([
			'id' => 'material_new_usage_modal',
			'header' => '<h2>использовать материал</h2>',
			'size' => Modal::SIZE_LARGE,
		]);
		$usage = new \app\models\MaterialsUsages();
		$usage->materials_id = [$model->id];
		echo $this->render('/materials-usages/_form', ['model' => $usage]);
		Modal::end();

		Modal::begin([
			'id' => 'material_new_material_modal',
			'header' => '<h2>переместить часть</h2>',
			'size' => Modal::SIZE_LARGE,
		]);
		$material = new \app\models\Materials();
		$material->parent_id = [$model->id];
		echo $this->render('/materials/_form', ['model' => $material]);
		Modal::end();

		//иначе не будет работать поиск в виджетах Select2
		$this->registerJs(
			"$('#material_new_usage_modal').removeAttr('tabindex');" .
			"$('#material_new_material_modal').removeAttr('tabindex');"
		);
	} ?>



</div>
