<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsUsages */

$this->title = $model->to;
$this->params['breadcrumbs'][] = ['label' => \app\models\MaterialsUsages::$title, 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="materials-usages-view">

    <h1>
        <?= Html::encode($this->title) ?>
        <?= Html::a('<span class="glyphicon glyphicon-pencil">', ['update', 'id' => $model->id]) ?>
        <?= Html::a('<span class="glyphicon glyphicon-trash">', ['delete', 'id' => $model->id], [
	        'data' => [
		        'confirm' => 'Are you sure you want to delete this item?',
		        'method' => 'post',
	        ],
        ]) ?>
    </h1>

    <br />

    <h4><?= $model->attributeLabels()['materials_id'] ?></h4>
    <p>
	    <?= $this->render('/materials/item',['model'=>$model->material]) ?>
        <br />
        <?= $model->count ?> <?= $model->material->type->units ?> израсходовано
    </p>
    <br />

    <h4><?= $model->attributeLabels()['arms_id'] ?></h4>
    <p>
		<?= $this->render('/arms/item',['model'=>$model->arm]) ?>
    </p>
    <br />

    <h4><?= $model->attributeLabels()['techs_id'] ?></h4>
    <p>
		<?= $this->render('/techs/item',['model'=>$model->tech]) ?>
    </p>
    <br />

</div>
