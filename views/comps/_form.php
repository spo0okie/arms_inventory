<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use \app\models\Arms;
use yii\bootstrap5\Modal;
use kartik\select2\Select2;



/* @var $this yii\web\View */
/* @var $model app\models\Comps */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

$arms=\yii\helpers\ArrayHelper::map(Arms::find()->all(),'id','num');
$arms['']='-Отсутствует-';
asort($arms);
$domains=\app\models\Domains::fetchNames();
$domains['']='-Отсутствует-';
asort($domains);
?>

<div class="comps-form">

    <?php $form = ActiveForm::begin(); ?>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'domain_id')->dropDownList($domains) ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'arm_id')->widget(Select2::className(), [
				'data' => \app\models\Arms::fetchNames(),
				'options' => ['placeholder' => 'Выберите АРМ',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'user_id')->widget(Select2::className(), [
				'data' => \app\models\Users::fetchWorking($model->user_id),
				'options' => ['placeholder' => 'сотрудник не назначен',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>
	</div>

	
    <?= $form->field($model, 'ignore_hw')->checkbox([]) ?>

    <?= $form->field($model, 'comment')->textInput(['maxlength' => true]) ?>

    <p>
        <span onclick="$('#comps_advanced_settings').toggle()" class="href">Расширенные настройки</span>
    </p>
    <div id="comps_advanced_settings" style="display: none">
        <?= $form->field($model, 'os')->textInput(['maxlength' => true]) ?>
        <?= $form->field($model, 'ip')->textarea(['rows' => 2]) ?>
        <?= $form->field($model, 'raw_hw')->textarea(['rows' => 10]) ?>
        <?= $form->field($model, 'raw_soft')->textarea(['rows' => 10]) ?>
    </div>


    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>



    <p>АРМ для этой ОС еще не заведен? - нажимайте
        <?php

        Modal::begin([
            'id'=>'arms_add_modal',
            'title' => '<h2>Добавление АРМ</h2>',
			'size' => Modal::SIZE_LARGE,
            'toggleButton' => [
                'label' => 'Создать АРМ',
                'tag' => 'button',
                'class' => 'btn btn-success',
            ],
            //'footer' => 'Низ окна',
        ]);
        $armModel=new \app\models\Arms();
        $armModel->comp_id=$model->id;

        echo $this->render(
            '/arms/_form',
            [
                'model'=>$armModel,
            ]
        );
$js = <<<JS
    $('#arms_add_modal').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2
    $('#arms-form').on('beforeSubmit', function(){
        var data = $(this).serialize();
        //alert(data);
        $.ajax({
            url: '/web/arms/create',
            type: 'POST',
            data: data,
            success: function(res){
                //alert(res);
                window.location.replace(window.location.toString()+'&arms_id='+res[0].id);
            },
            error: function(){
                alert('Error!');
            }
        });
        return false;
    });
JS;
        $this->registerJs($js);
        Modal::end(); ?>
    </p>



</div>
