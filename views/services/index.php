<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\Services::$title;
$this->params['breadcrumbs'][] = $this->title;

$renderer=$this;
?>
<div class="services-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Новый сервис', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],
	
	        [
		        'attribute'=>'name',
		        'format'=>'raw',
		        'value'=>function($data) use ($renderer) {
			        return $renderer->render('/services/item',['model'=>$data]);
		        }
	        ],
            //'name',
            //'description:ntext',
            //'is_end_user',
	        'sla_id',
            'userGroup.name',
            //'notebook:ntext',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
