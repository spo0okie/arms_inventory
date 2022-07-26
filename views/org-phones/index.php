<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\OrgPhones::$title;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
?>
<div class="org-phones-index">

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
	    'formatter' => ['class' => 'yii\i18n\Formatter','nullDisplay' => ''],
	    'columns' => [
            //['class' => 'yii\grid\SerialColumn'],
            //'id',
            [
				'attribute' => 'fullNum',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('item', ['model' => $data, 'href'=>true,'static_view'=>false]);
				}
			],
	        'comment:ntext',
			[
				'attribute' => 'places_id',
				'format' => 'raw',
				'value' => function ($data) use ($renderer) {
					return $renderer->render('/places/item', ['model' => $data->place, 'static_view'=>true]);
				}
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
