<?php

use app\components\Forms\ArmsForm;
use app\models\SchedulesEntries;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;




/* @var $this yii\web\View */
/* @var $model app\models\Schedules */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

if (!isset($acl_mode)) $acl_mode=false;

?>

<div class="schedules-form">

    <?php
	$form = ArmsForm::begin(['model'=>$model]);

	if ($model->isNewRecord) { ?>
		<div class="row">
			<div class="col-md-4">
				<?= $form->field($model, 'name') ?>
			</div>
			<div class="col-md-4">
				<?= $form->field($model, 'parent_id')->select2([
					'options' => [
						'onchange' => '$("#schedules-defaultitemschedule").prop("disabled",($(this).val()))',
					],
				]) ?>
			</div>
			<div class="col-md-4">
				<?= $form->field($model, 'defaultItemSchedule')
					->textInput([
						'onchange' => '$("#schedules-parent_id").prop("disabled",($(this).val()))',
						'onkeypress' => "this.onchange();",
						'onpaste'    => "this.onchange();",
						'oninput'    => "this.onchange();",
					])
					->hint(
						$model->getAttributeHint('defaultItemSchedule').'<br />Примеры расписаний: '.
						SchedulesEntries::scheduleSamplesHtmlFor('schedules-defaultitemschedule')
					)
				?>
			</div>
		</div>

	<?php } else { ?>
		<div class="row">
			<div class="col-md-6">
				<?= $form->field($model, 'name') ?>
			</div>
			<div class="col-md-6">
				<?= $form->field($model, 'parent_id')->select2() ?>
			</div>
		</div>

	<?php } ?>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'description') ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model, 'start_date')->date()
				->hint($model->getDictionary('period_start')); ?>
		</div>
		<div class="col-md-3">
			<?= $form->field($model, 'end_date')->date()
				->hint($model->getDictionary('period_end')); ?>
		</div>
	</div>
	<?= $form->field($model, 'history')->text() ?>

	<div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
