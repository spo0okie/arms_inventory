<?php

/*
 * Кусочек кода, который выводит привязанные к арм документы
 */
?>

<?= \app\components\ModelFieldWidget::renderFieldTitle($model,'history') ?>
<p>
	<?= \app\components\ModelFieldWidget::renderFieldValue($model,'history') ?>
</p>

