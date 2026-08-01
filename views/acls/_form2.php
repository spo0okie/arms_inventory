<?php

use app\components\Forms\ArmsForm;
use app\components\widgets\page\ModelWidget;
use app\models\Acls;
use app\models\Comps;
use app\models\NetIps;
use app\models\Networks;
use app\models\Services;
use app\models\Techs;
use kartik\tabs\TabsX;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */
/* @var $ace app\models\Aces */
/* @var $form yii\widgets\ActiveForm */
/* @var $schedule app\modules\schedules\models\Schedules|null расписание при создании нового временного доступа */
if (!isset($modalParent)) $modalParent=null;
if (!isset($schedule)) $schedule=null;

/** @noinspection JSUnusedLocalSymbolsInspection */
/** @noinspection JSUnusedLocalSymbols */
$js= <<<JS
commentInput="input#acls-comment";
compInput="select#acls-comps_id";
techInput="select#acls-techs_id";
ipInput="select#acls-ips_id";
netInput="select#acls-networks_id";
srvInput="select#acls-services_id";
function onInputUpdate(input) {
    //console.log("clearing not "+input+": "+$(input).val())
    if ($(input).val()) {
        [commentInput,compInput,techInput,ipInput,srvInput,netInput].forEach(item => {
            if (item !== input) {
                //console.log("clearing "+item)
            	$(item).val("").trigger("change");
            }
        })
	}
}
JS;

//mutual-exclusion ресурсов нужен только для одиночного редактирования (TabsX);
//в форме группового создания ресурсы выбираются мультиселектами и взаимоисключение не нужно
if (!$model->isNewRecord) $this->registerJs($js,yii\web\View::POS_HEAD);

$defaultTypesUrl=Url::to(['/services/default-access-types']);
/** @noinspection JSUnusedLocalSymbols */
$defaultsJs= <<<JS
/*
 * Дефолтные типы доступа выбранных сервисов-ресурсов (issue #204):
 * при изменении мультиселекта сервисов галочки типов доступа предзаполняются
 * объединением дефолтов выбранных сервисов. Типы, которые пользователь щёлкал
 * руками, не трогаем; авто-выставленные снимаются при выпадении из дефолтов.
 */
let serviceDefaultTypes=[];	//текущие авто-выставленные (id строками)
let userTouchedTypes={};	//типы, которые пользователь щёлкал руками

$(document).on('change','input[name="Aces[access_types_ids][]"]',function(){
	userTouchedTypes[String($(this).val())]=true;
});

//typesMap: { access_types_id: {ip_params: строка-переопределение | null}, ... }
function applyServiceDefaultAccessTypes(typesMap) {
	typesMap=typesMap||{};
	let defaults=Object.keys(typesMap).map(String);
	//снимаем прежние авто-галочки, выпавшие из дефолтов (заблокированные дочерние не трогаем)
	serviceDefaultTypes.forEach(function(id){
		if (defaults.indexOf(id)<0 && !userTouchedTypes[id]) {
			$('input[name="Aces[access_types_ids][]"][value='+id+']:not(:disabled)').prop('checked',false);
		}
	});
	defaults.forEach(function(id){
		if (!userTouchedTypes[id]) {
			$('input[name="Aces[access_types_ids][]"][value='+id+']').prop('checked',true);
			//сервисное переопределение сетевых параметров (например HTTPS на порту 8140):
			//создаём инпут параметров с ним раньше, чем updateAccessTypes подтянет
			//дефолт типа (ensureAccessTypeParamInput существующий инпут не трогает)
			let p=typesMap[id] && typesMap[id].ip_params;
			if (p) ensureAccessTypeParamInput(id,{default_param:p});
		}
	});
	serviceDefaultTypes=defaults;
	updateAccessTypes();
}

function fetchServiceDefaultAccessTypes() {
	let ids=$('select#acls-services_ids').val()||[];
	if (!ids.length) {
		applyServiceDefaultAccessTypes({});
		return;
	}
	$.getJSON('{$defaultTypesUrl}',{ids:ids},applyServiceDefaultAccessTypes);
}

$(document).on('change','select#acls-services_ids',fetchServiceDefaultAccessTypes);
JS;
if ($model->isNewRecord) {
	$this->registerJs($defaultsJs,yii\web\View::POS_HEAD);
	//предзаполнение сервисов из GET (ссылки «создать доступ» со страницы сервиса):
	//применяем дефолты сразу при открытии, но только если галочки ещё не выставлены
	//(POST-перерисовка после ошибок валидации восстанавливает выбор пользователя)
	$this->registerJs(
		'if (!$(\'input[name="Aces[access_types_ids][]"]:checked\').length) fetchServiceDefaultAccessTypes();'
	);
}

?>

