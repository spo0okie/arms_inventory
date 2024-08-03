<?php

use app\models\Contracts;
use app\models\Currency;
use app\models\Materials;
use app\models\MaterialsTypes;
use app\models\Places;
use app\models\Users;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use kartik\date\DatePicker;
use kartik\typeahead\Typeahead;
use yii\helpers\Url;
use yii\web\JsExpression;

/* @var $this yii\web\View */
/* @var $model app\models\Materials */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

$places= Places::fetchNames();
$places['']='- помещение не назначено -';
asort($places);
if (empty($model->date)) $model->date=date('Y-m-d',time());
$hidden=' style="display:none" ';

if ($model->isNewRecord) {
	$formActionSave= Url::to(['materials/create','return'=>'previous']);
} else {
	$formActionSave= Url::to(['materials/update','id'=>$model->id,'return'=>'previous']);
}

?>

<div class="materials-form">

    <?php $form = ActiveForm::begin([
	    'enableClientValidation' => false,
	    'enableAjaxValidation' => true,
	    'id' => 'materials-form',
	    'validationUrl' => $model->isNewRecord?['materials/validate']:['materials/validate','id'=>$model->id],
    ]); ?>

    <div class="row">
		<div class="col-md-2">
			<br>
			<div class="form-check">
				<input class="form-check-input"
					   type="radio"
					   name="materialSource"
					   id="materialSource1"
					<?= !$model->parent_id?'checked':'' ?>
					   onchange="$('#source_parent, #source_new, #materials-model-selector, #materials-model-hint').toggle();">
				<label class="form-check-label" for="materialSource1">
					Приобретено новое
				</label>
			</div>
			<div class="form-check">
				<input class="form-check-input"
					   type="radio"
					   name="materialSource"
					   id="materialSource2"
					<?= $model->parent_id?'checked':'' ?>
					   onchange="$('#source_parent, #source_new, #materials-model-selector, #materials-model-hint').toggle();">
				<label class="form-check-label" for="materialSource2">
					Перемещено из другого места
				</label>
			</div>
		</div>
        <div class="col-md-2">
		    <?= $form->field($model, 'count')->textInput() ?>
        </div>
        <div class="col-md-2">
	        <?= $form->field($model, 'date')->widget(DatePicker::classname(), [
		        'options' => ['placeholder' => 'Введите дату ...'],
		        'pluginOptions' => [
			        'autoclose'=>true,
			        'format' => 'yyyy-mm-dd',
					'weekStart' => '1'
		        ]
	        ]); ?>
        </div>
        <div class="col-md-6">
			<div id="source_parent" <?= empty($model->parent_id)?$hidden:'' ?>>
				<?= $form->field($model, 'parent_id')->widget(Select2::className(), [
					'data' => Materials::fetchNames(),
					'options' => ['placeholder' => 'Выберите источник этого материала',],
					'toggleAllSettings'=>['selectLabel'=>null],
					'pluginOptions' => [
						'dropdownParent' => $modalParent,
						'allowClear' => true,
						'multiple' => false
					],
				]) ?>
			</div>
			<div id="source_new" class="row" <?= !empty($model->parent_id)?$hidden:'' ?>>
				<div class="col-md-3">
					<?= $form->field($model, 'currency_id')->widget(Select2::classname(), [
						'data' => Currency::fetchNames(),
						'options' => ['placeholder' => 'RUR'],
						'toggleAllSettings'=>['selectLabel'=>null],
						'pluginOptions' => [
							'dropdownParent' => $modalParent,
							'allowClear' => false,
							'multiple' => false
						],
					]) ?>
				</div>
				<div class="col-md-6">
					<?= $form->field($model,'cost')->textInput() ?>
				</div>
				<div class="col-md-3">
					<?= $form->field($model,'charge')->textInput()->hint(Contracts::chargeCalcHtml('materials','cost','charge')) ?>
				</div>
			</div>
			

        </div>
    </div>
	<div <?= empty($model->parent_id)?$hidden:'' ?> id="materials-model-hint" class="alert alert-striped text-center" role="alert">
		<span class="fs-3">Если выбрано взятие материала из другого источника, то соответственно категория и модель те же, что и в источнике</span>
	</div>
	<div class="row" id="materials-model-selector" <?= !empty($model->parent_id)?$hidden:'' ?>>
		<div class="col-md-6">
			<?= $form->field($model, 'type_id')->widget(Select2::className(), [
				'data' => MaterialsTypes::fetchNames(),
				'options' => ['placeholder' => 'Выберите тип',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => false,
					'multiple' => false
				]
			]) ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'model')->widget(Typeahead::classname(), [
				'options' => ['placeholder' => 'Наберите или выберите название','autocomplete'=>'off'],
				'pluginOptions' => ['highlight'=>true],
				'pluginEvents' => ['click'=>'function(){
	                $(\'.typeahead\').typeahead(\'open\');
	            }'],
				'dataset' => [
					[
						'datumTokenizer' => "Bloodhound.tokenizers.obj.whitespace('value')",
						'display' => 'value',
						'prefetch' => Url::to(['materials/search-list','type'=>$model->type_id]),
						'remote' => [
							'url' => Url::to(['materials/search-list']) . '?name=%QUERY&type=%TYPE_ID',
							'prepare' => new JsExpression('function(query, settings) {return settings.url.replace("%QUERY", query).replace("%TYPE_ID", $("#materials-type_id").val());}'),
						]
					]
				]			]); ?>
		</div>
	</div>

    <div class="row">
        <div class="col-md-6">
	        <?= $form->field($model, 'it_staff_id')->widget(Select2::className(), [
		        'data' => Users::fetchWorking($model->it_staff_id),
		        'options' => ['placeholder' => 'Кто отвечает?',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
					'dropdownParent' => $modalParent,
			        'allowClear' => true,
			        'multiple' => false
		        ]
	        ]) ?>
        </div>
        <div class="col-md-6">
	        <?= $form->field($model, 'places_id')->widget(Select2::className(), [
				'data' => Places::fetchNames(),
				'options' => ['placeholder' => 'Где хранится?',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => false,
					'multiple' => false
				]
			]) ?>
        </div>
    </div>

	<?= $form->field($model, 'contracts_ids')->widget(Select2::className(), [
		'data' => Contracts::fetchNames(),
		'options' => ['placeholder' => 'Выберите документы о поступлении этого материала',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>



    <?= $form->field($model, 'comment')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?php //Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success','name' => 'save', 'formaction' => $formActionSave,]) ?>
    </div>
	
    
	<?php ActiveForm::end();
	$js=<<<JS
	$('#materials-form').on('beforeSubmit', function () {
		if ($('#materialSource1').is(':checked')) $('#materials-parent_id').val('');
		return true;
	});
JS;

	$this->registerJs($js)
	?>
	
	
	

</div>
