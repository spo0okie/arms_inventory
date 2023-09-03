<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap5\ActiveForm */
/* @var $model app\models\LoginForm */

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Смена пароля';
?>
<div class="site-login row col align-self-center">
	<div class="body-content">
		
		<?= $this->render('_about') ?>

		<div class="row">
			<div class="col-lg-3">
			</div>
			<div class="col-lg-6">
				<div class="card">
					<div class="card-header  text-center">
						<h2 class="card-title"><?= Html::encode($this->title) ?></h2>
					</div>
					<div class="card-body">
						<h5 class=" text-center m-4">Введите и повторите новый пароль для входа в систему</h5>
						<?php $form = ActiveForm::begin([
							'layout' => 'horizontal',
						]); ?>
						
						<?= $form->field($model, 'user_id')->hiddenInput()->label(false) ?>
						<?= $form->field($model, 'password')->passwordInput(['autofocus' => true]) ?>
						<?= $form->field($model, 'passwordRepeat')->passwordInput() ?>
						<div class="text-center">
							<?= Html::submitButton('Сохранить', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
						</div>
						<?php ActiveForm::end(); ?>
					</div>
				</div>
			</div>
			<div class="col-lg-3">
			</div>
		</div>
	</div>
</div>
