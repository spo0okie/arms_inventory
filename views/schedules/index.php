<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SchedulesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\Schedules::$titles;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="schedules-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Новое', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php //var_dump($dataProvider->models); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            //'id',
            [
                'attribute'=>'name',
                'format'=>'raw',
                'value'=>function($data) {
                    return Html::a($data->name,['view','id'=>$data->id]);
                }
            ],

            'description',
            'weekWorkTimeDescription',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
