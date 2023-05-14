<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/** @var yii\web\View $this */
/** @var app\models\Attaches $model */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Attaches', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="attaches-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'techs_id',
            'services_id',
            'lic_types_id',
            'lic_groups_id',
            'lic_items_id',
            'lic_keys_id',
            'contracts_id',
            'places_id',
            'schedules_id',
            'filename',
        ],
    ]) ?>

</div>
