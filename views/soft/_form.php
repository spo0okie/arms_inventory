<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\select2\Select2;


/* @var $this yii\web\View */
/* @var $model app\models\Soft */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="soft-form">

    <?php $form = ActiveForm::begin(); ?>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'manufacturers_id')->widget(Select2::className(), [
				'data' => \app\models\Manufacturers::fetchNames(),
				'options' => ['placeholder' => 'Выберите производителя',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'allowClear' => false,
					'multiple' => false
				]
			]) ?>
			
			<?= $form->field($model, 'descr')->textInput(['maxlength' => true]) ?>

		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'softLists_ids')->dropDownList(\app\models\SoftLists::listAll(), ['multiple' => true]) ?>
		</div>
	</div>

	<h3>Распознавание установленного ПО</h3>


	<div class="row">
		<div class="col-md-6">
			<?= \app\widgets\TextAutoResizeWidget::widget([
				'form' => $form,
				'model' => $model,
				'attribute' => 'items',
				'lines' => 4,
			]) ?>

		</div>
		<div class="col-md-6">
			<br/>
				Внимание! Элементы, составляющие пакет ПО, вносятся как regexp выражения. Это означает что многие символы являются служебными и должны быть экранированы.<br />
				Например \( \. \+ и т.п. Более подробно читать <?= html::a('тут','https://ru.wikipedia.org/wiki/%D0%A0%D0%B5%D0%B3%D1%83%D0%BB%D1%8F%D1%80%D0%BD%D1%8B%D0%B5_%D0%B2%D1%8B%D1%80%D0%B0%D0%B6%D0%B5%D0%BD%D0%B8%D1%8F') ?><br />
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<?= \app\widgets\TextAutoResizeWidget::widget([
				'form' => $form,
				'model' => $model,
				'attribute' => 'additional',
				'lines' => 4,
			]) ?>

		</div>
		<div class="col-md-6">
			<br />
				Если в списке ПО на компьютере обнаружатся основные компоненты продукта (те что выше), то из него вместе с основными будут также исключены и дополнительные (те что ниже).
				В дополнительные надо включать разделяемые между несколькими продуктами компоненты, которые сами по себе полноценным продуктом не являются.
				Например сервисы обновления.
		</div>
	</div>


	<div class="text-center">
		<blockquote class="small">
			<p>Некоторые люди, столкнувшись с проблемой, думают: «О, а использую-ка я регулярные выражения». Теперь у них есть две проблемы.</p>
			<footer>Джейми Завински</footer>
		</blockquote>
	</div>


	<h3>Описание ПО</h3>
	
	<?= \app\widgets\TextAutoResizeWidget::widget([
		'form' => $form,
		'model' => $model,
		'attribute' => 'comment',
		'lines' => 4,
	]) ?>

	<div class="form-group">
		<?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>
	
	<?php ActiveForm::end(); ?>


	
</div>
