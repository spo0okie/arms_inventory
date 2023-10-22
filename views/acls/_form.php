<?php

use app\models\Comps;
use app\models\NetIps;
use app\models\Services;
use app\models\Techs;
use kartik\markdown\MarkdownEditor;
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

/** @noinspection JSUnusedLocalSymbolsInspection */
$js= <<<JS
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
}
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
	<?= $form->field($model,'schedules_id')->hiddenInput()->label(false)->hint(false) ?>

	<div class="for-alert"></div>
	<div class="row">
		<div class="col-md-6">
			<div class="card bg-light">
				<div class="card-header">Выберите кому и какой предоставляется доступ</div>
				<div class="card-body">
					<?php if ($model->isNewRecord) { ?>
						<div class="text-center" >
							<img class="exclamation-sign" src="/web/img/exclamation-mark.svg" alt="!" /><br/>
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
							'Aces[acls_id]' => $model->id,
							'modal' => 'modal_form_loader'
						], [
							'class' => 'btn btn-primary btn-sm open-in-modal-form',
							'title' => 'Добавление элемента в список доступа',
							'data-update-element' => '#aces-list',
							'data-update-url' => Url::to(['/acls/ace-cards', 'id' => $model->id]),
						]);
					}?>
				</div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="card bg-light">
				<div class="card-header">Выберите <b>один</b> ресурс к которому предоставляется доступ</div>
				<div class="card-body">
					<?= TabsX::widget([
						'items'=>[
							[
								'label'=>'ОС',
								'content'=>$form->field($model, 'comps_id')->widget(Select2::class, [
									'data' => Comps::fetchNames(),
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
								'content'=>$form->field($model, 'techs_id')->widget(Select2::class, [
									'data' => Techs::fetchNames(),
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
								'content'=>$form->field($model, 'ips_id')->widget(Select2::class, [
									'data' => NetIps::fetchNames(),
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
								'content'=>$form->field($model, 'services_id')->widget(Select2::class, [
									'data' => Services::fetchNames(),
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
				</div>
			</div>
			<?= $form->field($model, 'notepad')->widget(MarkdownEditor::class, [
				'showExport'=>false
			]) ?>
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
