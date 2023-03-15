<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Techs */

//ширина 1U = 465
//высота 1U =

/*$json='{
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
	}';*/

?>
<div class="rack-place d-flex flex-row" >
	
	<?php if ($model->model->contain_front_rack) {
		//передняя спереди
		$params=json_decode($model->model->front_rack_layout,true);
		$params['id']=$model->id;
		$params['title']=$model->num;
		$params['front']=true;
		$params['model']=$model;
		?>
		<span class="rack-cabinet">
			Спереди<br />
	    	<?= \app\components\RackWidget::widget($params) ?>
		</span>
	<?php } ?>
	
	<?php if ($model->model->contain_back_rack && $model->model->back_rack_two_sided) {
		//задняя спереди (на передней стенке устройства будет задница двусторонней задней корзины)
		$params=json_decode($model->model->back_rack_layout,true);
		$params['id']=$model->id;
		$params['title']=$model->num;
		$params['front']=false;
		$params['model']=$model;
		?>
		<span class="rack-cabinet">
			Спереди<br />
	    	<?= \app\components\RackWidget::widget($params) ?>
		</span>
	<?php } ?>
	
	<?php if ($model->model->contain_back_rack) {
		//задняя корзина
		$params=json_decode($model->model->contain_back_rack,true);
		$params['id']=$model->id;
		$params['title']=$model->num;
		$params['front']=true;
		$params['model']=$model;
		?>
		<span class="rack-cabinet">
			Сзади<br />
	    	<?= \app\components\RackWidget::widget($params) ?>
		</span>
	<?php } ?>
	
	<?php if ($model->model->contain_front_rack && $model->model->front_rack_two_sided) {
		//задняя сзади (на передней стенке устройства будет задница двусторонней задней корзины)
		$params=json_decode($model->model->front_rack_layout,true);
		$params['id']=$model->id;
		$params['title']=$model->num;
		$params['front']=false;
		$params['model']=$model;
		?>
		<span class="rack-cabinet">
			Сзади<br />
	    	<?= \app\components\RackWidget::widget($params) ?>
		</span>
	<?php } ?>



</div>
