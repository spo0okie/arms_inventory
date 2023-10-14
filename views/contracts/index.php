<?php

use app\components\DynaGridWidget;
use app\models\Contracts;
use app\models\ContractsStates;
use app\models\Currency;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\ContractsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Contracts::$title;
$this->params['layout-container'] = 'container-fluid';

$this->params['breadcrumbs'][] = $this->title;
$renderer=$this;
$filter=\yii\helpers\Html::tag('span','Отфильтровать:',['class'=>'btn']).
	\yii\helpers\Html::a('счета',['index','ContractsSearch[fullname]'=>'счет'],['class'=>'btn btn-default']).' // '.
	\yii\helpers\Html::a('ТТН',['index','ContractsSearch[fullname]'=>'ттн'],['class'=>'btn btn-default']).' // '.
	\yii\helpers\Html::a('УПД',['index','ContractsSearch[fullname]'=>'упд'],['class'=>'btn btn-default']).' // '.
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
foreach (Currency::find()->all() as $currency) {
	/** @var Currency $currency */
	if (isset($totals[$currency->id]) && $totals[$currency->id]) {
		$arrFooter['total'][]=number_format($totals[$currency->id],2,'.','&nbsp;').$currency->symbol;
	}
	if (isset($charge[$currency->id]) && $charge[$currency->id]) {
		$arrFooter['charge'][]=number_format($charge[$currency->id],2,'.','&nbsp;').$currency->symbol;
	}
}


?>
<div class="contracts-index">

    <?= DynaGridWidget::widget([
		'id'=>'contracts-index',
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
	    'columns' => [
	        'fullname'=>[
		        'value'=>function($data) use ($renderer) {
			        return $renderer->render('/contracts/item',['model'=>$data,'name'=>$data['sname']]);
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
				'contentOptions' => ['class' => 'contracts-total-column'],
				'footerOptions' => ['class' => 'contracts-total-column'],
				'value'=>function($data) use ($renderer) {
					if ($data->total) {
						//return $data->total;
						return number_format($data->total,2,'.',' ' ).$data->currency->symbol;
					} return null;
				},
				'footer'=>implode('<br />',$arrFooter['total']),
			],
			'charge'=>[
				'contentOptions' => ['class' => 'contracts-total-column'],
				'footerOptions' => ['class' => 'contracts-total-column'],
				'value'=>function($data) use ($renderer) {
					if ($data->charge) {
						return number_format($data->charge,2,'.',' ').$data->currency->symbol;
					} return null;
				},
				'footer'=>implode('<br />',$arrFooter['charge']),
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
        ],
		'defaultOrder' => [
			'fullname',
			'state_id',
			'total',
			'charge',
			'attach',
		],
		'createButton'=>Html::a('Добавить', ['create'], ['class' => 'btn btn-success']).$filter,
		'showFooter' => true,
		'header' => $this->title,
		'resizableColumns'=>false,
	]); ?>
</div>
