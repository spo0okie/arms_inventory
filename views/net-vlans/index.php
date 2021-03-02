<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\NetVlansSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
\yii\helpers\Url::remember();

$this->title = \app\models\NetVlans::$title;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="net-vlans-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Создать', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            //'id',
			//'vlan',
            [
                'attribute'=>'name',
                'format'=>'raw',
                'value'=>function($data) use ($renderer){
                    return $renderer->render('item',['model'=>$data]);
                },
				'contentOptions'=>[
					'class'=>'text-right'
				]
            ],
			[
				'attribute'=>'domain_id',
				'format'=>'raw',
				'value'=>function($data) use ($renderer){
					return $renderer->render('/net-domains/item',['model'=>$data->netDomain]);
				},
				'contentOptions'=>[
					'class'=>'text-center'
				]
			],
			[
				'attribute'=>'segment_id',
				'format'=>'raw',
				'value'=>function($data) use ($renderer){
					return $renderer->render('/segments/item',['model'=>$data->segment]);
				}
			],
            'comment:ntext',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
