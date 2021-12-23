<?php

use yii\helpers\Html;
use kartik\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ContractsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = \app\models\Contracts::$title;
$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
$filter=\yii\helpers\Html::tag('span','Отфильтровать:',['class'=>'btn']).
	\yii\helpers\Html::a('счета',['index','ContractsSearch[fullname]'=>'счет'],['class'=>'btn btn-default']).
	\yii\helpers\Html::a('ТТН',['index','ContractsSearch[fullname]'=>'ттн'],['class'=>'btn btn-default']).
	\yii\helpers\Html::a('УПД',['index','ContractsSearch[fullname]'=>'упд'],['class'=>'btn btn-default']).
	\yii\helpers\Html::a('договоры',['index','ContractsSearch[fullname]'=>'договор'],['class'=>'btn btn-default']);

$users=[];

//собираем суммы по валютам
$totals=[];
$charge=[];
foreach ($dataProvider->models as $model) {
	
	if ($model->total) {
		if (!isset($totals[$model->currency_id])) $totals[$model->currency_id]=0;
		if (!isset($charge[$model->currency_id])) $charge[$model->currency_id]=0;
		
		$totals[$model->currency_id]+=$model->total;
		$charge[$model->currency_id]+=$model->charge;
	}
	
}

$arrFooter=['total'=>[],'charge'=>[]];
foreach (\app\models\Currency::find()->all() as $currency) {
	if (isset($totals[$currency->id]) && $totals[$currency->id]) {
		$arrFooter['total'][]=number_format($totals[$currency->id],2,'.','&nbsp;').$currency->symbol;
	}
	if (isset($charge[$currency->id]) && $charge[$currency->id]) {
		$arrFooter['charge'][]=number_format($charge[$currency->id],2,'.','&nbsp;').$currency->symbol;
	}
}


?>
<div class="contracts-index">

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
	    'formatter' => ['class' => 'yii\i18n\Formatter','nullDisplay' => ''],
	    'columns' => [
	        [
		        'attribute'=>'fullname',
		        'header'=>'Документы',
		        'format'=>'raw',
		        'value'=>function($data) use ($renderer) {
			        return $renderer->render('/contracts/item',['model'=>$data,'name'=>$data['sname']]);
		        }
	        ],
			[
				'attribute'=>'state_id',
				'filter'=>\app\models\ContractsStates::fetchNames(),
				'format'=>'raw',
				'value'=>function($data) use ($renderer) {
					return $renderer->render('/contracts/item-state',['model'=>$data]);
				}
			],
			[
				'attribute'=>'total',
				//'filter'=>\app\models\ContractsStates::fetchNames(),
				'format'=>'raw',
				'contentOptions' => ['class' => 'contracts-total-column'],
				'footerOptions' => ['class' => 'contracts-total-column'],
				'value'=>function($data) use ($renderer) {
    				if ($data->total) {
						return number_format($data->total,2,'.','&nbsp;').$data->currency->symbol;
					} return '';
				},
				'footer'=>implode('<br />',$arrFooter['total']),
			],
			[
				'attribute'=>'charge',
				//'filter'=>\app\models\ContractsStates::fetchNames(),
				'format'=>'raw',
				'contentOptions' => ['class' => 'contracts-total-column'],
				'footerOptions' => ['class' => 'contracts-total-column'],
				'value'=>function($data) use ($renderer) {
					if ($data->charge) {
						return number_format($data->charge,2,'.','&nbsp;').$data->currency->symbol;
					} return '';
				},
				'footer'=>implode('<br />',$arrFooter['charge']),
			],
	        [
		        'attribute'=>'docsAttached',
		        'header'=>'<span class="fas fa-paperclip" title="Привязано документов"></span>',
		        'format'=>'raw',
		        'value'=>function($data){
			        return (count($data->childs)+($data->parent_id?1:0))?(count($data->childs)+($data->parent_id?1:0)):'';
		        },
	        ],
	        [
		        'attribute'=>'armsAttached',
		        'header'=>'<span class="fas fa-desktop" title="Привязано АРМов"></span>',
		        'format'=>'raw',
		        'value'=>function($data){
			        return count($data->arms)?count($data->arms):'';
		        }
	        ],
	        [
		        'attribute'=>'techsAttached',
		        'header'=>'<span class="fas fa-print" title="Привязано техники"></span>',
		        'format'=>'raw',
		        'value'=>function($data){
			        return count($data->techs)?count($data->techs):'';
		        }
	        ],
	        [
		        'attribute'=>'licsAttached',
		        'header'=>'<span class="fas fa-award" title="Привязано лицензий"></span>',
		        'format'=>'raw',
		        'value'=>function($data){
			        return count($data->licItems)?count($data->licItems):'';
		        }
	        ],
	        [
		        'attribute'=>'orgInetsAttached',
		        'header'=>'<span class="fas fa-globe" title="Привязано вводов интернет"></span>',
		        'format'=>'raw',
		        'value'=>function($data){
			        return count($data->orgInets)?count($data->orgInets):'';
		        }
	        ],
	        [
		        'attribute'=>'orgPhonesAttached',
		        'header'=>'<span class="fas fa-phone-alt" title="Привязано услуг телефонии"></span>',
		        'format'=>'raw',
		        'value'=>function($data){
			        return count($data->orgInets)?count($data->orgInets):'';
		        }
	        ],
        ],
	    'toolbar' => [
	    	Html::a('Добавить', ['create'], ['class' => 'btn btn-success']),
		    '{export}',
		    $filter
	    ],
		'toolbarContainerOptions' => ['class'=>'btn-toolbar pull-left'],
	    'export' => [
		    'fontAwesome' => true
	    ],
	    'showFooter' => true,
		'showPageSummary' => false,
	    'panel' => [
		    'type' => GridView::TYPE_DEFAULT,
		    'heading' => $this->title,
	    ],
		'condensed'=>true,
		'resizableColumns'=>false,
	]); ?>
</div>
