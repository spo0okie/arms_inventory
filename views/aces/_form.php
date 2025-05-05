<?php

use app\components\Forms\ArmsForm;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */
/* @var $form yii\widgets\ActiveForm */

if (!isset($modalParent)) $modalParent=null;

?>

<div class="aces-form">
    <?php $form = ArmsForm::begin([
		'model'=>$model
	]); ?>
	<div class="for-alert"></div>
	<?= $this->render('/aces/_form_layout', ['model' => $model,'form'=>$form]); ?>
	
	
	
	<?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    <?php ArmsForm::end(); ?>

</div>
