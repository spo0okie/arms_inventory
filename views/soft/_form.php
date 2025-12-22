<?php

use app\components\Forms\ArmsForm;
use app\models\SoftLists;
use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Soft */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

$model->addItem($model->add_item);
?>

<div class="soft-form">

    <?php $form = ArmsForm::begin(['model'=>$model]); ?>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'manufacturers_id')->select2() ?>
			
			<?= $form->field($model, 'descr')->textInput(['maxlength' => true]) ?>
			<?= $form->field($model, 'soft_lists_ids')->checkboxList(SoftLists::listAll(), ['multiple' => true]) ?>

			<h3>Распознавание установленного ПО</h3>
			
			<?= $form->field($model, 'items')->textAutoresize(['rows'=>3]) ?>
			<?= $form->field($model, 'additional')->textAutoresize(['rows'=>3]) ?>

			<?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'comment') ?>
			<?= $form->field($model, 'notepad')->text() ?>
			<?= $form->field($model, 'links')->textAutoresize(['rows'=>2]) ?>
			<?php if (\app\components\llm\LlmClient::available()) { ?>
				<button type="button" id="btn-generate" class="btn btn-secondary">Сгенерировать описание</button>
			<?php } ?>
		</div>
	</div>
	
	<?php ArmsForm::end(); ?>
</div>

<?php
$js = <<<JS
$('#btn-generate').on('click', function() {
    const manufacturer	= $('#soft-manufacturers_id').val();
    const name			= $('#soft-descr').val();

    if (!name) {alert('Заполните наименование ПО'); return; }
    if (!manufacturer) {alert('Укажите разработчика'); return; }

    $(this).prop('disabled', true).text('Генерация...');

    $.post('/web/soft/generate-description', {manufacturer, name})
        .done(function(resp) {
            if (resp.error) {
                alert(resp.error);
                return;
            }
            const data = resp.data;
            if (data) {
                if (!$('#soft-comment').val()) $('#soft-comment').val(data.comment || '');
                if (!$('#soft-notepad').val()) $('#soft-notepad').val(data.description || '');
                if (!$('#soft-links').val()) $('#soft-links').val(data.links || '');
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