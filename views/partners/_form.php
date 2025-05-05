<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Partners */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="partners-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model,
	]); ?>
	
	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'uname') ?>
			<div class="row">
				<div class="col-md-10">
					<?= $form->field($model, 'bname') ?>
				</div>
				<div class="col-md-2">
					<?= $form->field($model, 'prefix') ?>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<?= $form->field($model,  'inn') ?>
				</div>
				<div class="col-md-6">
					<?= $form->field($model,  'kpp') ?>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<?= $form->field($model,  'alias')->textAutoresize(['rows'=>1]) ?>
			<?= $form->field($model,  'cabinet_url')->textAutoresize(['rows'=>1]) ?>
			<?= $form->field($model,  'support_tel')->textAutoresize(['rows'=>1]) ?>
		</div>
	</div>
	





	<div class="row">
		<div class="col-md-6">
		    <?= $form->field($model,  'comment')->text(['rows' => 6]) ?>
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


    <?php ArmsForm::end(); ?>

</div>
