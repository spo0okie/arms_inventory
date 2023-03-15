<?php

use yii\helpers\Html;

/**
 * @var $attr string
 * @var $empty boolean
 * @var $rack \app\components\RackWidget;
 * @var $rackDefault \app\components\RackWidget;
 
 */

?>

<?php if ($empty) { ?>
	<div class="alert alert-info" id="<?= $attr ?>-constructor-alert">
		<b>Внимание!</b> конфигурация корзины не настроена. Загружены значения по умолчанию!
	</div>
<?php $rack=$rackDefault; } elseif (!is_object($rack) || !$rack->isSimpleConfig) { ?>
	<div class="alert alert-danger" id="<?= $attr ?>-constructor-alert">
		<b>Внимание!</b> конфигурация корзины не распознана как типовая.
		Конструктор не может загрузить значения. Загружены значения по умолчанию!
	</div>
<?php
	$rack=$rackDefault; } ?>
<div class="row p-0 mx-0 mb-1">
	<div class="card p-0 m-0 col-md-3" >
		<div class="card-header">Габ. корпуса</div>
		<div class="card-body row">
			<div class="col-md-6">
				<?= Html::label('Ширина',$attr.'_width') ?>
				<?= Html::textInput($attr.'_width',$rack->totalWidth,['id'=>$attr.'_width','class'=>'form-control']) ?>
			</div>
			<div class="col-md-6">
				<?= Html::label('Высота',$attr.'_height') ?>
				<?= Html::textInput($attr.'_height',$rack->totalHeight,['id'=>$attr.'_height','class'=>'form-control']) ?>
			</div>
		</div>
	</div>
	<div class="card p-0 m-0 col-md-3" >
		<div class="card-header">Посадочных мест</div>
		<div class="card-body row">
			<div class="col-md-6">
				<?= Html::label('По гор.',$attr.'_cols') ?>
				<?= Html::textInput($attr.'_cols',$rack->simpleCols,['id'=>$attr.'_cols','class'=>'form-control','maxlength'=>3]) ?>
			
			</div>
			<div class="col-md-6">
				<?= Html::label('По верт.',$attr.'_rows') ?>
				<?= Html::textInput($attr.'_rows',$rack->simpleRows,['id'=>$attr.'_rows','class'=>'form-control','maxlength'=>3]) ?>
			
			</div>
		</div>
	</div>

	<div class="card p-0 col-md-6" >
		<div class="card-header">Отступы с краев до корзины</div>
		<div class="card-body row">
			<div class="col-md-3">
				<?= Html::label('Слева',$attr.'_empty_left') ?>
				<?= Html::textInput($attr.'_empty_left',$rack->simpleLeftOffset,['id'=>$attr.'_empty_left','class'=>'form-control','maxlength'=>3]) ?>

			</div>
			<div class="col-md-3">
				<?= Html::label('Справа',$attr.'_empty_right') ?>
				<?= Html::textInput($attr.'_empty_right',$rack->simpleRightOffset,['id'=>$attr.'_empty_right','class'=>'form-control','maxlength'=>3]) ?>

			</div>
			<div class="col-md-3">
				<?= Html::label('Сверху',$attr.'_empty_top') ?>
				<?= Html::textInput($attr.'_empty_top',$rack->simpleTopOffset,['id'=>$attr.'_empty_top','class'=>'form-control','maxlength'=>3]) ?>

			</div>
			<div class="col-md-3">
				<?= Html::label('Снизу',$attr.'_empty_bottom') ?>
				<?= Html::textInput($attr.'_empty_bottom',$rack->simpleBottomOffset,['id'=>$attr.'_empty_bottom','class'=>'form-control','maxlength'=>3]) ?>

			</div>
		</div>
	</div>
</div>


