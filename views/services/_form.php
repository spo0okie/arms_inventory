<?php

use app\components\formInputs\DokuWikiEditor;
use app\components\Forms\ArmsForm;
use app\helpers\FieldsHelper;
use app\models\Comps;
use app\models\Contracts;
use app\models\Currency;
use app\models\MaintenanceJobs;
use app\models\MaintenanceReqs;
use app\models\Partners;
use app\models\Places;
use app\models\Schedules;
use app\models\Services;
use app\models\Techs;
use app\models\Users;
use kartik\markdown\MarkdownEditor;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Services */
if (!isset($modalParent)) $modalParent=null;

$segmentPlaceholder='Выберите сегмент ИТ инфраструктуры предприятия';
$schedulePlaceholder='Выберите расписание';
$responsiblePlaceholder='Выберите ответственного за сервис';
$supportPlaceholder='Выберите сотрудников поддержки';
$infrastructureResponsiblePlaceholder='Выберите ответственного за инфраструктуру';
$infrastructureSupportPlaceholder='Выберите сотрудников поддержки инфраструктуры';
$parentPlaceholder=' (насл. из основного сервиса/услуги)';

$tags=$model->tags_ids;

if (!$model->is_service) $model->is_service=0;

?>

<div class="services-form">

    <?php $form = ArmsForm::begin([
		'model'=>$model
	]); ?>

	<div class="row">
		<div class="col-md-5">
			<?= $form->field($model, 'name') ?>
		</div>
		<div class="col-md-5">
			<?= $form->field($model, 'parent_id')->select2() ?>
		</div>
		<div class="col-md-2">
			<?= $form->field($model, 'is_service')->radioList([0=>'Услуга',1=>'Сервис'],[
				'class'=>'input-group',
				'item' => function ($index, $label, $name, $checked, $value) use ($model){
					$id_prefix=Html::getInputId($model, 'is_service');
					return
						Html::radio($name, $checked, ['value' => $value, 'class' => 'btn-check','id'=>"{$id_prefix}_$index"]).
						'<label class="btn btn-outline-secondary" for="'.$id_prefix.'_'.$index.'">' . $label . '</label>';
				},
			]) ?>
		</div>
	</div>

    <div class="row">
        <div class="col-md-4">
	        <?= $form->field($model, 'is_end_user')->checkbox() ?>
			<?= $form->field($model, 'segment_id')->select2() ?>
			<?= $form->field($model, 'providing_schedule_id')->select2() ?>
			<?= $form->field($model, 'support_schedule_id')->select2() ?>
			<?= $form->field($model, 'responsible_id')->select2() ?>
			<?= $form->field($model, 'infrastructure_user_id')->select2() ?>
			<?= $form->field($model, 'places_id')->select2() ?>
			<?= $form->field($model, 'partners_id')->select2() ?>
			<div class="row">
				<div class="col-md-3">
					<?= $form->field($model,'currency_id')->select2(['allowClear'=>false]) ?>
				</div>
				<div class="col-md-6">
					<?= $form->field($model,'cost') ?>
				</div>
				<div class="col-md-3">
					<?= $form->field($model,'charge')
						->classicHint(Contracts::chargeCalcHtml('services','cost','charge')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-md-4">
					<?= $form->field($model,'vm_cores') ?>
				</div>
				<div class="col-md-4">
					<?= $form->field($model,'vm_ram') ?>
				</div>
				<div class="col-md-4">
					<?= $form->field($model,'vm_hdd') ?>
				</div>
			</div>
			<div class="row">
				<div class="col-md-6">
					<?= $form->field($model,'weight') ?>
				</div>
				<div class="col-md-6 p-3 align-content-end">
					<br/>
					<?= $form->field($model, 'archived')->checkbox() ?>
				</div>
			</div>
			<?= $form->field($model, 'tags_ids') ?>


		</div>
        <div class="col-md-8">
			<?= $form->field($model,'search_text')->textAutoresize(['rows' => 1,]) ?>
			<?= $form->field($model,'description')->textAutoresize(['rows' => 2,]) ?>
			<?= $form->field($model,'links')->textAutoresize(['rows' => 1,]) ?>
			<?= $form->field($model,'support_ids')->select2() ?>
			<?= $form->field($model,'infrastructure_support_ids')->select2() ?>
			<?= $form->field($model,'depends_ids')->select2() ?>
			<?= $form->field($model,'comps_ids')->select2() ?>
			<?= $form->field($model,'techs_ids')->select2() ?>
			<div class="row">
				<div class="col-6">
					<?= $form->field($model,'maintenance_reqs_ids')->select2() ?>
				</div>
				<div class="col-6">
					<?= $form->field($model, 'maintenance_jobs_ids')->select2() ?>

				</div>
			</div>
			<?= $form->field($model, 'contracts_ids')->select2() ?>
		</div>
    </div>

	<div class="form-group">
		<?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
	</div>
	<?= $form->field($model, 'notebook')->text() ?>






    <?php ArmsForm::end(); ?>

</div>
