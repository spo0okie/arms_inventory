<?php

use app\components\Forms\ArmsForm;
use app\models\Contracts;
use app\models\Users;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\LicItems */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

$js = '
    //меняем подсказку выбора арм в при смене списка документов
    function fetchArmsFromDocs(){
        docs=$("#licitems-contracts_ids").val();
        console.log(docs);
        $.ajax({url: "/web/contracts/hint-arms?form=licitems&ids="+docs})
            .done(function(data) {$("#arms_id-hint").html(data);})
            .fail(function () {console.log("Ошибка получения данных!")});
        }';
$this->registerJs($js, yii\web\View::POS_BEGIN);

?>

<div class="lic-items-form">

    <?php $form = ArmsForm::begin([
	    'action' => $model->isNewRecord? Url::to(['lic-items/create']): Url::to(['lic-items/update','id'=>$model->id]),
	    'enableClientValidation' => false,
	    'enableAjaxValidation' => true,
	    'validationUrl' => $model->isNewRecord?['lic-items/validate']:['lic-items/validate','id'=>$model->id],
		'model'=>$model,
    ]); ?>


    <div class="row">
        <div class="col-md-3" >
            <?= $form->field($model, 'lic_group_id')->select2() ?>
        </div>
        <div class="col-md-9" >
			<?= $form->field($model, 'descr') ?>
        </div>
    </div>

	<div class="row">
		<div class="col-md-11" >
			<?= $form->field($model, 'contracts_ids')->select2([
				'options' => ['onchange' => 'fetchArmsFromDocs();'],
			]) ?>
		</div>
		<div class="col-md-1" >
			<?= $form->field($model, 'count')?>
		</div>
	</div>
	
	<div class="row">
		<div class="col-md-6" >
			<?= $form->field($model, 'active_from')->date(); ?>
		</div>
		<div class="col-md-6" >
			<?= $form->field($model, 'active_to')->date(); ?>
		</div>
	</div>



	<?= $form->field($model, 'arms_ids')
		->select2(['pluginEvents' =>['change'=>'function(){$("#linkComment").show("highlight",1600)}']])
		->classicHint(Contracts::fetchArmsHint($model->contracts_ids,'licitems'),['id'=>'arms_id-hint'])
	?>
	
	<?= $form->field($model,  'users_ids')->select2([
		'data' => Users::fetchWorking(),
		'pluginEvents' =>['change'=>'function(){$("#linkComment").show("highlight",1600)}'],
	]) ?>
	
	<?= $form->field($model, 'comps_ids')->select2([
		'pluginEvents' =>['change'=>'function(){$("#linkComment").show("highlight",1600)}'],
	]) ?>
	
	<?= $form->field($model, 'linkComment',['options'=>['style'=>'display:none','id'=>'linkComment']]) ?>

	
	
	<?= $form->field($model,'comment')->text(['height' => 130,'rows'=>9]) ?>


    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ArmsForm::end(); ?>

</div>
