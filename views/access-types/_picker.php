<?php

/**
 * Переиспользуемый пикер типов доступа (вынесен из views/aces/_form_layout.php):
 *  - выбранные типы всплывают в топ списка (flex order), у выбранных IP-типов инпут сетевых параметров в строке
 *  - фильтр по подстроке названия (выбранные видны всегда)
 *  - кнопка «+»: создание нового типа доступа через ШТАТНУЮ форму AccessTypes
 *    в модалке (open-in-modal-form + хук data-call-on-submit в layouts/main.php);
 *    созданный тип добавляется в список и отмечается
 *
 * Используется формой ACE (Aces[access_types_ids] + Aces[ipParams]) и формой сервиса
 * (Services[default_access_types_ids] + Services[defaultIpParams], режим override).
 *
 * Параметры:
 * @var yii\web\View $this
 * @var yii\widgets\ActiveForm $form
 * @var \app\models\base\ArmsModel $model модель-владелец атрибутов
 * @var string $attribute атрибут со списком ID типов доступа (checkboxList)
 * @var string $paramsAttribute виртуальный атрибут карты IP-параметров [type_id=>string]
 * @var bool $paramsOverride режим инпутов параметров:
 *      false (ACE) - значение = параметры записи или дефолт типа;
 *      true (сервис) - значение = только переопределение, дефолт типа в placeholder
 * @var bool $card оборачивать ли пикер в карточку с заголовком (label атрибута + иконка «?»);
 *      false - голый пикер для мест со своей обвязкой
 * @var string|null $label кастомный заголовок карточки (по умолчанию label атрибута)
 */

use app\components\AttributeTooltip;
use app\models\AccessTypes;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

if (!isset($paramsOverride)) $paramsOverride=false;
if (!isset($card)) $card=true;
if (!isset($label)) $label=null;

$accessTypesAll=AccessTypes::find()->orderBy(['name'=>SORT_ASC])->all();
$accessTypesById=ArrayHelper::index($accessTypesAll,'id');
$accessTypesItems=ArrayHelper::map($accessTypesAll,'id','name');
$paramsValues=$model->$paramsAttribute??[];

$bundleUrl=Url::to(['/access-types/access-types-form']);

$checkName=Html::getInputName($model,$attribute).'[]';
$paramPrefix=Html::getInputName($model,$paramsAttribute);
$listId=Html::getInputId($model,$attribute);
$overrideFlag=$paramsOverride?1:0;

/*
 * updateAccessTypes() передает в контроллер список выбранных типов и в ответ получает список типов,
 * которые должны быть выбраны (могут добавиться дочерние от комплексных - они блокируются от снятия),
 * а также параметры IP-типов для генерации инпутов
 */
/*
 * NB: код регистрируется в <head> и живет вне ready-обертки Yii (jQuery(function($){...})),
 * поэтому обращаемся к jQuery по полному имени, а не через глобальный `$`: на его месте
 * может оказаться что угодно (сторонние/инжектированные на страницу скрипты, noConflict),
 * и тогда вся ready-цепочка страницы падает с «$ is not a function» - форма перестает работать
 */
/** @noinspection JSUnusedLocalSymbols */
$js= <<<JS
//параметризация пикера (имена инпутов зависят от модели-владельца)
accessTypesCheckName='{$checkName}';
accessTypesParamPrefix='{$paramPrefix}';
accessTypesListId='{$listId}';
accessTypesParamsOverride={$overrideFlag};

//поднимает выбранные типы в топ (flex order), тогглит видимость инпутов IP-параметров, обновляет фильтр
function markAccessTypesSelection() {
	jQuery('div.access-type-item').each(function(i,el){
		let \$item=jQuery(el);
		let checked=\$item.find('input[type=checkbox]').prop('checked');
		\$item.toggleClass('order-0 w-100 selected',checked).toggleClass('order-1',!checked);
		\$item.find('div.access-type-param').toggleClass('d-none',!checked);
	});
	filterAccessTypes();
}

//фильтр списка по подстроке названия (выбранные видны всегда)
function filterAccessTypes() {
	let q=jQuery('input#access-types-filter').val().toLowerCase().trim();
	let total=0,shown=0;
	jQuery('div.access-type-item').each(function(i,el){
		let \$item=jQuery(el);
		total++;
		let visible=!q
			|| String(\$item.attr('data-name')).indexOf(q)>=0
			|| \$item.find('input[type=checkbox]').prop('checked');
		\$item.toggleClass('d-none',!visible);
		if (visible) shown++;
	});
	let \$count=jQuery('span#access-types-filter-count');
	if (shown<total) {
		\$count.text(shown+' / '+total).removeClass('d-none');
	} else {
		\$count.text('').addClass('d-none');
	}
}

