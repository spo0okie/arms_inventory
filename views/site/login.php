<?php

/* @var $this yii\web\View */
/* @var $form yii\bootstrap5\ActiveForm */
/* @var $model app\models\LoginForm */

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

$this->title = 'Вход';
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
						<h5 class=" text-center">Введите логин и пароль для входа в систему</h5>
						<p class="text-right p-3">
							Авторизация производится при помощи доменных учетных данных.<br />
							За назначением прав вашему пользователю на просмотр/редактирование данных, или на управление ACL обращайтесь к ответственному за инвентаризацию.
						</p>
						<?php $form = ActiveForm::begin([
							'id' => 'login-form',
							'layout' => 'horizontal',
						]); ?>
						
						<?= $form->field($model, 'username')->textInput(['autofocus' => true]) ?>
						<?= $form->field($model, 'password')->passwordInput() ?>
						<?= $form->field($model, 'rememberMe')->checkbox([
							'template' => "<div class=\"col-lg-offset-1 col-lg-3\">{input} {label}</div>\n<div class=\"col-lg-8\">{error}</div>",
						]) ?>
						<div class="text-center">
							<?= Html::submitButton('Авторизоваться', ['class' => 'btn btn-primary', 'name' => 'login-button']) ?>
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
