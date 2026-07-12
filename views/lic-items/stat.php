<?php

use app\components\ModelFieldWidget;

/* @var $this yii\web\View */
/* @var $model app\models\LicItems */

?>
<?= ModelFieldWidget::renderFieldTitle($model,'status',null,'h3') ?>
<h4><?= ModelFieldWidget::renderFieldValue($model,'status') ?></h4>

<?php if (count($model->arms_ids)) { ?>
	<br />Привязано к АРМ: <?= count($model->arms_ids) ?>
<?php } ?>
<?php if (count($model->comps_ids)) { ?>
	<br />Привязано к ОС: <?= count($model->comps_ids) ?>
<?php } ?>
<?php if (count($model->users_ids)) { ?>
	<br />Привязано к Польз: <?= count($model->users_ids) ?>
<?php } ?>
<?php if (count($model->keys)) { ?>
	<br/>Внесено ключей: <?= count($model->keys) ?>
	<?php if (count($model->keyArms)) { ?>
		<br/>Распределено ключей: <?= count($model->usedKeys) ?>
	<?php }
}?>
