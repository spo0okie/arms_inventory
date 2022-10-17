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
	
	<?= \app\components\DynaGridWidget::widget([
		'id' => 'org-inet-index',
		'dataProvider' => $dataProvider,
		'model' => new \app\models\OrgInet(),
		'columns' => [
			'name' => [
				'value' => function ($data) use ($renderer) {
					return $renderer->render('item', ['model' => $data,'static_view'=>false]);
				}
			],
			'places_id' => [
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/places/item', ['model' => $data->place, 'static_view'=>true,'short'=>true]);
				}
			],
			'networks_id' => [
				'value' => function ($data) use ($renderer) {
					return $this->render('/networks/item',['model'=>$data->network, 'static_view'=>true]);
				},
			],
			'services_id' => [
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/services/item', ['model' => $data->service, 'href'=>true]);
				}
			],
			'account' => [
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
			'totalUnpaid' => [
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
	        'comment',
        ],
	    'createButton' => Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		'toolButton'=> '<span class="p-2">'.\app\components\ShowArchivedWidget::widget().'<span>',
    ]); ?>
</div>
