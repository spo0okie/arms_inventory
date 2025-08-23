<?php

use app\components\DynaGridWidget;
use app\models\Contracts;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ContractsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Contracts::$title;
$this->params['layout-container'] = 'container-fluid';

$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
$filter= Html::tag('span','Отфильтровать:',['class'=>'btn']).
	Html::a('счета',['index','ContractsSearch[fullname]'=>'счет'],['class'=>'btn btn-default']).' // '.
	Html::a('ТТН',['index','ContractsSearch[fullname]'=>'ттн'],['class'=>'btn btn-default']).' // '.
	Html::a('УПД',['index','ContractsSearch[fullname]'=>'упд'],['class'=>'btn btn-default']).' // '.
	Html::a('договоры',['index','ContractsSearch[fullname]'=>'договор'],['class'=>'btn btn-default']);

$gridId='contracts-index';
?>
<div class="contracts-index">

    <?= DynaGridWidget::widget([
		'id'=>'contracts-index',
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
	    'columns' => require 'columns.php',
		'defaultOrder' => Contracts::$defaultColumns,
		'gridOptions'=> [
			'showPageSummary' => true,
			'pageSummaryRowOptions'=>['class'=>'contracts-total-summary default']
		],
		'createButton'=>Html::a('Добавить', ['create'], ['class' => 'btn btn-success']).$filter,
		'header' => $this->title,
		'resizableColumns'=>false,
	]); ?>
</div>
