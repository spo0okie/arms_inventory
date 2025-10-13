<?php

use app\components\CollapsableCardWidget;
use app\components\Forms\ArmsForm;
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

    <?php $form = ArmsForm::begin([
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
		'model'=>$model,
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
            <?= $form->field($model, 'type_id')->select2([
                'options' => [
					'onchange' => 'techSwitchDescr();'
				],
            ]) ?>
        </div>
        <div class="col-md-6" >
			<?= $form->field($model, 'manufacturers_id') ?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8" >
	        <?= $form->field($model, 'name') ?>
        </div>
        <div class="col-md-4" >
			<?= $form->field($model,'short')?>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8" >
			<?= $form->field($model, 'comment')->text() ?>
			<?= $form->field($model, 'individual_specs')->checkbox() ?>
        </div>
        <div class="col-md-4" >
			<label class="control-label" >
				Подсказка для описания модели
			</label>
			<br />
			<div id="comment-hint" class="hint-block mb-3">
				
				<?= is_null($model->type_id)?
					$model->getAttributeHint('comment'):
					Yii::$app->formatter->asNtext($model->type->comment)
				 ?>
			</div>
			<?php if (\app\components\llm\LlmClient::available()) { ?>
				<button type="button" id="btn-generate" class="btn btn-secondary">Сгенерировать описание</button>
			<?php } ?>
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

    <?php ArmsForm::end(); ?>

</div>
<?php
$js = <<<JS
$('#btn-generate').on('click', function() {
    const manufacturer	= $('#techmodels-manufacturers_id').val();
    const type			= $('#techmodels-type_id').val();
    const name			= $('#techmodels-name').val();

    if (!name) {alert('Заполните наименование модели'); return; }
    if (!manufacturer) {alert('Укажите производителя'); return; }
    if (!type) {alert('Укажите тип оборудования'); return; }

    $(this).prop('disabled', true).text('Генерация...');

    $.post('/web/tech-models/generate-description', {manufacturer, type, name})
        .done(function(resp) {
            if (resp.error) {
                alert(resp.error);
                return;
            }
            const data = resp.data;
            if (data) {
                if (!$('#techmodels-comment').val()) $('#techmodels-comment').val(data || '');
            }
        })
        .fail(function() {
            alert('Ошибка при обращении к серверу');
        })
        .always(function() {
            $('#btn-generate').prop('disabled', false).text('Сгенерировать описание');
        });
});
JS;
$this->registerJs($js);