<?php

use app\components\ModelFieldWidget;

/* @var $this yii\web\View */
/* @var $model app\models\LicItems */

?>
<?= ModelFieldWidget::renderFieldTitle($model,'status',null,'h3') ?>
<h4><?= ModelFieldWidget::renderFieldValue($model,'status') ?></h4>

<?php
//количества через кэши количеств/id-шников (loaderCount/loaderIds):
//блок рендерится и в тултипах, чтение `*_ids`/relations грузило бы связи по одной
$armsCount=$model->loaderCount('arms') ?? count($model->arms_ids);
$compsCount=$model->loaderCount('comps') ?? count($model->comps_ids);
$usersCount=$model->loaderCount('users') ?? count($model->users_ids);
$keysCount=$model->loaderCount('keys') ?? count($model->keys);
?>
<?php if ($armsCount) { ?>
	<br />Привязано к АРМ: <?= $armsCount ?>
<?php } ?>
<?php if ($compsCount) { ?>
	<br />Привязано к ОС: <?= $compsCount ?>
<?php } ?>
<?php if ($usersCount) { ?>
	<br />Привязано к Польз: <?= $usersCount ?>
<?php } ?>
<?php if ($keysCount) { ?>
	<br/>Внесено ключей: <?= $keysCount ?>
	<?php if ($model->keyArmsCount) { ?>
		<br/>Распределено ключей: <?= count($model->usedKeys) ?>
	<?php }
}?>
