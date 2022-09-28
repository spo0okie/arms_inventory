<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\helpers\Html;

$this->title = $name;
?>
	<div class="site-error">
		
		<?= $this->render('_about') ?>

		
		<div class="row">
			<div class="col-lg-3">
			</div>
			<div class="col-lg-6 text-center">
				<p>
					Во время обработки запроса возникла ошибка
				</p>
				<div class="card text-center border-danger ">
					<div class="card-header bg-danger">
						<h2 class="card-title"><?= Html::encode($this->title) ?></h2>
					</div>
					<div class="card-body">
						<?= nl2br(Html::encode($message)) ?>
					</div>
				</div>
				<br>
				<p>
					Свяжитесь с ответственным за сервис "Инвентаризация", если вы считаете что это ошибка сервиса.
				</p>
				
			</div>
			<div class="col-lg-3">
			</div>
		</div>
		
		
		
	
	</div>

