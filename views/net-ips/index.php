<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\NetIpsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
\yii\helpers\Url::remember();

$this->title = app\models\NetIps::$titles;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="net-ips-index">

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

            [
                'attribute'=>'text_addr',
                'format'=>'raw',
                'value'=>function($data) use ($renderer){
                    return $renderer->render('item',['model'=>$data]);
                }
            ],
			[
				'attribute'=>'network',
				'format'=>'raw',
				'value'=>function($data) use ($renderer){
					return $renderer->render('/networks/item',['model'=>$data->network]);
				}
			],
			[
				'attribute'=>'vlan',
				'format'=>'raw',
				'value'=>function($data) use ($renderer){
    				if (is_object($data->network))
						return $renderer->render('/net-vlans/item',['model'=>$data->network->netVlan]);
    				return null;
				}
			],
			[
				'attribute'=>'attached',
				'format'=>'raw',
				'value'=>function($data) use ($renderer){
					$objects=[];
					
					if (is_array($data->comps) && count ($data->comps)) {
						foreach ($data->comps as $comp) $objects[]=$renderer->render('/comps/item',['model'=>$comp]);
					}
					
					if (is_array($data->techs) && count ($data->techs)) {
						foreach ($data->techs as $tech) $objects[]=$renderer->render('/techs/item',['model'=>$tech]);
					}
					
					if (count($objects)) return implode(', ',$objects);
					return null;
				}
			],
            //'addr',
            //'mask',
            'comment',
			//
            //['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
