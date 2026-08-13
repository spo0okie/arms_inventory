<?php
/**
 * Список вложений прикрепленных к модели
 * User: aareviakin
 * Date: 14.05.2023
 * Time: 14:33
 */

/** @var yii\web\View $this */
/** @var app\models\Attaches $model */

use app\models\Attaches;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

if (!isset($static_view)) $static_view=false;
if (!isset($link)) $link=$model::tableName().'_id';
if (!isset($active)) $active=false;
if (!isset($cardClass)) $cardClass='';

$switchCode=<<<JS
	$('#add-attaches-form-button, #add-attaches-form-form').toggle();
	return false;
JS;

$attaches=$this->render('list',['models'=>$model->attaches]);

if (!$static_view) $attaches.=Html::a('Загрузить','#',[
	'onclick'=>$switchCode,
	'id'=>'add-attaches-form-button',
	'style'=>$active?'display:none':null,
]);

?>

<h4>Файлы:</h4>
<p class="<?= $cardClass ?>"><?= $attaches ?></p>

<?php

if (!$static_view) {
	$attach= new Attaches();
	?>

	<div class="add-attaches-form text-center" id="add-attaches-form-form" <?= !$active?'style="display:none"':'' ?>>

	    <?php $form = ActiveForm::begin(['action'=>[
			'attaches/create',
			'return'=>'previous',
			'Attaches['.$link.']'=>$model->id
		]]); ?>

		<div class="input-group">
			<?= $form->field($attach, 'uploadedFile')->fileInput(['class'=>'form-control'])->label(false) ?>
			<div class="input-group-append">
				<?= Html::submitButton('Загрузить', ['class' => 'btn btn-success']) ?>
			</div>
			<div class="input-group-append">
				<?= Html::Button('Отмена', ['class' => 'btn btn-danger','onclick'=>$switchCode]) ?>
			</div>
		</div>

	    <?php ActiveForm::end(); ?>

	</div>

<?php }
