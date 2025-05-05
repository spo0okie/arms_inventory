<?php

use app\components\Forms\ArmsForm;
use app\helpers\FieldsHelper;
use app\models\Manufacturers;
use app\models\SoftLists;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;


/* @var $this yii\web\View */
/* @var $model app\models\Soft */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

$model->addItem($model->add_item);
?>

<div class="soft-form">

    <?php $form = ArmsForm::begin(['model'=>$model]); ?>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'manufacturers_id')->select2() ?>
			
			<?= $form->field($model, 'descr')->textInput(['maxlength' => true]) ?>
			<?= $form->field($model, 'softLists_ids')->checkboxList(SoftLists::listAll(), ['multiple' => true]) ?>

		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'comment')->text() ?>
			<?= $form->field($model, 'links')->textAutoresize(['rows'=>2]) ?>
		</div>
	</div>

	<h3>Распознавание установленного ПО</h3>


	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'items')->textAutoresize() ?>

		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'additional')->textAutoresize() ?>
		</div>
	</div>

	<div class="form-group">
		<?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>
	
	<?php ArmsForm::end(); ?>


	
</div>
