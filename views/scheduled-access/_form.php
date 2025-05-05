<?php

use app\components\Forms\ArmsForm;
use app\models\Acls;
use kartik\markdown\MarkdownEditor;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Schedules */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
?>

<div class="schedules-form">

    <?php $form = ArmsForm::begin(['model'=>$model]); ?>
	<?= $form->field($model, 'name')
		->hint(Acls::$scheduleNameHint)
	?>

	<?= $form->field($model, 'history')->text()
		->classicHint(Acls::$scheduleHistoryHint) ?>
	
	<div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