<div class="acls-form">
    <?php $form = ArmsForm::begin([
		'model'=>$model,
		'id' => 'acls-form',
		//в форме группового создания валидируем массивы *_ids (сценарий group)
	] + ($model->isNewRecord?['validationUrl'=>['/acls/validate','scenario'=>Acls::SCENARIO_GROUP]]:[])); ?>
	<?= $form->field($model,'schedules_id')->hiddenInput()->label(false)->hint(false) ?>

	<?php if ($schedule) {
			//режим создания нового временного доступа: расписание создаётся вместе с ACL (issue #214)
			echo Html::hiddenInput('newSchedule',1);
			?>
			<div class="card bg-light mb-3">
				<div class="card-header">Новый временный доступ <small class="text-muted">(создаётся вместе с этим доступом)</small></div>
				<div class="card-body">
					<?= $form->field($schedule,'name')->hint(Acls::$scheduleNameHint) ?>
					<?= $form->field($schedule,'history')->text(['rows'=>5,'height'=>100])->label(Acls::$scheduleHistoryHint) ?>
				</div>
			</div>
		<?php } ?>

		<div class="for-alert"></div>
	<div class="row">
		<div class="<?= $model->isNewRecord?'col-md-8':'col-md-6' ?>">
			<?php if ($model->isNewRecord) {
				echo $this->render('/aces/_form_layout', ['model' => $ace,'form'=>$form]);
			} else { ?>
				<div class="card bg-light">
					<div class="card-header">Выберите кому и какой предоставляется доступ</div>
					<div class="card-body">
						<div id="aces-list">

							<?php foreach ($model->aces as $ace) {
								echo ModelWidget::widget(['model'=>$ace,'view'=>'card']);
							}?>
						</div>

						<?= Html::a('<span class="fas fa-plus"></span>', [
							'aces/create',
							'Aces[acls_id]' => $model->id,
							'modal' => 'modal_form_loader'
						], [
							'class' => 'btn btn-primary btn-sm open-in-modal-form',
							'title' => 'Добавление элемента в список доступа',
							'data-update-element' => '#aces-list',
							'data-update-url' => Url::to(['/acls/ace-cards', 'id' => $model->id]),
						]); ?>
					</div>
				</div>
			<?php }?>
		</div>
		<div class="<?= $model->isNewRecord?'col-md-4':'col-md-6' ?>">
			<?php if ($model->isNewRecord) { ?>
				<div class="card bg-light">
					<div class="card-header">Выберите ресурсы, к которым предоставляется доступ<br>
						<small class="text-muted">можно выбрать несколько ресурсов разных типов — на каждый будет создан отдельный доступ с одинаковым набором ACE</small>
					</div>
					<div class="card-body">
						<?php
						//Групповые поля выбора ресурсов — массивы *_ids (мультиселект). Валидация
						//«хотя бы один ресурс» работает корректно (attrIsEmpty для *_ids понимает массивы).
						?>
						<?= $form->field($model, 'comps_ids')->select2(['data'=>Comps::fetchNames()])->label('ОС') ?>
						<?= $form->field($model, 'techs_ids')->select2(['data'=>Techs::fetchNames()])->label('Оборудование') ?>
						<?= $form->field($model, 'ips_ids')->select2(['data'=>NetIps::fetchNames()])->label('IP адреса') ?>
						<?= $form->field($model, 'networks_ids')->select2(['data'=>Networks::fetchNames()])->label('IP сети') ?>
						<?= $form->field($model, 'services_ids')->select2(['data'=>Services::fetchNames()])->label('Сервисы') ?>
						<?= $form->field($model, 'comment')->textInput(['maxlength' => true])->label('Другое (описание)') ?>
					</div>
				</div>
			<?php } else { ?>
				<div class="card bg-light">
					<div class="card-header">Выберите <b>один</b> ресурс к которому предоставляется доступ</div>
					<div class="card-body">
						<?= TabsX::widget([
							'items'=>[
								[
									'label'=>'ОС',
									'content'=>$form->field($model, 'comps_id')->select2(['options'=>['onchange' => 'onInputUpdate(compInput)']]),
									'active'=>(bool)$model->comps_id
								],
								[
									'label'=>'Оборудование',
									'content'=>$form->field($model, 'techs_id')->select2(['options'=>['onchange' => 'onInputUpdate(techInput)']]),
									'active'=>(bool)$model->techs_id
								],
								[
									'label'=>'IP адрес',
									'content'=>$form->field($model, 'ips_id')->select2(['options'=>['onchange' => 'onInputUpdate(ipInput)']]),
									'active'=>(bool)$model->ips_id
								],
								[
									'label'=>'IP сеть',
									'content'=>$form->field($model, 'networks_id')->select2(['options'=>['onchange' => 'onInputUpdate(netInput)']]),
									'active'=>(bool)$model->networks_id
								],
								[
									'label'=>'Сервис',
									'content'=>$form->field($model, 'services_id')->select2(['options'=>['onchange' => 'onInputUpdate(srvInput)']]),
									'active'=>(bool)$model->services_id
								],
								[
									'label'=>'Другое',
									'content'=>$form->field($model, 'comment')->textInput([
										'maxlength' => true,
										'onchange'=>'onInputUpdate(commentInput)'
									]),
									'active'=>!($model->services_id||$model->comps_id||$model->techs_id||$model->ips_id||$model->networks_id)
								],
							],
							'position'=>TabsX::POS_ABOVE,
							//'align'=>TabsX::ALIGN_CENTER,
							'encodeLabels'=>false,
							'bordered'=>true,

						]) ?>
					</div>
				</div>
			<?php } ?>
			<?= $form->field($model, 'notepad')->text(['height'=>100,'rows'=>6]) ?>
		</div>
	</div>


	<?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
	<?= Html::Button('Применить',	[
			'class' => 'btn btn-primary',
			'onClick' => '$("form#acls-form").attr("action",
				$("#acls-form").attr("action") + ($("#acls-form").attr("action").indexOf("?")>=0?"&":"?") +	"accept=1"
			); $("form#acls-form").trigger("submit");'
		]) ?>

    <?php ArmsForm::end(); ?>

</div>
