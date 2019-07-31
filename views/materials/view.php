<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Materials */

$deleteable=!count($model->childs);

$this->title =  $model->materialType->name.': '. $model->model;

$this->params['breadcrumbs'][] = ['label' => \app\models\Materials::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

\yii\web\YiiAsset::register($this);
?>
<div class="materials-view">

    <h1>
        <?= Html::encode($this->title) ?>
        <?= Html::a('<span class="glyphicon glyphicon-pencil">', ['update', 'id' => $model->id]) ?>
        <?= $deleteable?Html::a('<span class="glyphicon glyphicon-trash">', ['delete', 'id' => $model->id], [
	        'data' => [
		        'confirm' => 'Are you sure you want to delete this item?',
		        'method' => 'post',
	        ],
        ]):'' ?>
    </h1>

    <p>	<?= \Yii::$app->formatter->asNtext($model->comment) ?> </p>
    <p>Поступило <?= $model->date?> <b><?= $model->count?><?= $model->materialType->units?></b>. Остаток <b><?= $model->rest?><?= $model->materialType->units?></b></p>
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
	        <?= $this->render('/materials/item',['model'=>$child,'full'=>true]) ?> (<?= $child->count?><?= $model->materialType->units?>) <br />
	        <?php } ?>
        </p>
        <br/>
	<?php } ?>


</div>
