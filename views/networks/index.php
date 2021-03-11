<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\NetworksSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
\yii\helpers\Url::remember();

$this->title = app\models\Networks::$title;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="networks-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Создать', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
		'formatter' => ['class' => 'yii\i18n\Formatter','nullDisplay' => ''],
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
			[
				'attribute'=>'segments_id',
				'format'=>'raw',
				'value'=>function($data) use ($renderer){
					return $renderer->render('/segments/item',['model'=>$data->segment]);
				}
			],
			'comment:ntext',
			[
				'attribute'=>'vlan_id',
				'format'=>'raw',
				'value'=>function($data) use ($renderer){
					return $renderer->render('/net-vlans/item',['model'=>$data->netVlan]);
				},
				'contentOptions'=>[
					'class'=>'text-right'
				]
			],
			[
				'attribute'=>'domain_id',
				'format'=>'raw',
				'value'=>function($data) use ($renderer){
					if (is_object($data->netVlan) && is_object($data->netVlan->netDomain))
						return $renderer->render('/net-domains/item',['model'=>$data->netVlan->netDomain]);
					return null;
				}
			],
			[
				'attribute'=>'usage',
				'format'=>'raw',
				'value'=>function($data) use ($renderer){
					return $renderer->render('used',['model'=>$data]);
				},
				'contentOptions'=>['class'=>'usage_col']
				
			],
            //'addr',
            //'mask',
            //'readableRouter',
            //'readableDhcp',

            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
