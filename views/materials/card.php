<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 22.08.2019
 * Time: 12:08
 */

use yii\helpers\Html;
use yii\widgets\DetailView;
use yii\bootstrap5\Modal;

/* @var $this yii\web\View */
/* @var $model app\models\Materials */

if (!isset($static_view)) $static_view=false;
if (!isset($hide_usages)) $hide_usages=false;
if (!isset($hide_places)) $hide_places=false;

$deleteable=!count($model->childs) && !count($model->usages);

?>
<div class="materials-view">

	<h1>
		<?= Html::a($model->type->name.': '. $model->model,['/materials/view','id'=>$model->id]) ?>
		<?= !$static_view?Html::a('<span class="fas fa-pencil-alt">', ['update', 'id' => $model->id]):'' ?>
		<?= $deleteable&&!$static_view?Html::a('<span class="fas fa-trash">', ['delete', 'id' => $model->id], [
			'data' => [
				'confirm' => 'Are you sure you want to delete this item?',
				'method' => 'post',
			],
		]):'' ?>

	</h1>

	<p>	<?= \Yii::$app->formatter->asNtext($model->comment) ?> </p>
	<p>
		<strong>Поступило</strong> <?= $model->date?> <b><?= $model->count?><?= $model->type->units?></b>. Остаток <b><?= $model->rest?><?= $model->type->units?></b>
		<?php if (!$static_view && ($model->rest >0)) { ?> <a onclick="$('#material_new_usage_modal').modal('toggle')" class="href btn btn-primary">использовать</a> <?php } ?>
		<?php if (!$static_view && ($model->rest >1)) { ?> <a onclick="$('#material_new_material_modal').modal('toggle')" class="href btn btn-primary">разделить</a> <?php } ?>
	</p>
	
	<?php if ($model->cost) { ?>
	<p>
		<strong>Стоимость:</strong> <?= $model->cost.''.$model->currency->symbol. (
			$model->charge?(' (в т.ч. НДС: '.$model->charge.$model->currency->symbol.')'):''
		) ?>
	</p>
	<?php } ?>
	<br>

	<?php if (!empty($model->contracts_ids)) { ?>
		<h4><?= $model->getAttributeLabel('contracts_ids')?> </h4>
		<p>
			<?php foreach ($model->contracts as $contract) { ?>
				<?= $this->render('/contracts/item',['model'=>$contract]) ?><br />
			<?php } ?>
		</p>
		<br/>
	<?php } ?>

	<?php if (!$hide_places) { ?>
		<h4>Местонахождение</h4>
		<p><?= $this->render('/places/item',['model'=>$model->place,'full'=>true]) ?></p>
		<br/>
	<?php } ?>

	<h4>Ответственный</h4>
	<p><?= $this->render('/users/item',['model'=>$model->itStaff]) ?></p>
	<br/>
	
	<?php if (!$hide_usages) { ?>
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
	<?php } ?>

	<?php if(!$static_view) {
		//моздаем кнопочку добавления к продукту и открываем модальную форму выбора продукта
		Modal::begin([
			'id' => 'material_new_usage_modal',
			'title' => '<h2>использовать материал</h2>',
			'size' => Modal::SIZE_LARGE,
		]);
		$usage = new \app\models\MaterialsUsages();
		$usage->materials_id = [$model->id];
		$usage->date=date('Y-m-d',time());
		echo $this->render('/materials-usages/_form', ['model' => $usage,'modalParent'=>'#material_new_usage_modal']);
		Modal::end();

		Modal::begin([
			'id' => 'material_new_material_modal',
			'title' => '<h2>переместить часть</h2>',
			'size' => Modal::SIZE_LARGE,
		]);
		$material = new \app\models\Materials();
		$material->parent_id = [$model->id];
		echo $this->render('/materials/_form', ['model' => $material,'modalParent'=>'#material_new_material_modal']);
		Modal::end();

	} ?>



</div>
