<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\UserGroups::$title;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-groups-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            'id',
            'name',
            'description:ntext',
            //'notebook:ntext',
            'ad_group',
            'sync_time',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
