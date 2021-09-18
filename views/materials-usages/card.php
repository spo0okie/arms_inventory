<?php
/**
 * Created by PhpStorm.
 * User: aareviakin
 * Date: 22.08.2019
 * Time: 12:08
 */

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\MaterialsUsages */

if (!isset($static_view)) $static_view=false;

?>
    <h1>
        <?= Html::encode($model->to) ?>
        <?= Html::a('<span class="fas fa-pencil-alt">', ['update', 'id' => $model->id]) ?>
        <?= !$static_view?Html::a('<span class="fas fa-trash">', ['delete', 'id' => $model->id], [
	        'data' => [
		        'confirm' => 'Are you sure you want to delete this item?',
		        'method' => 'post',
	        ],
        ]):'' ?>
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
