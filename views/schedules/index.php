<?php

use yii\helpers\Html;
use yii\grid\GridView;

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

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

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
            'monEffectiveDescription',
            'tueEffectiveDescription',
            'wedEffectiveDescription',
            'thuEffectiveDescription',
            'friEffectiveDescription',
            'satEffectiveDescription',
            'sunEffectiveDescription',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>


</div>
