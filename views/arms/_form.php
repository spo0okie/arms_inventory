<?php

use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
use kartik\select2\Select2;

/* @var $this yii\web\View */
/* @var $model app\models\OldArms */
/* @var $form yii\widgets\ActiveForm */

/*
$users=\app\models\Users::listWorking();
$users['']='- сотрудник не назначен -';
asort($users);
*/

$places=\app\models\Places::fetchNames();
$places['']='- помещение не назначено -';
asort($places);

if ($model->isNewRecord) {
    $formActionSave=\yii\helpers\Url::to(['arms/create','return'=>'previous']);
    $formActionApply=\yii\helpers\Url::to(['arms/create-apply']);
} else {
	$formActionSave=\yii\helpers\Url::to(['arms/update','id'=>$model->id,'return'=>'previous']);
	$formActionApply=\yii\helpers\Url::to(['arms/update-apply','id'=>$model->id]);
}

if (!isset($modalParent)) $modalParent=null;
?>

<div class="arms-form">

    <?php $form = ActiveForm::begin([
        'enableClientValidation' => false,
        'enableAjaxValidation' => true,
        'id' => 'arms-form',
        'validationUrl' => $model->isNewRecord?['arms/validate']:['arms/validate','id'=>$model->id],
    ]); ?>

    <div class="row">
		<div class="col-md-10" >
			<row class="row">
				<div class="col-md-4" >
					<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'num') ?>
				</div>
				<div class="col-md-4" >
					<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'inv_num')->textInput(['maxlength' => true]) ?>
				</div>
				<div class="col-md-4" >
					<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'sn')->textInput(['maxlength' => true]) ?>
				</div>
			</row>
			<div class="row">
				<div class="col-md-5" >
					<?= \app\helpers\FieldsHelper::Select2Field($form,$model, 'model_id', [
						'data' => \app\models\TechModels::fetchPCs(),
						'options' => [
							'placeholder' => 'Выберите модель',
							'onchange' => 'techSwitchDescr();'
						],
						'pluginOptions' => [
							'dropdownParent' => $modalParent,
							'allowClear' => false,
						]
					]) ?>
					<?= \app\helpers\FieldsHelper::CheckboxField($form,$model, 'is_server') ?>
				</div>
				<div class="col-md-2" >
					<?= \app\helpers\FieldsHelper::Select2Field($form,$model,'comp_id',[
						'data'=>\yii\helpers\ArrayHelper::map($model->comps,'id','name'),
						'pluginOptions' => [
							'dropdownParent' => $modalParent,
							'allowClear' => false,
						]
					]) ?>
					<p id="os_attach_selector">
						<?= $this->render('compsAttach',['arm_id'=>$model->id,'user_id'=>$model->user_id]); ?>
					</p>
				</div>
				<div class="col-md-2" >
					<?= \app\helpers\FieldsHelper::Select2Field($form,$model,'state_id', [
						'data' => \app\models\TechStates::fetchNames(),
						'options' => ['placeholder' => 'Статус рабочего места',],
						'pluginOptions' => [
							'dropdownParent' => $modalParent,
							'allowClear' => false,
						]
					]) ?>
				</div>
				<div class="col-md-3" >
					<?= \app\helpers\FieldsHelper::TextInputField($form,$model, 'comment') ?>
				</div>
			</div>
		</div>
        <div class="col-md-2" >
			<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model, 'mac',['lines'=>4]) ?>
        </div>

	    <?php /*
 		ранее считал что у сервера не может быть пользователя
 		но это в идеальном мире, т.к. в качестве сервера для выполнения каких-либо сервисов
 		может использоваться и любой пользовательский АРМ
        $js = <<<JS
        $("#arms-is_server").on('change',function(){
        
            // to submit only if the checkbox is checked otherwise 
            // you can remove the check and just use the submit statement
            if($(this).is(':checked')){
                $("#arms-user_settings, #arms-responsible_settings_block").hide();
                $("#arms-server_hint_block").show();
            } else {
                $("#arms-user_settings, #arms-responsible_settings_block").show();
                $("#arms-server_hint_block").hide();
            }
        });
		JS;

	    $this->registerJs($js, \yii\web\View::POS_READY);
	    */?>
    </div>
	<?php if ($model->isNewRecord) { ?>
        <p>
            Свободные номера АРМов:
			<?php
			$nums=\app\models\OldArms::fetchNextNums();
			foreach ($nums as $num) { ?>
                <span class="arms-num-selector" onclick="$('#arms-num').val('<?= $num ?>')">
                    <?= $num ?>
                </span>
			<?php }
			?>
        </p>
	<?php }
	
	$js = '
        //меняем подсказку описания модели в при смене типа оборудования
        function techSwitchDescr(){
            model_id=$("#arms-model_id").val();
            $.ajax({url: "/web/tech-models/hint-template?id="+model_id})
                .done(function(data) {
                	if (data=="'.\app\models\TechModels::$no_specs_hint.'") {
                		$("#arms-specs_settings").hide();
                	} else {
                		$("#specs-hint").html(data);
                		$("#arms-specs_settings").show();
                	}
				})
                .fail(function () {console.log("Ошибка получения данных!")});
            $.ajax({url: "/web/tech-models/hint-description?id="+model_id})
                .done(function(data) {$("#model-hint").html(data);})
                .fail(function () {console.log("Ошибка получения данных!")});
        }';
	$this->registerJs($js, yii\web\View::POS_BEGIN);
	
	
	?>

	
	
	<div class="row " id="arms-specs_settings"
		<?= (is_object($model) && is_object($model->techModel) && $model->techModel->individual_specs)?'':'style="display:none"' ?>
	>
		<div class="col-md-4" >
			<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model,'specs',['lines'=>6]) ?>

		</div>
		<div class="col-md-4" >
			<label class="control-label" >
				Подсказка для заполнения спеки
			</label>
			<br />
			<div id="specs-hint" class="hint-block">
				<?php
				if(is_object($model) && is_object($model->techModel))
					echo Yii::$app->formatter->asNtext($model->techModel->type->comment)
				?>
			</div>
		</div>
		<div class="col-md-4" >
			<label class="control-label" >
				Описание модели
			</label>
			
			<div id="model-hint" class="hint-block">
				Эти данные не нужно вносить в индивидуальную спеку:<br />
				<?php
				if(is_object($model) && is_object($model->techModel))
					echo Yii::$app->formatter->asNtext($model->techModel->comment)
				?>
			</div>
		</div>
	</div>
	
	
    <div class="row" id="arms-user_settings" >
        <div class="col-md-6" >
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

			<?= $form->field($model, 'it_staff_id')->widget(Select2::className(), [
				'data' => \app\models\Users::fetchWorking($model->it_staff_id),
				'options' => ['placeholder' => 'сотрудник не назначен',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>

        </div>
        <div class="col-md-6" >
	        <?= $form->field($model, 'head_id')->widget(Select2::className(), [
		        'data' => \app\models\Users::fetchWorking($model->it_staff_id),
		        'options' => ['placeholder' => 'сотрудник не назначен',],
		        'toggleAllSettings'=>['selectLabel'=>null],
		        'pluginOptions' => [
					'dropdownParent' => $modalParent,
			        'allowClear' => true,
			        'multiple' => false
		        ]
	        ]) ?>
	
			<?= $form->field($model, 'responsible_id')->widget(Select2::className(), [
				'data' => \app\models\Users::fetchWorking($model->responsible_id),
				'options' => ['placeholder' => 'ответственным является сотрудник ИТ','id'=>'arms-responsible_settings'],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
        </div>
    </div>

	<div class="row">
		<div class="col-md-6">
			<?= $form->field($model, 'places_id')->widget(Select2::className(), [
				'data' => $places,
				'options' => ['placeholder' => 'Выберите помещение',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>
		<div class="col-md-6">
			<?= $form->field($model, 'departments_id')->widget(Select2::className(), [
				'data' => \app\models\Departments::fetchNames(),
				'options' => ['placeholder' => 'Выберите подразделение',],
				'toggleAllSettings'=>['selectLabel'=>null],
				'pluginOptions' => [
					'dropdownParent' => $modalParent,
					'allowClear' => true,
					'multiple' => false
				]
			]) ?>
		</div>
	</div>





	<?= $form->field($model, 'contracts_ids')->widget(Select2::className(), [
		'data' => \app\models\Contracts::fetchNames(),
		'options' => ['placeholder' => 'Выберите документы',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>

	<?= $form->field($model, 'lic_items_ids')->widget(Select2::className(), [
		'data' => \app\models\LicItems::fetchNames(),
		'options' => ['placeholder' => 'Выберите лицензии',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>

	<?= $form->field($model, 'lic_groups_ids')->widget(Select2::className(), [
		'data' => \app\models\LicGroups::fetchNames(),
		'options' => ['placeholder' => 'Назначте группы',],
		'toggleAllSettings'=>['selectLabel'=>null],
		'pluginOptions' => [
			'dropdownParent' => $modalParent,
			'allowClear' => true,
			'multiple' => true
		]
	]) ?>

    <div class="form-group">
	    <?= Html::submitButton('Применить', ['class' => 'btn btn-success','name' => 'apply','formaction' => $formActionApply,]) ?>
	    <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success','name' => 'save', 'formaction' => $formActionSave,]) ?>
    </div>
	
	<?= \app\helpers\FieldsHelper::TextAutoresizeField($form,$model,'history',['lines' => 10,]) ?>

	<?php ActiveForm::end(); ?>

</div>
