<?php

/*
 * Содержимое формы вынесено в отдельный файл, т.к. может быть использовано и в форме ACE и в форме ACL
 */

use app\components\AttributeTooltip;
use app\models\AccessTypes;
use app\models\Users;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */
/* @var $form yii\widgets\ActiveForm */

if (!isset($modalParent)) $modalParent=null;

$accessTypesAll=AccessTypes::find()->orderBy(['name'=>SORT_ASC])->all();
$accessTypesById=ArrayHelper::index($accessTypesAll,'id');
$accessTypesItems=ArrayHelper::map($accessTypesAll,'id','name');
$ipParams=$model->getIpParams();

$bundleUrl=Url::to(['/access-types/access-types-form']);
$createUrl=Url::to(['/access-types/create']);

/*
 * Пикер типов доступа:
 *  - выбранные типы всплывают в топ списка (flex order), у выбранных IP-типов инпут сетевых параметров в строке
 *  - фильтр по подстроке названия (выбранные видны всегда)
 *  - quick-add: создание нового типа доступа не покидая форму (AJAX на штатный access-types/create)
 * updateAccessTypes() передает в контроллер список выбранных типов и в ответ получает список типов,
 * которые должны быть выбраны (могут добавиться дочерние от комплексных - они блокируются от снятия),
 * а также параметры IP-типов для генерации инпутов
 */
/** @noinspection JSUnusedLocalSymbols */
$js= <<<JS
//поднимает выбранные типы в топ (flex order), тогглит видимость инпутов IP-параметров, обновляет фильтр
function markAccessTypesSelection() {
	$('div.access-type-item').each(function(i,el){
		let \$item=$(el);
		let checked=\$item.find('input[type=checkbox]').prop('checked');
		\$item.toggleClass('order-0 w-100 selected',checked).toggleClass('order-1',!checked);
		\$item.find('div.access-type-param').toggleClass('d-none',!checked);
	});
	filterAccessTypes();
}

//фильтр списка по подстроке названия (выбранные видны всегда)
function filterAccessTypes() {
	let q=$('input#access-types-filter').val().toLowerCase().trim();
	let total=0,shown=0;
	$('div.access-type-item').each(function(i,el){
		let \$item=$(el);
		total++;
		let visible=!q
			|| String(\$item.attr('data-name')).indexOf(q)>=0
			|| \$item.find('input[type=checkbox]').prop('checked');
		\$item.toggleClass('d-none',!visible);
		if (visible) shown++;
	});
	let \$count=$('span#access-types-filter-count');
	if (shown<total) {
		\$count.text(shown+' / '+total).removeClass('d-none');
	} else {
		\$count.text('').addClass('d-none');
	}
}

//создает (если еще нет) инпут IP-параметров внутри строки типа type_id
function ensureAccessTypeParamInput(type_id,type) {
	let \$item=$('div.access-type-item[data-type-id="'+type_id+'"]');
	if (!\$item.length || \$item.find('div.access-type-param').length) return;
	let \$group=$('<div class="access-type-param input-group input-group-sm ms-3 flex-grow-1 w-auto"></div>')
		.attr('id','access_type_'+type_id+'_param');
	\$group.append($('<span class="input-group-text"></span>')
		.attr('id','access_type_'+type_id+'_param_label')
		.text('IP параметры'));
	\$group.append($('<input type="text" class="form-control">')
		.attr('name','Aces[ipParams]['+type_id+']')
		.attr('aria-describedby','access_type_'+type_id+'_param_label')
		.val(type.hasOwnProperty('default_param')?type.default_param:''));
	\$item.append(\$group);
}

function updateAccessTypes() {
	//сбрасываем прежние авто-добавленные дочерние типы (disabled == выставлен автоматически),
	//актуальные снова выставит ответ сервера
	$('input[name="Aces[access_types_ids][]"]:disabled').prop('disabled',false).prop('checked',false);
	let get_params=[];
	//получаем список выбранных типов
	$('input[name="Aces[access_types_ids][]"]:checked').each(function(i,el){
		get_params.push('access_types_ids[]='+$(el).val());
	});
	markAccessTypesSelection();
	if (!get_params.length) return;
	//передаем в контроллер с типами доступа
	$.ajax({
		url:'{$bundleUrl}?'+get_params.join('&'),
		success: function (data) {
			for (let i in data) if (data.hasOwnProperty(i)) {
				let type=data[i];
				if (type.hasOwnProperty('optional')) {
					$('input[name="Aces[access_types_ids][]"][value='+i+']')
						.prop('checked',true)
						.prop('disabled',!(type.optional));
				}
				if (type.hasOwnProperty('is_ip')) ensureAccessTypeParamInput(i,type);
			}
			markAccessTypesSelection();
		}
	});
}

function toggleQuickAddAccessType() {
	let \$quickAdd=$('div#access-type-quick-add').toggleClass('d-none');
	if (!\$quickAdd.hasClass('d-none')) $('input#at-quick-name').trigger('focus');
}

