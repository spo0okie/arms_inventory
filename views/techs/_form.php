<?php

use app\helpers\FieldsHelper;
use app\models\Contracts;
use app\models\Departments;
use app\models\Domains;
use app\models\MaintenanceJobs;
use app\models\MaintenanceReqs;
use app\models\Partners;
use app\models\Places;
use app\models\Services;
use app\models\Techs;
use app\models\TechStates;
use app\models\Users;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use app\models\TechModels;

/*
 * Для порядку: список жабаскриптовых свистоперделок:
 * fetchArmsFromDocs - подгружает список АРМ из документов прикрепленных к оборудованию
 * надо бы учитывать текущую модель: если мы сами АРМ, то не надо ничего предлагать
 *
 * fetchCommentFromModel - меняет название и подсказку примечания для телефонов
 * (ну и если где-то еще в оборудовании переопределено)
 * также открывает окно спецификаций и грузит в него подсказки, если для модели это определено
 *
 * при выборе АРМ скрываются соответствующие поля (которые подтягиваются из АРМ)
 *
 * для новых моделей подтягивается код формирования инвентарного номера
 */

/* @var $this yii\web\View */
/* @var $model app\models\Techs */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

$hidden=' style="display:none" ';
switch (Yii::$app->request->get('type')) {
	case 'phone':
		$techModels= TechModels::fetchPhones();
		break;
    case 'pc':
	    $techModels= TechModels::fetchPCs();
	    break;
    default:
	    $techModels= TechModels::fetchNames();
        break;
}

$no_specs_hint= TechModels::$no_specs_hint;
$hint_icon=FieldsHelper::labelHintIcon;
/** @var @noinspect $js */
/** @noinspection JSUnusedLocalSymbols */
$js =  /** @lang JavaScript */ <<<JS
    //меняем подсказку выбора арм в при смене списка документов

    function fetchArmsFromDocs(){
        let docs=$("#techs-contracts_ids").val();
        //console.log(docs);
        $.ajax({url: "/web/contracts/hint-arms?form=techs&ids="+docs})
        .done(function(data) {
            $("#arms_id-hint").html(data);
        })
        .fail(function () {console.log("Ошибка получения данных!")});
    }
    
    //меняем подсказки для разных типов оборудования
    function fetchCommentFromModel(){
        let model_id=$("#techs-model_id").val();
        //console.log(model_id);
        
        $.ajax({url: "/web/tech-models/hint-comment?id="+model_id})
        .done(function(data) {
            $('label[for="techs-comment"]')
            	.html(data['name']+' $hint_icon')
            	.attr('qtip_ttip',data['hint'])
            	.tooltipster('destroy');
            //$("#comment-hint").html(data['hint']);
        })
        .fail(function () {console.log("Ошибка получения данных!")});
        
		$.ajax({url: "/web/tech-models/hint-template?id="+model_id})
			.done(function(data) {
				if (data==="$no_specs_hint") {
					$("#techs-specs_settings").hide();
				} else {
					$("#techs-hint").html(data);
					$("#techs-specs_settings").show();
				}
			})
			.fail(function () {console.log("Ошибка получения данных!")});
		
		$.ajax({url: "/web/tech-models/hint-description?id="+model_id})
			.done(function(data) {
				$("#model-hint").html(data);
			})
			.fail(function () {console.log("Ошибка получения данных!")});
    }
    
JS;
$this->registerJs($js, yii\web\View::POS_BEGIN);

//формирование инвентарника регистрируем только для новых моделей
$formInvNumJs = /** @lang JavaScript */ <<<JS
	$('#techs-model_id, #techs-places_id, #techs-arms_id, #techs-installed_id, #techs-partners_id').on('change', function(){
		$.ajax({
			url: '/web/techs/inv-num?model_id='+
			$('#techs-model_id').val()
			+'&place_id='+
			$('#techs-places_id').val()
			+'&org_id='+
			$('#techs-partners_id').val()
			+'&installed_id='+
			$('#techs-installed_id').val()
			+'&arm_id='+
			$('#techs-arms_id').val(),
			success: function(data) {
				$('#techs-num').val(data);
			}
		});
	});
