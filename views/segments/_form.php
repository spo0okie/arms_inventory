<?php

use app\components\Forms\ArmsForm;
use kartik\markdown\MarkdownEditor;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Segments */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;
$defaultCss=[
	'open',
	'closed',
	'common',
	'gw_close2open',
	'gw_open2close',
	'ext',
	'ext_dmz',
	'int_dmz',
	'open_dmz',
	'closed_dmz',
	'guest_dmz',
	'client_vpn',
	'intersite_vpn',
	'it_lan',
	'voip',
	'prn',
	'skud',
	'mgmt'
];
?>

<div class="segments-form">

    <?php $form = ArmsForm::begin(['model'=>$model]); ?>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'name') ?>
			<?= $form->field($model, 'code') ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model,'description') ?>
			<?= $form->field($model,'links')->textAutoresize() ?>
		</div>
	</div>
	<div class="d-flex flex-row flex-wrap">
		<span class="m-1">Готовые коды: </span>
		<?php
		foreach ($defaultCss as $css) {
			echo '<span class="segment_'.$css.' m-1 px-1"><a href="#" onclick="$(\'#segments-code\').val(\'segment_'.$css.'\')">segment_'.$css.'</a></span>';
		}
		?>
	</div>

	<?= $form->field($model, 'history')->text() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
