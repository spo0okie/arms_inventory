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
				'value'=>function($data) use ($renderer) {
    				if ($data->total) {
						return $data->total.$data->currency->symbol;
					} return '';
				}
			],
			'charge',
	        [
		        'attribute'=>'docsAttached',
		        'header'=>'<span class="fas fa-paperclip" title="Привязано документов"></span>',
		        'format'=>'raw',
		        'value'=>function($data){
			        return (count($data->childs)+($data->parent_id?1:0))?(count($data->childs)+($data->parent_id?1:0)):'';
		        }
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
	    'showFooter' => false,
		'showPageSummary' => false,
	    'panel' => [
		    'type' => GridView::TYPE_DEFAULT,
		    'heading' => $this->title,
	    ],
		'condensed'=>true,
		'resizableColumns'=>false,
	]); ?>
</div>
