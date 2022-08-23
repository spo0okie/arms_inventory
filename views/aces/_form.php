<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;
use \app\helpers\FieldsHelper;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */
/* @var $form yii\widgets\ActiveForm */

if (!isset($modalParent)) $modalParent=null;

?>

<div class="aces-form">
    <?php $form = ActiveForm::begin([
		//'enableClientValidation' => false,	//чтобы отключить валидацию через JS в браузере
		//'enableAjaxValidation' => true,		//чтобы включить валидацию на сервере ajax запросы
		//'id' => 'aces-form',
		//'validationUrl' => $model->isNewRecord?	//URL валидации на стороне сервера
			//['aces/validate']:	//для новых моделей
			//['aces/validate','id'=>$model->id], //для существующих
		//'action' => Yii::$app->request->getQueryString(),
	]); ?>
	<i></i>
	<div class="for-alert"></div>
	<div class="row">
		<div class="col-md-6">
			<div class="card bg-light">
				<div class="card-header">Кому предоставляется доступ</div>
				<div class="card-body">
					<?= FieldsHelper::Select2Field($form, $model, 'users_ids', [
						'data' => \app\models\Users::fetchWorking(),
						'pluginOptions' => ['dropdownParent' => $modalParent,'multiple' => true],
					]) ?>
					
					<?= FieldsHelper::Select2Field($form, $model, 'comps_ids', [
						'data' => \app\models\Comps::fetchNames(),
						'pluginOptions' => ['dropdownParent' => $modalParent,'multiple' => true],
					]) ?>
					
					<?= FieldsHelper::TextAutoresizeField($form,$model,'ips',['lines' => 1]) ?>
					
					<?= FieldsHelper::TextInputField($form,$model, 'comment') ?>
				</div>
			</div>

			<hr />
			<!-- https://www.yiiframework.com/doc/api/2.0/yii-helpers-basehtml#activeCheckboxList()-detail -->
			<div class="card bg-light">
				<div class="card-header"><?= Html::tag(
					'span',
					'Какой этим объектам предоставляется доступ',
					FieldsHelper::toolTipOptions(
						'Типы предоставляемого доступа' ,
						$model->getAttributeHint('access_types_ids')
					)
				)?>
				</div>
				<div class="card-body">
					<?= FieldsHelper::CheckboxListField($form,$model, 'access_types_ids',[
						'data'=>\app\models\AccessTypes::fetchNames()
					]);?>
				</div>
			</div>
			

		</div>
		<div class="col-md-6">
			<?= FieldsHelper::MarkdownField($form,$model, 'notepad') ?>
			<?= Html::submitButton('Сохранить', ['class' => 'btn btn-success float-end']) ?>
		</div>
	</div>
	


    <?php ActiveForm::end(); ?>

</div>
