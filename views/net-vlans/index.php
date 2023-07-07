<?php

use app\components\DynaGridWidget;
use yii\helpers\Html;
use kartik\grid\GridView;

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

    <?= DynaGridWidget::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'id'=>'vlans-index',
        'panel'=>false,
        'columns' => [
			[
				'attribute'=>'networks_ids',
				'format'=>'raw',
				'value'=>function($data) use ($renderer){
    				if (is_array($data->networks)) {
    					$output=[];
    					foreach ($data->networks as $network)
    						$output[]=$renderer->render('/networks/item',['model'=>$network]);
						return implode('<br />',$output);
					}
					return '';
				},
				'contentOptions'=>[
					'class'=>'text-right'
				]
			],
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
            'comment',
        ],
    ]); ?>
</div>