//создает (если еще нет) инпут IP-параметров внутри строки типа type_id
function ensureAccessTypeParamInput(type_id,type) {
	let \$item=jQuery('div.access-type-item[data-type-id="'+type_id+'"]');
	if (!\$item.length || \$item.find('div.access-type-param').length) return;
	let \$group=jQuery('<div class="access-type-param input-group input-group-sm ms-3 flex-grow-1 w-auto"></div>')
		.attr('id','access_type_'+type_id+'_param');
	\$group.append(jQuery('<span class="input-group-text"></span>')
		.attr('id','access_type_'+type_id+'_param_label')
		.text('IP параметры'));
	let def=type.hasOwnProperty('default_param')?type.default_param:'';
	let \$input=jQuery('<input type="text" class="form-control">')
		.attr('name',accessTypesParamPrefix+'['+type_id+']')
		.attr('aria-describedby','access_type_'+type_id+'_param_label');
	//режим override (сервис): дефолт типа - в placeholder, значение только если переопределено
	if (accessTypesParamsOverride) \$input.attr('placeholder',def);
	else \$input.val(def);
	\$group.append(\$input);
	\$item.append(\$group);
}

function updateAccessTypes() {
	//сбрасываем прежние авто-добавленные дочерние типы (disabled == выставлен автоматически),
	//актуальные снова выставит ответ сервера
	jQuery('input[name="'+accessTypesCheckName+'"]:disabled').prop('disabled',false).prop('checked',false);
	let get_params=[];
	//получаем список выбранных типов
	jQuery('input[name="'+accessTypesCheckName+'"]:checked').each(function(i,el){
		get_params.push('access_types_ids[]='+jQuery(el).val());
	});
	markAccessTypesSelection();
	if (!get_params.length) return;
	//передаем в контроллер с типами доступа
	jQuery.ajax({
		url:'{$bundleUrl}?'+get_params.join('&'),
		success: function (data) {
			for (let i in data) if (data.hasOwnProperty(i)) {
				let type=data[i];
				if (type.hasOwnProperty('optional')) {
					jQuery('input[name="'+accessTypesCheckName+'"][value='+i+']')
						.prop('checked',true)
						.prop('disabled',!(type.optional));
				}
				if (type.hasOwnProperty('is_ip')) ensureAccessTypeParamInput(i,type);
			}
			markAccessTypesSelection();
		}
	});
}

//добавляет в список чекбокс нового (созданного через модалку) типа доступа
function addAccessTypeItem(model) {
	let \$item=jQuery(jQuery('template#access-type-item-template').html());
	let inputId='picker-access-type-'+model.id;
	\$item.attr('data-type-id',model.id)
		.attr('data-name',String(model.name||'').toLowerCase())
		.attr('data-is-ip',model.is_ip?1:0);
	\$item.find('input[type=checkbox]')
		.attr('name',accessTypesCheckName)
		.attr('value',model.id).attr('id',inputId).prop('checked',true);
	\$item.find('label').attr('for',inputId).text(model.name);
	//вставляем по алфавиту
	let inserted=false;
	jQuery('div#'+accessTypesListId+' div.access-type-item').each(function(i,el){
		if (!inserted && jQuery(el).attr('data-name')>\$item.attr('data-name')) {
			\$item.insertBefore(jQuery(el));
			inserted=true;
		}
	});
	if (!inserted) jQuery('div#'+accessTypesListId).append(\$item);
}

//приемник модалки создания типа доступа (data-call-on-submit):
//добавляет созданный тип в список отмеченным
function accessTypePickerCreated(data) {
	//defaultReturn отдает модель обернутой в массив(ы) - разворачиваем
	let model=data;
	while (Array.isArray(model)) model=model[0];
	if (typeof model==='string') {
		try { model=JSON.parse(model); } catch (e) { model=null; }
		while (Array.isArray(model)) model=model[0];
	}
	if (!model || !model.id) return;
	//если тип с таким именем уже в списке (защита от дублей) - просто отмечаем
	let lower=String(model.name||'').toLowerCase();
	let \$existing=jQuery('div.access-type-item').filter(function(){return jQuery(this).attr('data-name')===lower;});
	if (\$existing.length) {
		\$existing.find('input[type=checkbox]').prop('checked',true);
	} else {
		addAccessTypeItem(model);
	}
	updateAccessTypes();
}
JS;
$this->registerJs($js, View::POS_HEAD);
//вызываем нашу функцию после загрузки формы, т.к. может быть нужно поотключать некоторые чекбоксы если они дочерние доступы
$this->registerJs('updateAccessTypes()');

