<?php

use app\components\DynaGridWidget;
use app\components\ModelFieldWidget;
use app\models\Contracts;
use app\models\ContractsStates;
use app\models\Currency;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ContractsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $tableId string */

$renderer=$this;
$showUsersInName=!DynaGridWidget::tableColumnIsVisible($tableId,'users',Contracts::$defaultColumns);
$showPartnersInName=!DynaGridWidget::tableColumnIsVisible($tableId,'partners',Contracts::$defaultColumns);
$showPayIdInName=!DynaGridWidget::tableColumnIsVisible($tableId,'pay_id',Contracts::$defaultColumns);
$showDateInName=!DynaGridWidget::tableColumnIsVisible($tableId,'date',Contracts::$defaultColumns);
return [
	'name'=>[
		'value'=>function($data) use ($renderer,$showPartnersInName,$showUsersInName,$showPayIdInName,$showDateInName) {
			/** @var $data Contracts */
			return $renderer->render('/contracts/item',[
				'model'=>$data,
				'name'=>$data->getSname(
					$showDateInName,
					true,
					$showPartnersInName,
					$showUsersInName,
					$showPayIdInName
				)
			]);
		}
	],
	'pay_id'=>[
		'value'=>function($data) {
			return $data->pay_id;
		}
	],
	'date'=>[
		'value'=>function($data) {
			return $data->datePart;
		}
	],
	'users'=>[
		'value'=>function($data) use ($renderer) {
			return ModelFieldWidget::widget(['model'=>$data,'field'=>'users','title'=>false,'item_options'=>['short'=>true]]);
		}
	],
	'partners'=>[
		'value'=>function($data) use ($renderer) {
			return ModelFieldWidget::widget(['model'=>$data,'field'=>'partners','title'=>false]);
		}
	],
	'state_id'=>[
		'filter'=> ContractsStates::fetchNames(),
		'contentOptions' => ['class' => 'contracts-state-column'],
		'value'=>function($data) use ($renderer) {
			return $renderer->render('/contracts/item-state',['model'=>$data]);
		}
	],
	'float_total'=>[
		'contentOptions' => ['class' => 'contracts-total-column'],
		'footerOptions' => ['class' => 'contracts-total-column'],
		'value'=>function($data) use ($renderer) {
			if ($data->total) {
				return number_format($data->total,2,',','');
			} return null;
		},
	],
	'float_charge'=>[
		'contentOptions' => ['class' => 'contracts-total-column'],
		'footerOptions' => ['class' => 'contracts-total-column'],
		'value'=>function($data) use ($renderer) {
			if ($data->charge) {
				//return $data->charge;
				return number_format($data->charge,2,',','');
			} return null;
		},
	],
	'total'=>[
		'class' => '\kartik\grid\DataColumn',
		'contentOptions' => ['class' => 'contracts-total-column'],
		'value'=>function($data) use ($renderer) {
			if ($data->total) {
				//return $data->total;
				return number_format($data->total,2,'.',' ' ).$data->currency->symbol;
			} return null;
		},
		'pageSummary'=>function() use ($dataProvider) {
			$totals=[];
			foreach ($dataProvider->models as $model) {
				if ($model->total) {
					if (!isset($totals[$model->currency_id])) $totals[$model->currency_id]=0;
					$totals[$model->currency_id]+=$model->total;
				}
			}
			
			$arrFooter=[];
			foreach (Currency::find()->all() as $currency) {
				/** @var Currency $currency */
				if (isset($totals[$currency->id]) && $totals[$currency->id]) {
					$arrFooter[]=number_format($totals[$currency->id],2,'.','&nbsp;').$currency->symbol;
				}
			}
			return implode('<br />',$arrFooter);
		}
	],
	'charge'=>[
		'contentOptions' => ['class' => 'contracts-total-column'],
		'value'=>function($data) use ($renderer) {
			if ($data->charge) {
				return number_format($data->charge,2,'.',' ').$data->currency->symbol;
			} return null;
		},
		'pageSummary'=>function() use ($dataProvider) {
			$totals=[];
			foreach ($dataProvider->models as $model) {
				if ($model->charge) {
					if (!isset($totals[$model->currency_id])) $totals[$model->currency_id]=0;
					$totals[$model->currency_id]+=$model->charge;
				}
			}
			
			$arrFooter=[];
			foreach (Currency::find()->all() as $currency) {
				/** @var Currency $currency */
				if (isset($totals[$currency->id]) && $totals[$currency->id]) {
					$arrFooter[]=number_format($totals[$currency->id],2,'.','&nbsp;').$currency->symbol;
				}
			}
			return implode('<br />',$arrFooter);
		},
	],
	'currency'=>[
		'value'=>function($data) {
			return ($data->total)?$data->currency->symbol:null;
		},
	],
	'attach'=>[
		'contentOptions' => ['class' => 'contracts-attach-column'],
		'value'=>function($data){
			return $data->sAttach;
		},
	],
	'techsCount'=>[
		'contentOptions' => ['class' => 'contracts-1attach-column'],
		'value'=>function($data){
			return $data->techsCount?$data->techsCount:'';
		},
	],
	'materialsCount'=>[
		'contentOptions' => ['class' => 'contracts-1attach-column'],
		'value'=>function($data){
			return $data->materialsCount?$data->materialsCount:'';
		},
	],
	'licsCount'=>[
		'contentOptions' => ['class' => 'contracts-1attach-column'],
		'value'=>function($data){
			return $data->licsCount?$data->licsCount:'';
		},
	],
	'deliveryStatus'=>[
		'contentOptions' => function($data) {
			switch ($data->deliveryState) {
				//case Contracts::DELIVERY_PAYMENT_WAIT:
				//	return ['class' => 'contracts-1attach-column'];
				
				case Contracts::DELIVERY_INCOMPLETE:
					return ['class' => 'contracts-1attach-column table-danger'];
				
				case Contracts::DELIVERY_COMPLETE:
					return ['class' => 'contracts-1attach-column table-success'];
				
				default:
					return ['class' => 'contracts-1attach-column'];
			}
			
		},
		'value'=>function($data){
			/** @var Contracts $data */
			switch ($data->deliveryState) {
				case Contracts::DELIVERY_PAYMENT_WAIT:
					return '<span qtip_ttip="Ожидаем оплату"><i class="fas fa-dollar-sign"></i></span>';

				case Contracts::DELIVERY_INCOMPLETE:
					return '<span qtip_ttip="Ожидаем поставку:<br>'.(implode('<br>',$data->undeliveredDescription)).'">'
							.'<i class="fas fa-truck"></i>'
						.'</span>';

				case Contracts::DELIVERY_COMPLETE:
					return '<i class="far fa-check-circle"></i>';

				default:
					return '';
			}
		},
		'filter'=>[true=>'С поставками',false=>'Без поставок'],
		//'filterType'=>GridView::FILTER_SELECT2
		'filterInputOptions'=>['style'=>'padding:0; background:#fff'],
	],
];