function quickAddAccessTypeReset() {
	$('input#at-quick-name,input#at-quick-ip-params').val('');
	$('input#at-quick-is-app,input#at-quick-is-ip,input#at-quick-is-phone,input#at-quick-is-vpn').prop('checked',false);
	$('div#at-quick-ip-params-group').addClass('d-none');
	$('div#access-type-quick-add').addClass('d-none');
}

//добавляет в список чекбокс нового (созданного quick-add-ом) типа доступа
function addAccessTypeItem(model) {
	let \$item=$($('template#access-type-item-template').html());
	let inputId='ace-access-type-'+model.id;
	\$item.attr('data-type-id',model.id)
		.attr('data-name',String(model.name||'').toLowerCase())
		.attr('data-is-ip',model.is_ip?1:0);
	\$item.find('input[type=checkbox]').attr('value',model.id).attr('id',inputId).prop('checked',true);
	\$item.find('label').attr('for',inputId).text(model.name);
	//вставляем по алфавиту
	let inserted=false;
	$('div#aces-access_types_ids div.access-type-item').each(function(i,el){
		if (!inserted && $(el).attr('data-name')>\$item.attr('data-name')) {
			\$item.insertBefore($(el));
			inserted=true;
		}
	});
	if (!inserted) $('div#aces-access_types_ids').append(\$item);
}

