<?php

use a1inani\yii2ModalAjax\ModalAjax;
use app\components\RackWidget;
use yii\web\JsExpression;

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



echo ModalAjax::widget([
	'id' => 'modal_rack_editor',
	'bootstrapVersion' => ModalAjax::BOOTSTRAP_VERSION_5,
	'header' => 'Правка слота',
	
	'selector' => 'td.rack-unit.open-in-modal-form',
	'ajaxSubmit' => true, // Submit the contained form as ajax, true by default
	'size' => ModalAjax::SIZE_DEFAULT,
	'options' => ['class' => 'header-secondary text-black text-left',],
	'clientOptions'=>['backdrop'=> 'static',],
	'autoClose' => true,
	'events'=>[
		ModalAjax::EVENT_MODAL_SHOW => new JsExpression("
			function(event, data, status, xhr, selector) {
				selector.addClass('modal-open');
				let h1=$(this).find('h1');
				if (h1.length) {
					let title=h1[0].innerHTML;
					$('h5.modal-title#modal_rack_editor-label').html(title);
					h1.slice(0).remove();
				}
			}
		"),
		ModalAjax::EVENT_MODAL_SHOW_COMPLETE => new JsExpression("
            function(event, xhr, textStatus) {
                if (xhr.status == 403) {
                	$('div#modal_form_loader').addClass('border-danger');
                	$('div#modal_form_loader div.modal-header').addClass('card-header bg-danger');
                	$('h5.modal-title#modal_form_loader-label').html('Error');
                	$('div#modal_form_loader div.modal-body').html('Доступ к этой операции отсутствует');
                }
            }
		"),
		//ModalAjax::EVENT_BEFORE_SUBMIT => new \yii\web\JsExpression($js1),
		ModalAjax::EVENT_MODAL_SUBMIT => new JsExpression("
			function(event, data, status, xhr, selector) {
				if (status==='success') window.location.reload();
			}
		"),
		//ModalAjax::EVENT_MODAL_SUBMIT_COMPLETE => new \yii\web\JsExpression($js3),
	],
]);
?>
<div class="rack-place d-flex flex-row" >
	
	<?php if ($model->model->contain_front_rack) {
		//передняя спереди
		$params=json_decode($model->model->front_rack_layout,true);
		$params['id']=$model->id;
		$params['title']=$model->num;
		$params['front']=true;
		$params['front_rack']=true;
		$params['model']=$model;
		?>
		<span class="rack-cabinet">
			Спереди<br />
	    	<?= RackWidget::widget($params) ?>
		</span>
	<?php } ?>
	
	<?php if ($model->model->contain_back_rack && $model->model->back_rack_two_sided) {
		//задняя спереди (на передней стенке устройства будет задница двусторонней задней корзины)
		$params=json_decode($model->model->back_rack_layout,true);
		$params['id']=$model->id;
		$params['title']=$model->num;
		$params['front']=false;
		$params['front_rack']=false;
		$params['model']=$model;
		?>
		<span class="rack-cabinet">
			Спереди<br />
	    	<?= RackWidget::widget($params) ?>
		</span>
	<?php } ?>
	
	<?php if ($model->model->contain_back_rack) {
		//задняя корзина
		$params=json_decode($model->model->back_rack_layout,true);
		$params['id']=$model->id;
		$params['title']=$model->num;
		$params['front']=true;
		$params['front_rack']=false;
		$params['model']=$model;
		?>
		<span class="rack-cabinet">
			Сзади<br />
	    	<?= RackWidget::widget($params) ?>
		</span>
	<?php } ?>
	
	<?php if ($model->model->contain_front_rack && $model->model->front_rack_two_sided) {
		//задняя сзади (на передней стенке устройства будет задница двусторонней задней корзины)
		$params=json_decode($model->model->front_rack_layout,true);
		$params['id']=$model->id;
		$params['title']=$model->num;
		$params['front']=false;
		$params['front_rack']=true;
		$params['model']=$model;
		?>
		<span class="rack-cabinet">
			Сзади<br />
	    	<?= RackWidget::widget($params) ?>
		</span>
	<?php } ?>



</div>
