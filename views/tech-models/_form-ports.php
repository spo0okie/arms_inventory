<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\TechModels */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

//блок «Добавить группу портов» общий с карточкой устройства
?>
	<div class="row">
		<div class="col-md-8" >
			<?= $form->field($model, 'ports')->textarea(['rows' => 16]) ?>
			<?php if (!$model->isNewRecord) { ?>
				<?php
				//по умолчанию включено, но снимается само, как только число строк
				//меняется: это уже не переименование, а сдвиг списка
				$model->rename_ports = true;
				\app\components\PortsGroupWidget::registerRenameGuard($this,
					Html::getInputId($model, 'ports'), Html::getInputId($model, 'rename_ports'));
				?>
				<?= $form->field($model, 'rename_ports')->checkbox() ?>
			<?php } ?>
		</div>
		<div class="col-md-4" >
			<?= \app\components\PortsGroupWidget::widget([
				'fieldId' => Html::getInputId($model, 'ports'),
			]) ?>
		</div>
	</div>

	<div class="row">
		<div class="col-md-8">
			<?= $form->field($model, 'ports_layout')->textarea(['rows' => 3]) ?>
		</div>
		<div class="col-md-4">
			<?php /* корпус - свойство модели, поэтому раскладка живёт тут, а не
			       у экземпляра: у стекированного коммутатора панель та же,
			       меняются только имена портов */ ?>
			<div class="hint-block">
				Пример для 24-портового коммутатора с четырьмя SFP:<br>
				<code>12x2 вниз Основные<br>4 SFP</code><br>
				12x2 — сетка 12 на 2 (24 порта в два ряда), 4 — один ряд.<br>
				Блоки съедают порты в том порядке, в каком те объявлены слева.
			</div>
		</div>
	</div>