function quickAddAccessType() {
	let \$name=$('input#at-quick-name');
	let name=\$name.val().trim();
	if (!name) {
		\$name.trigger('focus');
		return;
	}
	//если тип с таким названием уже есть - не создаем дубль, а просто отмечаем его
	let lower=name.toLowerCase();
	let \$existing=$('div.access-type-item').filter(function(){return $(this).attr('data-name')===lower;});
	if (\$existing.length) {
		\$existing.find('input[type=checkbox]').prop('checked',true);
		quickAddAccessTypeReset();
		updateAccessTypes();
		return;
	}
	let \$btn=$('button#at-quick-create').prop('disabled',true);
	$.post('{$createUrl}',{
		_csrf: yii.getCsrfToken(),
		'AccessTypes[name]': name,
		'AccessTypes[is_app]': $('input#at-quick-is-app').prop('checked')?1:0,
		'AccessTypes[is_ip]': $('input#at-quick-is-ip').prop('checked')?1:0,
		'AccessTypes[is_phone]': $('input#at-quick-is-phone').prop('checked')?1:0,
		'AccessTypes[is_vpn]': $('input#at-quick-is-vpn').prop('checked')?1:0,
		'AccessTypes[ip_params_def]': $('input#at-quick-ip-params').val()
	}).done(function(data){
		//defaultReturn отдает модель обернутой в массив(ы) - разворачиваем
		let model=data;
		while (Array.isArray(model)) model=model[0];
		if (!model || !model.id) {
			alert('Не удалось создать тип доступа');
			return;
		}
		addAccessTypeItem(model);
		quickAddAccessTypeReset();
		updateAccessTypes();
	}).fail(function(xhr){
		alert(xhr.status===403?
			'Нет прав на создание типов доступа':
			'Не удалось создать тип доступа');
	}).always(function(){
		\$btn.prop('disabled',false);
	});
}
JS;
$this->registerJs($js, View::POS_HEAD);
//вызываем нашу функцию после загрузки формы, т.к. может быть нужно поотключать некоторые чекбоксы если они дочерние доступы
$this->registerJs('updateAccessTypes()');
?>

	<div class="row">
		<div class="col-md-6">
			<div class="card bg-light">
				<div class="card-header">Кому предоставляется доступ</div>
				<div class="card-body">
					<?= $form->field($model,'users_ids')->select2(['data' => Users::fetchWorking()]) ?>

					<?= $form->field($model, 'comps_ids')->select2() ?>

					<?= $form->field($model, 'services_ids')->select2() ?>

					<?= $form->field($model,'ips')->textAutoresize(['rows' => 1]) ?>

					<?= $form->field($model, 'comment') ?>
				</div>
			</div>



		</div>
		<div class="col-md-6">
			<div class="card bg-light mb-3">
				<div class="card-header">Зачем предоставляется доступ</div>
				<div class="card-body">
					<?= $form->field($model,'name');?>
				</div>
			</div>
			<!-- https://www.yiiframework.com/doc/api/2.0/yii-helpers-basehtml#activeCheckboxList()-detail -->
			<div class="card bg-light">
				<div class="card-header">Какой этим объектам предоставляется доступ
					<?/*подача подсказки атрибута - иконкой «?» (ui-sources.md §0.1)*/?>
					<?= AttributeTooltip::icon(AttributeTooltip::build(
						$model,'access_types_ids',AttributeTooltip::MODE_FORM,
						'Типы предоставляемого доступа'
					)) ?>
				</div>
				<div class="card-body">
					<?php
					//фильтр и quick-add встраиваются в template поля между label и списком,
					//чтобы заголовок «Типы доступа» был над всем пикером
					ob_start(); ?>
					<div class="input-group input-group-sm mb-2">
						<input type="text" class="form-control" id="access-types-filter"
							placeholder="Фильтр по названию…" oninput="filterAccessTypes()">
						<span class="input-group-text d-none" id="access-types-filter-count"></span>
						<button type="button" class="btn btn-outline-primary" qtip_ttip="Создать новый тип доступа"
							onclick="toggleQuickAddAccessType()"><span class="fas fa-plus"></span></button>
					</div>
					<div class="card card-body p-2 mb-2 d-none" id="access-type-quick-add">
						<div class="input-group input-group-sm">
							<span class="input-group-text">Новый тип</span>
							<!--suppress HtmlFormInputWithoutLabel -->
							<input type="text" class="form-control" id="at-quick-name" maxlength="64" placeholder="Название"
								onkeydown="if(event.key==='Enter'){event.preventDefault();quickAddAccessType();}">
							<button type="button" class="btn btn-success" id="at-quick-create"
								onclick="quickAddAccessType()">Создать</button>
						</div>
						<div class="mt-1">
							<div class="form-check form-check-inline" title="Доступ на уровне приложения">
								<input type="checkbox" class="form-check-input" id="at-quick-is-app">
								<label class="form-check-label" for="at-quick-is-app">Приложение</label>
							</div>
							<div class="form-check form-check-inline" title="Доступ по IP (разрешения на фаерволе)">
								<input type="checkbox" class="form-check-input" id="at-quick-is-ip"
									onchange="$('#at-quick-ip-params-group').toggleClass('d-none',!this.checked)">
								<label class="form-check-label" for="at-quick-is-ip">IP</label>
							</div>
							<div class="form-check form-check-inline" title="Доступ уровня телефонии">
								<input type="checkbox" class="form-check-input" id="at-quick-is-phone">
								<label class="form-check-label" for="at-quick-is-phone">Телефония</label>
							</div>
							<div class="form-check form-check-inline" title="Доступ через VPN">
								<input type="checkbox" class="form-check-input" id="at-quick-is-vpn">
								<label class="form-check-label" for="at-quick-is-vpn">VPN</label>
							</div>
						</div>
						<div class="input-group input-group-sm mt-1 d-none" id="at-quick-ip-params-group">
							<span class="input-group-text">IP параметры по умолчанию</span>
							<!--suppress HtmlFormInputWithoutLabel -->
							<input type="text" class="form-control" id="at-quick-ip-params" placeholder="например TCP 443">
						</div>
					</div>
					<?php $pickerTools=ob_get_clean(); ?>
					<?= $form->field($model, 'access_types_ids',[
						'template'=>"{label}\n$pickerTools\n{input}\n{hint}\n{error}",
					])->checkboxList($accessTypesItems,[
						'class'=>'card d-flex flex-wrap flex-row pt-2 pb-1 overflow-auto',
						'style'=>'max-height:40vh',
						'onchange'=>'updateAccessTypes()',
						'item'=>function($index,$label,$name,$checked,$value) use ($accessTypesById,$ipParams) {
							/** @var AccessTypes $type */
							$type=$accessTypesById[$value];
							$inputId="ace-access-type-$value";
							$row=Html::tag('div',
								Html::checkbox($name,$checked,['value'=>$value,'id'=>$inputId,'class'=>'form-check-input'])
								.Html::label(Html::encode($label),$inputId,['class'=>'form-check-label']),
								['class'=>'form-check']
							);
							//у выбранных IP-типов инпут кастомизации сетевых параметров в той же строке
							if ($checked && $type->is_ip) {
								$row.=Html::tag('div',
									Html::tag('span','IP параметры',[
										'class'=>'input-group-text',
										'id'=>"access_type_{$value}_param_label",
									])
									.Html::input('text',"Aces[ipParams][$value]",$ipParams[$value]??$type->ip_params_def,[
										'class'=>'form-control',
										'aria-describedby'=>"access_type_{$value}_param_label",
									]),
									['class'=>'access-type-param input-group input-group-sm ms-3 flex-grow-1 w-auto','id'=>"access_type_{$value}_param"]
								);
							}
							return Html::tag('div',$row,[
								'class'=>'access-type-item pb-1 ps-2 pe-2 d-flex align-items-center '.($checked?'order-0 w-100 selected':'order-1'),
								'data'=>[
									'type-id'=>$value,
									'name'=>mb_strtolower($label),
									'is-ip'=>$type->is_ip?1:0,
								],
							]);
						},
					]) ?>
					<template id="access-type-item-template">
						<div class="access-type-item p-2 d-flex align-items-center order-1">
							<div class="form-check">
								<input type="checkbox" class="form-check-input" name="Aces[access_types_ids][]">
								<label class="form-check-label"></label>
							</div>
						</div>
					</template>
				</div>
			</div>
			<hr />
			<?= $form->field($model, 'notepad')->text(['height'=>100,'rows'=>6]) ?>
		</div>
		<?= $form->field($model,"acls_id")->hiddenInput()->label(false)->hint(false) ?>
	</div>