<div class="row p-0 mx-0 mb-1">
	<div class="card p-0 m-0 col-md-4" >
		<div class="card-header">Идентификаторы мест</div>
		<div class="card-body row">
			<div class="col-md-8">
				<div class="form-check">
					<?= Html::checkbox($attr.'_labelPre',$rack->labelPre,['id'=>$attr.'_labelPre','class'=>'form-check-input']) ?>
					<?= Html::label('Перед местом установки',$attr.'_labelPre',['class'=>'form-check-label']) ?>
				</div>

				<div class="form-check">
					<?= Html::checkbox($attr.'_labelPost',$rack->labelPost,['id'=>$attr.'_labelPost','class'=>'form-check-input']) ?>
					<?= Html::label('После места установки',$attr.'_labelPost',['class'=>'form-check-label']) ?>
				</div>
			</div>
			<div class="col-md-4">
				<?= Html::label('Размер',$attr.'_labelWidth') ?>
				<?= Html::textInput($attr.'_labelWidth',$rack->labelWidth,['id'=>$attr.'_labelWidth','class'=>'form-control','maxlength'=>3]) ?>
			</div>
		</div>
	</div>
	<div class="card p-0 m-0 col-md-8">
		<div class="card-header">Нумерация мест</div>
		<div class="card-body row">
			<div class="col-md-3 m-0 p-1">
				Сначала
				<div class="form-check">
					<?= Html::radio($attr.'_priorEnum',$rack->priorEnumeration=='h',['id'=>$attr.'_priorEnumH','class'=>'form-check-input','value'=>'h']) ?>
					<?= Html::label('По гориз.',$attr.'_priorEnumH',['class'=>'form-check-label']) ?>
				</div>
				<div class="form-check">
					<?= Html::radio($attr.'_priorEnum',$rack->priorEnumeration=='v',['id'=>$attr.'_priorEnumV','class'=>'form-check-input','value'=>'v']) ?>
					<?= Html::label('По верт.',$attr.'_priorEnumV',['class'=>'form-check-label']) ?>
				</div>
			</div>
			<div class="col-md-3 m-0 p-1">
				По верт.
				<div class="form-check">
					<?= Html::radio($attr.'_vEnum',$rack->vEnumeration==1,['id'=>$attr.'_vEnumDown','class'=>'form-check-input','value'=>'1']) ?>
					<?= Html::label('Вниз',$attr.'_vEnumDown',['class'=>'form-check-label']) ?>
				</div>
				<div class="form-check">
					<?= Html::radio($attr.'_vEnum',$rack->vEnumeration==-1,['id'=>$attr.'_vEnumUp','class'=>'form-check-input','value'=>'-1']) ?>
					<?= Html::label('Вверх',$attr.'_vEnumUp',['class'=>'form-check-label']) ?>
				</div>
			</div>
			<div class="col-md-3 m-0 p-1">
				По гориз.
				<div class="form-check">
					<?= Html::radio($attr.'_hEnum',$rack->hEnumeration==1,['id'=>$attr.'_hEnumR','class'=>'form-check-input','value'=>'1']) ?>
					<?= Html::label('Вправо',$attr.'_hEnumR',['class'=>'form-check-label']) ?>
				</div>
				<div class="form-check">
					<?= Html::radio($attr.'_hEnum',$rack->hEnumeration==-1,['id'=>$attr.'_hEnumL','class'=>'form-check-input','value'=>'-1']) ?>
					<?= Html::label('Влево',$attr.'_hEnumL',['class'=>'form-check-label']) ?>
				</div>
			</div>
			<div class="col-md-3 m-0 p-1">
				Четные строки.
				<div class="form-check">
					<?= Html::radio($attr.'_evenEnum',$rack->evenEnumeration==1,['id'=>$attr.'_evenEnumF','class'=>'form-check-input','value'=>'1']) ?>
					<?= Html::label('В том же',$attr.'_evenEnumF',['class'=>'form-check-label']) ?>
				</div>
				<div class="form-check">
					<?= Html::radio($attr.'_evenEnum',$rack->evenEnumeration==-1,['id'=>$attr.'_evenEnumB','class'=>'form-check-input','value'=>'-1']) ?>
					<?= Html::label('В обратном',$attr.'_evenEnumB',['class'=>'form-check-label']) ?>
				</div>
			</div>
		</div>
	</div>
</div>



<?= Html::button('Генерировать',[
	'class'=>'btn btn-secondary',
	'onClick'=>'generateRackConfFor("'.$attr.'")',
]) ?>