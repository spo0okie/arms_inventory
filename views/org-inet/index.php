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
					return $renderer->render('item', ['model' => $data]);
				}
			],
			[
				'attribute' => 'places_id',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/places/item', ['model' => $data->place, 'static_view'=>true]);
				}
			],
	        //'static',
			[
				'attribute' => 'networks_id',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $this->render('/networks/item',['model'=>$data->network]);;
				},
			],
			[
				'attribute' => 'services_id',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/services/item', ['model' => $data->service, 'href'=>true]);
				}
			],
	        'account',
	        'cost',
	        'charge',
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
