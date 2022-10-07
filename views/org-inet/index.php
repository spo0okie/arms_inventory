<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\OrgInet::$title;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="org-inet-index">
	
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
	    'formatter' => ['class' => 'yii\i18n\Formatter','nullDisplay' => ''],
	
	    'columns' => [
            //['class' => 'yii\grid\SerialColumn'],
			[
				'attribute' => 'name',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('item', ['model' => $data,'static_view'=>false]);
				}
			],
			[
				'attribute' => 'places_id',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/places/item', ['model' => $data->place, 'static_view'=>true,'short'=>true]);
				}
			],
	        //'static',
			[
				'attribute' => 'networks_id',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $this->render('/networks/item',['model'=>$data->network, 'static_view'=>true]);
				},
			],
			[
				'attribute' => 'services_id',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/services/item', ['model' => $data->service, 'href'=>true]);
				}
			],
			[
				'attribute' => 'account',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					/**
					 * @var $data \app\models\OrgInet
					 */
    				if (is_object($data->service) && count($data->service->contracts)) {
						return $renderer->render('/contracts/item', [
							'model' => $data->service->contracts[0],
							'name'=>$data->account,
							'static_view'=>true
						]);
					} else return $data->account;
					
				}
			],
	        'cost',
	        'charge',
			[
				'attribute' => 'totalUnpaid',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
    				if (is_object($service=$data->service))
					if (count($service->totalUnpaid)) {
						$debt = [];
						foreach ($service->totalUnpaid as $currency => $total)
							$debt[] = $total . '' . $currency;
						return implode('<br />', $debt) . '<br />' . floor((time()-strtotime($service->firstUnpaid))/86400).' дней';
					}
    				return null;
				}
			],
	        'comment:ntext',

            //['class' => 'yii\grid\ActionColumn'],
        ],
	    'toolbar' => [
		    Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		    '{export}'
	    ],
	    'toolbarContainerOptions' => ['class'=>'btn-toolbar pull-left'],
	    'export' => [
		    'fontAwesome' => true
	    ],
	    'showFooter' => false,
	    'showPageSummary' => false,
	    'panel' => [
		    'type' => GridView::TYPE_DEFAULT,
		    'heading' => $this->title,
	    ]
    ]); ?>
</div>
