<?php

/*
 * Кусочек кода, который выводит привязанные к арм документы
 */
?>

<h4>Статус:</h4>
<p>
	<b><?= $model->stateName ?></b>
	<?= strlen($model->comment)? (': '.$model->comment):'' ?><br/>
</p>

