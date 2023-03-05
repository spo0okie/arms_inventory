<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ManufacturersDictSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Словарь производителей';
$this->params['breadcrumbs'][] = $this->title;
$manufacturers=\app\models\Manufacturers::fetchNames();
?>
<div class="manufacturers-dict-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Добавить вариант написания производителя', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            'word',
            [
                'attribute'=>'manufacturers_id',
                'format'=>'raw',
                'value' => function($data) use ($manufacturers) {return $manufacturers[$data['id']];}
            ],

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
