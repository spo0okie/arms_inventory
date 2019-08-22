<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\MaterialsUsagesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
$renderer = $this;

$this->title = \app\models\MaterialsUsages::$title;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="materials-usages-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Добавить расход', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            //'id',
	        [
		        'attribute'=>'sname',
		        'format'=>'raw',
		        'value' => function($data) use($renderer){
			        return $renderer->render('/materials-usages/item',['model'=>$data,'full'=>true]);
		        }
	        ],
            //'count',
            //'date',
            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
