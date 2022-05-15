<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\Aces::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="aces-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Создать', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            [
                'attribute'=>'id',
                'format'=>'raw',
                'value'=>function($data) use ($renderer){
                    return $renderer->render('item',['model'=>$data]);
                }
            ],
            'acls_id',
            'ips:ntext',
            'comment:ntext',
            'notepad:ntext',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
