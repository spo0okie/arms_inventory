<?php

use app\components\Forms\ArmsForm;
use app\models\Contracts;
use app\models\Techs;
use app\models\Users;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\LicKeys */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

$js = '
    //меняем подсказку выбора арм в при смене закупки
    function fetchArmsFromDocs(){
        id=$("#lickeys-lic_items_id").val();
        console.log(id);
        $.ajax({url: "/web/lic-items/hint-arms?form=lickeys&id="+id})
            .done(function(data) {$("#arms_id-hint").html(data);})
            .fail(function () {console.log("Ошибка получения данных!")});
        }';
$this->registerJs($js, yii\web\View::POS_BEGIN);

?>

<div class="lic-keys-form">

	<?php $form = ArmsForm::begin([
		'action' => $model->isNewRecord? Url::to(['lic-keys/create']): Url::to(['lic-keys/update','id'=>$model->id]),
		'model'=>$model
	]); ?>

	<?= $form->field($model, 'lic_items_id')->select2([
		'options' => [
			'onchange' => 'fetchArmsFromDocs();'
        ],
	]) ?>

	<?= $form->field($model, 'key_text')->text(['rows' => 1,]) ?>

	<?= $form->field($model, 'arms_ids')
		->select2([
			'pluginEvents' =>['change'=>'function(){$("#linkComment").show("highlight",1600)}'],
			'data' => Techs::fetchArmNames(),
		])
		->classicHint(
			Contracts::fetchArmsHint(
				is_object($model->licItem)?$model->licItem->contracts_ids:null ,
				'lickeys'
			),[
				'id'=>'arms_id-hint'
			]
		);
	?>

	<?= $form->field($model, 'users_ids')->select2([
		'data' => Users::fetchWorking(),
		'pluginEvents' =>['change'=>'function(){$("#linkComment").show("highlight",1600)}'],
	]) ?>
	
	<?= $form->field($model, 'comps_ids')->select2([
		'pluginEvents' =>['change'=>'function(){$("#linkComment").show("highlight",1600)}'],
	]) ?>
	
	<?= $form->field($model, 'linkComment',['options'=>['style'=>'display:none','id'=>'linkComment']]) ?>

	<?= $form->field($model, 'comment')->text(['rows' => 4,]) ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
