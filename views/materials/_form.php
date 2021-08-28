<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;
use kartik\date\DatePicker;
use kartik\typeahead\Typeahead;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Materials */
/* @var $form yii\widgets\ActiveForm */

$places=\app\models\Places::fetchNames();
$places['']='- помещение не назначено -';
asort($places);
if (empty($model->date)) $model->date=date('Y-m-d',time());
$hidden=' style="display:none" ';

if ($model->isNewRecord) {
	$formActionSave=\yii\helpers\Url::to(['materials/create','return'=>'previous']);
} else {
	$formActionSave=\yii\helpers\Url::to(['materials/update','id'=>$model->id,'return'=>'previous']);
}

?>

<div class="materials-form">

    <?php $form = ActiveForm::begin([
	    'enableClientValidation' => false,
	    'enableAjaxValidation' => true,
	    'id' => 'materials-form',
	    'validationUrl' => $model->isNewRecord?['materials/validate']:['materials/validate','id'=>$model->id],
    ]); ?>

    <div class="row" id="materials-model-selector" <?= !empty($model->parent_id)?$hidden:'' ?>>
        <div class="col-md-6">
		    <?= $form->field($model, 'type_id')->widget(Select2::className(), [
			    'data' => \app\models\MaterialsTypes::fetchNames(),
			    'options' => ['placeholder' => 'Выберите тип',],
			    'toggleAllSettings'=>['selectLabel'=>null],
			    'pluginOptions' => [
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
							'prepare' => new \yii\web\JsExpression('function(query, settings) {return settings.url.replace("%QUERY", query).replace("%TYPE_ID", $("#materials-type_id").val());}'),
						]
					]
				]			]); ?>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2">
		    <?= $form->field($model, 'count')->textInput() ?>
        </div>
        <div class="col-md-3">
	        <?= $form->field($model, 'date')->widget(DatePicker::classname(), [
		        'options' => ['placeholder' => 'Введите дату ...'],
		        'pluginOptions' => [
			        'autoclose'=>true,
			        'format' => 'yyyy-mm-dd',
					'weekStart' => '1'
		        ]
	        ]); ?>
        </div>
        <div class="col-md-7">
	        <?= $form->field($model, 'parent_id')->widget(Select2::className(), [
		        'data' => \app\models\Materials::fetchNames(),
		        'options' => ['placeholder' => 'Выберите источник этого материала',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => true,
			        'multiple' => false
		        ],
		        'pluginEvents' =>[
			        'change'=>'function(){
                        if ($("#materials-parent_id").val()) {
                            $("#materials-model-selector").hide();
                            $("#materials-model-hint").show();
                        } else {
                            $("#materials-model-selector").show();
                            $("#materials-model-hint").hide();
                        }
                    }'
		        ],

	        ]) ?>
            <div <?= empty($model->parent_id)?$hidden:'' ?> id="materials-model-hint" >
                Если выбран источник материала, то категория и модель те, же что и в источнике<br /><br />
            </div>

        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
	        <?= $form->field($model, 'it_staff_id')->widget(Select2::className(), [
		        'data' => \app\models\Users::fetchWorking(),
		        'options' => ['placeholder' => 'сотрудник не назначен',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
			        'allowClear' => true,
			        'multiple' => false
		        ]
	        ]) ?>
        </div>
        <div class="col-md-6">
	        <?= $form->field($model, 'places_id')->dropDownList($places) ?>
        </div>
    </div>

	<?= $form->field($model, 'contracts_ids')->widget(Select2::className(), [
		'data' => \app\models\Contracts::fetchNames(),
		'options' => ['placeholder' => 'Выберите документы о поступлении этого материала',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>



    <?= $form->field($model, 'comment')->textarea(['rows' => 6]) ?>

    <div class="form-group">
        <?php //Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success','name' => 'save', 'formaction' => $formActionSave,]) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
