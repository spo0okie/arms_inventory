<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\OrgPhones::$title;
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="org-phones-index">

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
	    'formatter' => ['class' => 'yii\i18n\Formatter','nullDisplay' => ''],
	    'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            //'id',
            'fullNum',
	        'comment',
	        'place.fullName',
	        'provTel.name',
	        'account',
		    'cost',
		    'charge',
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
