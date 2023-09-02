<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Partners */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="partners-form">

    <?php $form = ActiveForm::begin(); ?>
	
	<div class="row">
		<div class="col-md-6">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'uname') ?>
		</div>
		<div class="col-md-4">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'bname') ?>
		</div>
		<div class="col-md-2">
			<?=  \app\helpers\FieldsHelper::TextInputField($form,$model, 'prefix') ?>
		</div>
	</div>
	

	<div class="row">
		<div class="col-md-6">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model,  'inn') ?>
		</div>
		<div class="col-md-6">
			<?= \app\helpers\FieldsHelper::TextInputField($form,$model,  'kpp') ?>
		</div>
	</div>


	<div class="row">
		<div class="col-md-6">
			<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model,  'cabinet_url') ?>
		</div>
		<div class="col-md-6 form-text text-muted">
			<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model,  'support_tel') ?>
		</div>
	</div>



	<div class="row">
		<div class="col-md-6">
		    <?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model,  'comment',['lines' => 6]) ?>
			<br>
			<?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
			
		</div>
		<div class="col-md-6 form-text text-muted">
			<strong>Пример</strong>:<br>
			Поставщик софта / железа<br>
			454111 г.Челябинск, улица Пушкина, дом колотушкина<br>
			<br>
			Продаван (Менеджер по корп. клиентам):<br>
			Лоханкин Васиссуалий Петрович /+7-9ХХ-ХХХ-ХХХХ/<br>
			Тел.: +7-351-ХХХ-ХХХХ доб. ХХХ<br>
			Эл. почта: lohankin@rogaikopyta.ru<br>
			<br>
			технарь (Тех директор):<br>
			Скумбриевич Егор Александровича /+7-9ХХ-ХХХ-ХХХХ/<br>
			Тел.: +7-351-ХХХ-ХХХХ доб. ХХХ<br>
			Эл. почта: skumbrievich@rogaikopyta.ru<br>
		</div>
	</div>


    <?php ActiveForm::end(); ?>

</div>
