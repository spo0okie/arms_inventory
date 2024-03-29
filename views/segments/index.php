<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

\yii\helpers\Url::remember();

$this->title = app\models\Segments::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="segments-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            //'id',
            [
                'attribute'=>'name',
                'format'=>'raw',
                'value'=>function($data) use ($renderer){
                    return $renderer->render('item',['model'=>$data]);
                }
            ],
            'description:ntext',
            'code',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
