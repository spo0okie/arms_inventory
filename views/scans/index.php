<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ScansSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\Scans::$title;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="scans-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Новый', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],
            'file',
	        [
		        'attribute'=>'id',
		        'format'=>'raw',
		        'value' => function($data){return Html::a($data['id'],['view', 'id' => $data['id']]);}
	        ],

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
