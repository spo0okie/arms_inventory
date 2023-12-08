<?php

use app\components\DynaGridWidget;
use app\models\Schedules;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\SchedulesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Schedules::$titles;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="schedules-index">

    <?= DynaGridWidget::widget([
		'id'=>'schedules-index',
        'header' => $this->title,
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'createButton' => Html::a('Новое', ['create'], ['class' => 'btn btn-success']),
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
            'workTimeDescription',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
