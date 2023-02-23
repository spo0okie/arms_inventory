<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Places */

//ширина 1U = 465
//высота 1U =

$json='{
	"cols":[
		{"type":"units","count":1,"size":60},
		{"type":"void","size":60},
		{"type":"units","count":1,"size":60}
	],
	"rows":[
		{"type":"title","size":"12"},
		{"type":"units","count":6,"size":120},
		{"type":"void","size":40},
		{"type":"units","count":2,"size":60}
	],
	"hEnumeration":"-1",
	"vEnumeration":"-1",
	"priorEnumeration":"v",
	"evenEnumeration":"-1",
	"labelWidth":"20",
	"labelMode":"h",
	"labelPre":"1",
	"labelPost":"1"
	}';

	$params=json_decode($json,true);
	$params['id']=1;
	$params['title']="КЛГ-ШК-01";
	
?>
<div class="rack-place d-flex flex-row" >
	<span class="rack-cabinet">
		Спереди<br />
	    <?= \app\components\RackWidget::widget($params) ?>
	</span>
	<span class="rack-cabinet">
		Сзади<br />
	    <?= \app\components\RackWidget::widget($params+['front'=>false]) ?>
	</span>
</div>

