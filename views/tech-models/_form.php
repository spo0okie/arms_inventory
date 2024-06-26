<?php

use app\components\CollapsableCardWidget;
use app\components\RackConstructorWidget;
use app\helpers\FieldsHelper;
use app\models\Manufacturers;
use app\models\TechTypes;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\TechModels */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

$addPorts=<<<JS
for (
    let i = $('#port_min').val();
    i <= $('#port_max').val();
    i++
) {
    if ($('#techmodels-ports').val().length>0) {
	    $('#techmodels-ports').val(
    	    $('#techmodels-ports').val() + "\\n" + $('#port_prefix').val() + i
		)
    } else {
    	$('#techmodels-ports').val(
	        $('#port_prefix').val() + i
		)
    }
}

JS;

$formAction=$model->isNewRecord?
	['tech-models/create']:
	['tech-models/update','id'=>$model->id];

if (Yii::$app->request->get('return'))
	$formAction['return']=Yii::$app->request->get('return');

?>

<div class="tech-models-form">

    <?php $form = ActiveForm::begin([
        'id'=>'tech_models-edit-form',
	    'enableClientValidation' => false,
	    'enableAjaxValidation' => true,
	    'validateOnBlur' => true,
	    'validateOnChange' => true,
	    'validateOnSubmit' => true,
	    'validationUrl' => $model->isNewRecord?['tech-models/validate']:['tech-models/validate','id'=>$model->id],
	    //'options' => ['enctype' => 'multipart/form-data'],
		//Вот это вот снизу зачем интересно? видимо для вставки в качестве модального окна
	    'action' => Url::to($formAction),
    ]); ?>

    <?php
        $js = '
        //меняем подсказку описания модели в при смене типа оборудования
        function techSwitchDescr(){
            techType=$("#techmodels-type_id").val();
            $.ajax({url: "/web/tech-types/hint-template?id="+techType})
                .done(function(data) {$("#comment-hint").html(data);})
                .fail(function () {console.log("Ошибка получения данных!")});
            }';
        $this->registerJs($js, yii\web\View::POS_BEGIN);
    ?>


    <div class="row">
        <div class="col-md-6" >
            <?= FieldsHelper::Select2Field($form,$model, 'type_id', [
                'data' => TechTypes::fetchNames(),
                'options' => [
					'placeholder' => 'Выберите тип оборудования',
					'onchange' => 'techSwitchDescr();'
				],
                //'toggleAllSettings'=>['selectLabel'=>null],
                'pluginOptions' => [
					'dropdownParent' => $modalParent,
                    'allowClear' => false,
                    'multiple' => false
                ]
            ]) ?>
        </div>
        <div class="col-md-6" >
			<?= FieldsHelper::Select2Field($form,$model, 'manufacturers_id', [
                'data' => Manufacturers::fetchNames(),
                'options' => ['placeholder' => 'Выберите производителя',],
                //'toggleAllSettings'=>['selectLabel'=>null],
                'pluginOptions' => [
					'dropdownParent' => $modalParent,
                    'allowClear' => false,
                    'multiple' => false
                ]
            ]) ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8" >
	        <?= FieldsHelper::TextInputField($form,$model, 'name') ?>
        </div>
        <div class="col-md-4" >
			<?= FieldsHelper::TextInputField($form,$model,'short')?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8" >
			<?= FieldsHelper::TextAutoresizeField($form,$model, 'comment', [
				'lines' => 4,
			]) ?>
			<?= $form->field($model, 'individual_specs')->checkbox() ?>
        </div>
        <div class="col-md-4" >
			<label class="control-label" >
				Подсказка для описания модели
			</label>
			<br />
			<div id="comment-hint" class="hint-block">
				
				<?= is_null($model->type_id)?
					$model->getAttributeHint('comment'):
					Yii::$app->formatter->asNtext($model->type->comment)
				 ?>
			</div>
        </div>
    </div>
	
	<?= $form->field($model, 'links')->textarea(['rows' => 3]) ?>

	<div class="card p-2 mb-2 bg-secondary">
	<?= CollapsableCardWidget::widget([
		'openedTitle'=>'<i class="far fa-minus-square"></i> Порты на устройстве',
		'closedTitle'=>'<i class="far fa-plus-square"></i> Порты на устройстве',
		'initialCollapse'=>!(bool)$model->ports,
		'content'=>'<div class="card-body">'.$this->render('_form-ports',['form'=>$form,'model'=>$model]).'</div>',
	]); ?>
	</div>

	<div class="card p-2 mb-2 bg-secondary">
		<?= CollapsableCardWidget::widget([
			'openedTitle'=>'<i class="far fa-minus-square"></i> Корзина спереди',
			'closedTitle'=>'<i class="far fa-plus-square"></i> Корзина спереди',
			'initialCollapse'=>!$model->contain_front_rack,
			'content'=>'<div class="card-body">'. RackConstructorWidget::widget([
				'form'=>$form,
				'model'=>$model,
				'attr'=>'front_rack',
			]).'</div>',
		]); ?>
	</div>

	<div class="card p-2 mb-2 bg-secondary">
		<?= CollapsableCardWidget::widget([
			'openedTitle'=>'<i class="far fa-minus-square"></i> Корзина сзади',
			'closedTitle'=>'<i class="far fa-plus-square"></i> Корзина сзади',
			'initialCollapse'=>!$model->contain_back_rack,
			'content'=>'<div class="card-body">'. RackConstructorWidget::widget([
					'form'=>$form,
					'model'=>$model,
					'attr'=>'back_rack',
				]).'</div>',
		]); ?>
	</div>

	<br />
	<div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
