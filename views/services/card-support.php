<?php

use app\components\ModelFieldWidget;

/* @var $this yii\web\View */
/* @var $model app\models\Services */

if (!isset($static_view)) $static_view=false;

//общий конфиг вывода: список пользователей инлайн (через запятую, без переносов)
$userList=['glue'=>', ','lineBr'=>false,'item_options'=>['static_view'=>true]];
$userOne=['item_options'=>['static_view'=>true]];
?>
<h4>
	<?= ModelFieldWidget::renderFieldTitle($model,'responsibleRecursive',tag:'span',labelOverride:'Ответственный') ?>:
	<?= ModelFieldWidget::renderFieldValue($model,'responsibleRecursive',$userOne) ?>
</h4>
<?php if (is_array($model->supportRecursive) && count($model->supportRecursive)) { ?>
	<p>
		<?= ModelFieldWidget::renderFieldTitle($model,'supportRecursive',tag:'span',labelOverride:'Поддержка') ?>:
		<?= ModelFieldWidget::renderFieldValue($model,'supportRecursive',$userList) ?>
	</p>
	<br />
<?php }
if (is_object($model->infrastructureResponsibleRecursive)) { ?>
	<h4>
		<?= ModelFieldWidget::renderFieldTitle($model,'infrastructureResponsibleRecursive',tag:'span',labelOverride:'Отв. за инфраструктуру') ?>:
		<?= ModelFieldWidget::renderFieldValue($model,'infrastructureResponsibleRecursive',$userOne) ?>
	</h4>
<?php }

if (is_array($model->infrastructureSupportRecursive) && count($model->infrastructureSupportRecursive)) { ?>
	<p>
		<?= ModelFieldWidget::renderFieldTitle($model,'infrastructureSupportRecursive',tag:'span',labelOverride:'Поддержка инфраструктуры') ?>:
		<?= ModelFieldWidget::renderFieldValue($model,'infrastructureSupportRecursive',$userList) ?>
	</p>
	<br />
<?php }