JS;

if ($model->isNewRecord) $this->registerJs($formInvNumJs,yii\web\View::POS_LOAD);

?>

<div class="techs-form">

    <?php $form = ActiveForm::begin([
	    'id'=>'techs-edit-form',
	    'enableClientValidation' => false,
	    'enableAjaxValidation' => true,
	    'validateOnBlur' => true,
	    'validateOnChange' => true,
	    'validateOnSubmit' => true,
	    'validationUrl' => $model->isNewRecord?
			['techs/validate']:
			['techs/validate','id'=>$model->id],
	    'action' => $model->isNewRecord?
			['techs/create']:
			['techs/update','id'=>$model->id,'return'=>'previous'],
    ]); ?>

    <div class="row">
        <div class="col-md-3" >
			<?= FieldsHelper::TextInputField($form,$model, 'num') ?>
        </div>
        <div class="col-md-3" >
			<?= FieldsHelper::TextInputField($form,$model, 'inv_num') ?>
        </div>
		<div class="col-md-3" >
			<?= FieldsHelper::TextInputField($form,$model, 'sn') ?>
		</div>
		<div class="col-md-3" >
			<?= FieldsHelper::TextInputField($form,$model, 'uid') ?>
		</div>
    </div>
	
	<?php if (
		Yii::$app->params['techs.hostname.enable'] &&
		(
			(is_object($model->model) && !$model->model->getIsPC()) //если модель есть и это не ПК
			||
			(!is_object($model->model)) //или модели нет
		)
	) { ?>
		<div class="row">
			<div class="col-6">
				<?= $form->field($model, 'hostname')->textInput(['maxlength' => true]) ?>
			</div>
			<div class="col-6">
				<?= FieldsHelper::Select2Field($form,$model, 'domain_id',[
					'data'=> Domains::fetchNames(),
					'pluginOptions' => [
						'dropdownParent' => $modalParent,
					],
				]) ?>
			</div>
		</div>
	<?php } ?>

    <div class="row">
        <div class="col-md-4" >
			<?= FieldsHelper::Select2Field($form,$model, 'model_id', [
				'data' => $techModels,
				'hintModel'=>'TechModels',
				'options' => [
			        'placeholder' => 'Выберите модель',
					'onchange' => 'fetchCommentFromModel();'
                ],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => false,
				]
			]) ?>
        </div>
		<div class="col-md-2" >
			<?= FieldsHelper::Select2Field($form,$model,'partners_id',[
				'data'=> Partners::fetchNames()
			])?>
		</div>
		<div class="col-md-2" >
			<?php if (count($model->comps)) {
				echo FieldsHelper::Select2Field($form, $model, 'comp_id', [
					'data' => ArrayHelper::map($model->comps, 'id', 'name'),
					'hintModel'=>'Comps',
					'pluginOptions' => [
						'dropdownParent' => $modalParent,
						'allowClear' => false,
					]
				]);
			} else { ?>
				<div class="alert-striped w-100 p-2 mt-4 cursor-default"
					 qtip_ttip="Когда/если к этому оборудованию АРМ будут привязаны ОС (делается из формы редактирования самой ОС),
					 тогда тут можно будет выбрать какую из них считать основной">Отсутствуют привязанные ОС</div>
			<?php } ?>
		</div>
        <div class="col-md-2" >
			<?= FieldsHelper::Select2Field($form, $model, 'state_id', [
				'data' => TechStates::fetchNames(),
				'options' => ['placeholder' => 'Выберите состояние оборудования',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
				]
			]) ?>
        </div>
        <div class="col-md-2" >
		    <?= FieldsHelper::TextInputField($form,$model, 'comment',[
				'hint'=>TechModels::fetchTypeComment($model->model_id)['hint'],
				'label'=>TechModels::fetchTypeComment($model->model_id)['name']
			]) ?>
        </div>
    </div>
	
	<div class="row " id="techs-specs_settings"
		<?= (is_object($model) && is_object($model->model) && $model->model->individual_specs)?'':'style="display:none"' ?>
	>
		<div class="col-md-4" >
			<?= $form->field($model, 'specs')->textarea(['rows' => max(6,count(explode("\n",$model->specs)))]) ?>
		</div>
		<div class="col-md-4" >
			<label class="control-label" >
				Подсказка для заполнения спеки
			</label>
			<br />
			<div id="specs-hint" class="hint-block">
				<?php
				if(is_object($model) && is_object($model->model))
					echo Yii::$app->formatter->asNtext($model->model->type->comment)
				?>
			</div>
		</div>
		<div class="col-md-4" >
			<label class="control-label" >
				Описание модели
			</label>

			<div id="model-hint" class="hint-block">
				Эти данные не нужно вносить в индивидуальную спеку:<br />
				<?php
				if(is_object($model) && is_object($model->model))
					echo Yii::$app->formatter->asNtext($model->model->comment)
				?>
			</div>
		</div>
	</div>


	<div class="row">
		<div class="col-md-6" >
			<?= FieldsHelper::TextAutoresizeField($form,$model,'ip',['lines' => 2,]) ?>
		</div>
		<div class="col-md-6" >
			<?= FieldsHelper::TextAutoresizeField($form,$model,'mac',['lines' => 1,]) ?>
		</div>
	</div>

	<div class="row">
		<div class="col-md-6"  id="tech-arms-selector" <?= ($model->installed_id)?$hidden:'' ?>>
			<?= FieldsHelper::Select2Field($form,$model, 'arms_id', [
				'data' => Techs::fetchArmNames(),
				'hintModel'=>'Techs',
				'options' => ['placeholder' => 'Выберите АРМ в состав которого входит это оборудование',],
				'pluginEvents' =>[
					'change'=>'function(){
                        if ($("#techs-arms_id").val()) {
                            $("#tech-users-selector, #tech-installed-selector, #tech-departments-selector, #tech-place-selector").hide();
                        } else {
                            $("#tech-users-selector, #tech-installed-selector, #tech-departments-selector, #tech-place-selector").show();
                        }
                    }'
				],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
				]
			])->hint(Contracts::fetchArmsHint($model->contracts_ids,'techs'),['id'=>'arms_id-hint']) ?>
		</div>
		<div class="col-md-6" id="tech-installed-param" <?= ($model->installed_id)?'':$hidden ?>>
			<div class="row float-right	">
				<div class="col-md-6 ">
					<?= FieldsHelper::CheckboxField($form,$model,'full_length',[
						'onchange'=>'{
						if ($("#techs-full_length").is(":checked")) {
							$("#tech-installed-pos-end").show();
						} else {
							$("#tech-installed-pos-end").hide();
						}
						console.log("click");
					}'
					]) ?>
					<?= FieldsHelper::CheckboxField($form,$model,'installed_back') ?>
				</div>
				<div class="col-md-3" id="tech-installed-pos">
					<?= FieldsHelper::TextInputField($form,$model,'installed_pos') ?>
				</div>
				<div class="col-md-3" id="tech-installed-pos-end" <?= ($model->full_length)?'':$hidden ?>>
					<?= FieldsHelper::TextInputField($form,$model,'installed_pos_end') ?>
				</div>
				
			</div>
		</div>
		<div class="col-md-6" id="tech-installed-selector" <?= ($model->arms_id)?$hidden:'' ?>>
			<?= FieldsHelper::Select2Field($form,$model,'installed_id', [
				'data' => Techs::fetchNames(),
				'hintModel'=>'Techs',
				'options' => ['placeholder' => 'Выберите оборудование куда установлено это устройство',],
				'pluginEvents' =>[
                    'change'=>'function(){
                        if ($("#techs-installed_id").val()) {
                            $("#tech-place-selector, #tech-arms-selector").hide();
                            $("#tech-installed-param").show();
                        } else {
                            $("#tech-arms-selector, #tech-place-selector").show();
                            $("#tech-installed-param").hide();
                        }
                    }'
                ],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
				]
			]) ?>
		</div>
	</div>

	<div class="row" id="tech-departments-selector" <?= ($model->arms_id)?$hidden:'' ?>>
		<div class="col-md-6" id="tech-place-selector" <?= ($model->arms_id||$model->installed_id)?$hidden:'' ?>>
			<?= FieldsHelper::Select2Field($form,$model, 'places_id', [
				'data' => Places::fetchNames(),
				'hintModel'=>'Places',
				'options' => ['placeholder' => 'Выберите помещение',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
				]
			]) ?>
		</div>
		<div class="col-md-6">
			<?= Yii::$app->params['departments.enable']?FieldsHelper::Select2Field($form,$model, 'departments_id', [
				'data' => Departments::fetchNames(),
				'options' => ['placeholder' => 'Выберите подразделение',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
				]
			]):'' ?>
		</div>
	</div>
	
    <div id="tech-users-selector" <?= ($model->arms_id)?$hidden:'' ?>>
		<div class="row">
			<div class="col-md-6" >
				<?= FieldsHelper::Select2Field($form,$model, 'user_id', [
					'data' => Users::fetchWorking(),
					'hintModel'=>'Users',
					'options' => ['placeholder' => 'Выберите сотрудника',],
					'pluginOptions' => [
						'dropdownParent' => $modalParent,
					]
				]) ?>

			</div>
			<div class="col-md-6" >
				<?= FieldsHelper::Select2Field($form,$model, 'head_id', [
					'data' => Users::fetchWorking(),
					'hintModel'=>'Users',
					'options' => ['placeholder' => 'Выберите сотрудника',],
					'pluginOptions' => [
						'dropdownParent' => $modalParent,
					]
				]) ?>
			</div>
		</div>
		<div class="row">
			<div class="col-md-6" >
				<?= FieldsHelper::Select2Field($form,$model,'it_staff_id', [
					'data' => Users::fetchWorking(),
					'hintModel'=>'Users',
					'options' => ['placeholder' => 'Выберите сотрудника',],
					'pluginOptions' => [
						'dropdownParent' => $modalParent,
					]
				]) ?>
			</div>
			<div class="col-md-6" >
				<?= FieldsHelper::Select2Field($form,$model,'responsible_id', [
					'data' => Users::fetchWorking(),
					'hintModel'=>'Users',
					'options' => ['placeholder' => 'Выберите сотрудника',],
					'pluginOptions' => [
						'dropdownParent' => $modalParent,
					]
				]) ?>
			</div>
		</div>
    </div>
	<div class="row">
		<div class="col-md-6">
			<?= FieldsHelper::Select2Field($form,$model, 'services_ids', [
				'data' => Services::fetchNames(),
				'options' => ['placeholder' => 'Нет сервисов',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
		</div>
		<div class="col-md-3">
			<?= FieldsHelper::Select2Field($form,$model, 'maintenance_reqs_ids', [
				'data' => MaintenanceReqs::fetchNames(),
				'options' => ['placeholder' => 'Получать из сервисов',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
		</div>
		<div class="col-md-3">
			<?= FieldsHelper::Select2Field($form,$model, 'maintenance_jobs_ids', [
				'data' => MaintenanceJobs::fetchNames(),
				'options' => ['placeholder' => 'Отсутствуют',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => true
				]
			]) ?>
		</div>
	</div>
	
	<?= FieldsHelper::Select2Field($form,$model, 'contracts_ids', [
		'data' => Contracts::fetchNames(),
		'hintModel'=>'Contracts',
		'options' => [
            'placeholder' => 'Выберите документы',
			'onchange' => 'fetchArmsFromDocs();'
        ],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => true
		]
	])?>
	
	
	
	<?= FieldsHelper::TextAutoresizeField($form,$model,'url',['lines' => 2,]) ?>
	
	<?= FieldsHelper::TextAutoresizeField($form,$model,'history',['lines' => 3,]) ?>


	<div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
