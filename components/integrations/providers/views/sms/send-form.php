<?php

use app\components\integrations\providers\SmsSendForm;

/* @var $this yii\web\View */
/* @var $form SmsSendForm */
/* @var $activeForm \app\components\Forms\ArmsForm */

//кнопку отправки рендерит ядро (views/integrations/action-form.php),
//здесь только поля
?>
<div class="row">
	<div class="col-3">
		<?= $activeForm->field($form, 'phone') ?>
	</div>
	<div class="col-9">
		<?= $activeForm->field($form, 'text')->textAutoresize() ?>
	</div>
</div>
