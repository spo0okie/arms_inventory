<?php

use kartik\tabs\TabsX;
use yii\helpers\Html;
use yii\bootstrap5\ActiveForm;
//use kartik\form\ActiveForm;
use kartik\select2\Select2;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

$js=<<<JS
commentInput="input#acls-comment";
compInput="select#acls-comps_id";
techInput="select#acls-techs_id";
ipInput="select#acls-ips_id";
srvInput="select#acls-services_id";

function onInputUpdate(input) {
    //console.log("clearing not "+input+": "+$(input).val())
    if ($(input).val()) {
        [commentInput,compInput,techInput,ipInput,srvInput].forEach(item => {
            if (item !== input) {
                //console.log("clearing "+item)
            	$(item).val("").trigger("change");
            }
        })
	}
};
JS;

$this->registerJs($js,yii\web\View::POS_HEAD);

$selectOptions= [
	'dropdownParent' => $modalParent,
	'allowClear' => false,
	'multiple' => false,
];
?>

<div class="acls-form">

    <?php $form = ActiveForm::begin([
		//'enableClientValidation' => false,	//чтобы отключить валидацию через JS в браузере
		//'enableAjaxValidation' => true,		//чтобы включить валидацию на сервере ajax запросы
		'id' => 'acls-form',
		//'validationUrl' => $model->isNewRecord?	//URL валидации на стороне сервера
			//['acls/validate']:	//для новых моделей
			//['acls/validate','id'=>$model->id], //для существующих
		//'action' => Yii::$app->request->getQueryString(),
	]); ?>
	<div class="row">
		<div class="col-md-6">
			<h3>Выберите один ресурс к которому предоставляется доступ</h3>
			<?= TabsX::widget([
				'items'=>[
					[
						'label'=>'ОС',
						'content'=>$form->field($model, 'comps_id')->widget(Select2::className(), [
							'data' => \app\models\Comps::fetchNames(),
							'options' => [
								'placeholder' => 'Выберите ОС',
								'onchange' => 'onInputUpdate(compInput)',
							],
							'pluginOptions' => $selectOptions,
						]),
						'active'=>(bool)$model->comps_id
					],
					[
						'label'=>'Оборудование',
						'content'=>$form->field($model, 'techs_id')->widget(Select2::className(), [
							'data' => \app\models\Techs::fetchNames(),
							'options' => [
								'placeholder' => 'Выберите оборудование',
								'onchange' => 'onInputUpdate(techInput)',
							],
							'pluginOptions' => $selectOptions,
						]),
						'active'=>(bool)$model->techs_id
					],
					[
						'label'=>'IP адрес',
						'content'=>$form->field($model, 'ips_id')->widget(Select2::className(), [
							'data' => \app\models\NetIps::fetchNames(),
							'options' => [
								'placeholder' => 'Выберите IP',
								'onchange' => 'onInputUpdate(ipInput)',
							],
							'pluginOptions' => $selectOptions,
						]),
						'active'=>(bool)$model->ips_id
					],
					[
						'label'=>'Сервис',
						'content'=>$form->field($model, 'services_id')->widget(Select2::className(), [
							'data' => \app\models\Services::fetchNames(),
							'options' => [
								'placeholder' => 'Выберите сервис',
								'onchange' => 'onInputUpdate(srvInput)',
							],
							'pluginOptions' => $selectOptions,
						]),
						'active'=>(bool)$model->services_id
					],
					[
						'label'=>'Другое',
						'content'=>$form->field($model, 'comment')->textInput([
							'maxlength' => true,
							'onchange'=>'onInputUpdate(commentInput)'
						]),
						'active'=>!($model->services_id||$model->comps_id||$model->techs_id||$model->ips_id)
					],
				],
				'position'=>TabsX::POS_ABOVE,
				//'align'=>TabsX::ALIGN_CENTER,
				'encodeLabels'=>false,
				'bordered'=>true,
				
			]) ?>
			<?= $form->field($model, 'notepad')->widget(\kartik\markdown\MarkdownEditor::className(), [
				'showExport'=>false
			]) ?>
		</div>
		<div class="col-md-6">
			<h3>Выберите кому предоставляется доступ к ресурсу</h3>
			<?php if ($model->isNewRecord) { ?>
				<div class="text-center" >
					<img class="exclamation-sign" src="/web/img/exclamation-mark.svg" /><br/>
				</div>
				<div class="text-center" >
					Чтобы добавлять элементы списка контроля доступа, нужно сначала сохранить список
				</div>
			<?php } else { ?>
			<div id="aces-list">
				
				<?php foreach ($model->aces as $ace) {
					echo $this->render('/aces/card', ['model' => $ace]);
				}?>
			</div>
			
				<?= Html::a('<span class="fas fa-plus"></span>', [
					'aces/create',
					'acls_id' => $model->id,
					'ajax' => 1,
					'modal' => 'modal_form_loader'
				], [
					'class' => 'btn btn-primary btn-sm open-in-modal-form',
					'title' => 'Добавление элемента в список доступа',
					'data-update-element' => '#aces-list',
					'data-update-url' => Url::to(['/acls/view', 'id' => $model->id, 'ajax' => 1,'aceCards'=>1]),
				]);
			}?>
		</div>
	</div>
	
	
	<?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
	<?= Html::Button('Применить',	[
			'class' => 'btn btn-primary',
			'onClick' => '$("form#acls-form").attr("action",
				$("#acls-form").attr("action") + ($("#acls-form").attr("action").indexOf("?")>=0?"&":"?") +	"accept=1"
			); $("form#acls-form").trigger("submit");'
		]) ?>

    <?php ActiveForm::end(); ?>

</div>
