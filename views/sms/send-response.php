<?php

use app\models\ui\SmsForm;


/* @var $this yii\web\View */
/* @var $model SmsForm */

if (!isset($modalParent)) $modalParent=null;

$title='Отправка SMS сообщения';

?>


<h1><?= $title ?></h1>

<?php if (!$model->response) {  ?>
<div class="alert alert-warning">
	Сервис недоступен
</div>
<?php } else {  ?>
<div class="card">
	<div class="card-title">
		Ответ сервиса
	</div>
	<div class="card-body">
		<pre>
			<?= $model->response ?>
		</pre>
	</div>
</div>
<?php }
