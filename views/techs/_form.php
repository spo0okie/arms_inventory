<?php

use app\helpers\FieldsHelper;
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
		$techModels=\app\models\TechModels::fetchPhones();
		break;
    case 'pc':
	    $techModels=\app\models\TechModels::fetchPCs();
	    break;
    default:
	    $techModels=\app\models\TechModels::fetchNames();
        break;
}

$no_specs_hint=\app\models\TechModels::$no_specs_hint;
$hint_icon=FieldsHelper::labelHintIcon;

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
				if (data=="$no_specs_hint") {
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
	$('#techs-model_id, #techs-places_id, #techs-arms_id').on('change', function(){
		$.ajax({
			url: '/web/techs/inv-num?model_id='+
			$('#techs-model_id').val()
			+'&place_id='+
			$('#techs-places_id').val()
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
        <div class="col-md-4" >
			<?= FieldsHelper::TextInputField($form,$model, 'num') ?>
        </div>
        <div class="col-md-4" >
			<?= FieldsHelper::TextInputField($form,$model, 'inv_num') ?>
        </div>
        <div class="col-md-4" >
			<?= FieldsHelper::TextInputField($form,$model, 'sn') ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6" >
			<?= FieldsHelper::Select2Field($form,$model, 'model_id', [
				'data' => $techModels,
				'itemsHintsUrl'=>\yii\helpers\Url::to(['/tech-models/ttip','q'=>'dummyVar']),
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
			<?php if (count($model->comps)) {
				echo FieldsHelper::Select2Field($form, $model, 'comp_id', [
					'data' => \yii\helpers\ArrayHelper::map($model->comps, 'id', 'name'),
					'itemsHintsUrl'=>\yii\helpers\Url::to(['/comps/ttip','q'=>'dummyVar']),
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
				'data' => \app\models\TechStates::fetchNames(),
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
			<?= $form->field($model, 'specs')->textarea(['rows' => max(6,count(explode("\n",$model->history)))]) ?>
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
			<?= FieldsHelper::TextAutoresizeField($form,$model,'ip',['lines' => 1,]) ?>
		</div>
		<div class="col-md-6" >
			<?= FieldsHelper::TextAutoresizeField($form,$model,'mac',['lines' => 1,]) ?>
		</div>
	</div>

	<div class="row">
		<div class="col-md-6"  id="tech-arms-selector" <?= ($model->installed_id)?$hidden:'' ?>>
			<?= FieldsHelper::Select2Field($form,$model, 'arms_id', [
				'data' => \app\models\Techs::fetchArmNames(),
				'itemsHintsUrl'=>\yii\helpers\Url::to(['/techs/ttip','q'=>'dummyVar']),
				'options' => ['placeholder' => 'Выберите АРМ в состав которого входит это оборудование',],
				'pluginEvents' =>[
					'change'=>'function(){
                        if ($("#techs-arms_id").val()) {
                            $("#tech-users-selector, #tech-installed-selector, #tech-departments-selector").hide();
                        } else {
                            $("#tech-users-selector, #tech-installed-selector, #tech-departments-selector").show();
                        }
                    }'
				],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
				]
			])->hint(\app\models\Contracts::fetchArmsHint($model->contracts_ids,'techs'),['id'=>'arms_id-hint']) ?>
		</div>
		<div class="col-md-4 mt-2" id="tech-installed-param" <?= ($model->installed_id)?'':$hidden ?>>
			<div class="d-flex flex-row-reverse">
				<div class="pe-2">
					<br><?= FieldsHelper::CheckboxField($form,$model,'full_length') ?>
				</div>
				<div class="pe-2">
					<br><?= FieldsHelper::CheckboxField($form,$model,'installed_back') ?>
				</div>
			</div>
		</div>
		<div class="col-md-2" id="tech-installed-pos" <?= ($model->installed_id)?'':$hidden ?>>
			<?= FieldsHelper::TextInputField($form,$model,'installed_pos') ?>
		</div>
		<div class="col-md-6" id="tech-installed-selector" <?= ($model->arms_id)?$hidden:'' ?>>
			<?= FieldsHelper::Select2Field($form,$model,'installed_id', [
				'data' => \app\models\Techs::fetchNames(),
				'itemsHintsUrl'=>\yii\helpers\Url::to(['/techs/ttip','q'=>'dummyVar']),
				'options' => ['placeholder' => 'Выберите оборудование куда установлено это устройство',],
				'pluginEvents' =>[
                    'change'=>'function(){
                        if ($("#techs-installed_id").val()) {
                            $("#tech-place-selector, #tech-arms-selector").hide();
                            $("#tech-installed-pos, #tech-installed-param").show();
                        } else {
                            $("#tech-arms-selector, #tech-place-selector").show();
                            $("#tech-installed-pos, #tech-installed-param").hide();
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
				'data' => \app\models\Places::fetchNames(),
				'options' => ['placeholder' => 'Выберите помещение',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
				]
			]) ?>
		</div>
		<div class="col-md-6">
			<?= FieldsHelper::Select2Field($form,$model, 'departments_id', [
				'data' => \app\models\Departments::fetchNames(),
				'options' => ['placeholder' => 'Выберите подразделение',],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
				]
			]) ?>
		</div>
	</div>
	
    <div id="tech-users-selector" <?= ($model->arms_id)?$hidden:'' ?>>
		<div class="row">
			<div class="col-md-6" >
				<?= FieldsHelper::Select2Field($form,$model, 'user_id', [
					'data' => \app\models\Users::fetchWorking(),
					'itemsHintsUrl'=>\yii\helpers\Url::to(['/users/ttip','q'=>'dummyVar']),
					'options' => ['placeholder' => 'Выберите сотрудника',],
					'pluginOptions' => [
						'dropdownParent' => $modalParent,
					]
				]) ?>

			</div>
			<div class="col-md-6" >
				<?= FieldsHelper::Select2Field($form,$model, 'head_id', [
					'data' => \app\models\Users::fetchWorking(),
					'itemsHintsUrl'=>\yii\helpers\Url::to(['/users/ttip','q'=>'dummyVar']),
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
					'data' => \app\models\Users::fetchWorking(),
					'itemsHintsUrl'=>\yii\helpers\Url::to(['/users/ttip','q'=>'dummyVar']),
					'options' => ['placeholder' => 'Выберите сотрудника',],
					'pluginOptions' => [
						'dropdownParent' => $modalParent,
					]
				]) ?>
			</div>
			<div class="col-md-6" >
				<?= FieldsHelper::Select2Field($form,$model,'responsible_id', [
					'data' => \app\models\Users::fetchWorking(),
					'itemsHintsUrl'=>\yii\helpers\Url::to(['/users/ttip','q'=>'dummyVar']),
					'options' => ['placeholder' => 'Выберите сотрудника',],
					'pluginOptions' => [
						'dropdownParent' => $modalParent,
					]
				]) ?>
			</div>
		</div>
    </div>
	
	<?= FieldsHelper::Select2Field($form,$model, 'contracts_ids', [
		'data' => \app\models\Contracts::fetchNames(),
		'itemsHintsUrl'=>\yii\helpers\Url::to(['/contracts/ttip','q'=>'dummyVar']),
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
