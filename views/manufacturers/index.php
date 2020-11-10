<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ManufacturersSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Производители';
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="manufacturers-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>Список производителей ПО и железа. Нужен для того, чтобы к каждому производителю можно было добавить разные варианты написания и тем самым привести паспорта к единообразию.</p>


    <p>
        <?= Html::a('Добавить производителя', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            //'id',
            [
                'attribute'=>'name',
                'format'=>'raw',
                'value' => function($data) use($renderer){return $renderer->render('item',['model'=>$data,'static_view'=>true]);}
            ],
            [
                'attribute'=>'full_name',
                'format'=>'raw',
                'value' => function($data){return Html::a($data['full_name'],['view', 'id' => $data['id']]);}
            ],
            [
                'attribute'=>'comment',
                'format'=>'raw',
                'value' => function($data){return Html::a($data['comment'],['view', 'id' => $data['id']]);}
            ],

            //'created_at',

            [
                'class' => 'yii\grid\ActionColumn',
                'template' => '{view} {update}',
            ],
        ],
    ]); ?>
</div>