//фильтр и кнопка создания встраиваются в template поля между label и списком,
//чтобы заголовок поля был над всем пикером
ob_start(); ?>
<div class="input-group input-group-sm mb-2">
	<input type="text" class="form-control" id="access-types-filter"
		placeholder="Фильтр по названию…" oninput="filterAccessTypes()">
	<span class="input-group-text d-none" id="access-types-filter-count"></span>
	<?/*создание нового типа доступа - штатной формой AccessTypes в модалке;
	созданную модель вернет хук data-call-on-submit (layouts/main.php)*/?>
	<?= Html::a('<span class="fas fa-plus"></span>',
		['/access-types/create','modal'=>'modal_form_loader'],[
			'class'=>'btn btn-outline-primary open-in-modal-form',
			'qtip_ttip'=>'Создать новый тип доступа',
			'data-call-on-submit'=>'accessTypePickerCreated',
			'data'=>['pjax'=>0],
		]) ?>
</div>
<?php $pickerTools=ob_get_clean();

//в карточке заголовок (label+«?») выносится в card-header - {label}/{hint} поля не дублируем
$fieldTemplate=$card?
	"$pickerTools\n{input}\n{error}":
	"{label}\n$pickerTools\n{input}\n{hint}\n{error}";

$picker=$form->field($model, $attribute,[
	'template'=>$fieldTemplate,
])->checkboxList($accessTypesItems,[
	'class'=>'card d-flex flex-wrap flex-row pt-2 pb-1 overflow-auto',
	'style'=>'max-height:40vh',
	'onchange'=>'updateAccessTypes()',
	'item'=>function($index,$label,$name,$checked,$value) use ($accessTypesById,$paramsValues,$paramPrefix,$paramsOverride) {
		/** @var AccessTypes $type */
		$type=$accessTypesById[$value];
		$inputId="picker-access-type-$value";
		$row=Html::tag('div',
			Html::checkbox($name,$checked,['value'=>$value,'id'=>$inputId,'class'=>'form-check-input'])
			.Html::label(Html::encode($label),$inputId,['class'=>'form-check-label']),
			['class'=>'form-check']
		);
		//у выбранных IP-типов инпут кастомизации сетевых параметров в той же строке
		if ($checked && $type->is_ip) {
			$inputOptions=[
				'class'=>'form-control',
				'aria-describedby'=>"access_type_{$value}_param_label",
			];
			if ($paramsOverride) {
				//режим переопределения (сервис): значение — только переопределение,
				//дефолт типа показываем в placeholder
				$inputValue=$paramsValues[$value]??'';
				$inputOptions['placeholder']=$type->ip_params_def;
			} else {
				$inputValue=$paramsValues[$value]??$type->ip_params_def;
			}
			$row.=Html::tag('div',
				Html::tag('span','IP параметры',[
					'class'=>'input-group-text',
					'id'=>"access_type_{$value}_param_label",
				])
				.Html::input('text',"{$paramPrefix}[$value]",$inputValue,$inputOptions),
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
]);

$template='<template id="access-type-item-template">
	<div class="access-type-item p-2 d-flex align-items-center order-1">
		<div class="form-check">
			<input type="checkbox" class="form-check-input">
			<label class="form-check-label"></label>
		</div>
	</div>
</template>';

if ($card) { ?>
	<div class="card bg-light mb-3">
		<div class="card-header"><?= Html::encode($label??$model->getAttributeLabel($attribute)) ?>
			<?/*подача подсказки атрибута - иконкой «?» (ui-sources.md §0.1)*/?>
			<?= AttributeTooltip::icon(AttributeTooltip::build(
				$model,$attribute,AttributeTooltip::MODE_FORM
			)) ?>
		</div>
		<div class="card-body">
			<?= $picker ?>
		</div>
	</div>
	<?= $template ?>
<?php } else { ?>
	<?= $picker ?>
	<?= $template ?>
<?php } ?>
