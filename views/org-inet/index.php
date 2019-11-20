<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\OrgInet::$title;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="org-inet-index">
	
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
	    'formatter' => ['class' => 'yii\i18n\Formatter','nullDisplay' => ''],
	
	    'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'name',
	        'places.name:raw:Объект',
	        //'static',
            'ip_addr',
            //'ip_gw',
            //'ip_dns1',
            //'ip_dns2',
            //'type',
            //'static',
            'provTel.name:raw:Оператор связи',
	        'account',
	        'cost',
	        'charge',
	        'comment:ntext',

            ['class' => 'yii\grid\ActionColumn'],
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
