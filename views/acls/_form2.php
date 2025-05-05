<?php

use app\components\Forms\ArmsForm;
use kartik\tabs\TabsX;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */
/* @var $ace app\models\Aces */
/* @var $form yii\widgets\ActiveForm */
if (!isset($modalParent)) $modalParent=null;

/** @noinspection JSUnusedLocalSymbolsInspection */
/** @noinspection JSUnusedLocalSymbols */
$js= <<<JS
commentInput="input#acls-comment";
compInput="select#acls-comps_id";
techInput="select#acls-techs_id";
ipInput="select#acls-ips_id";
netInput="select#acls-networks_id";
srvInput="select#acls-services_id";
function onInputUpdate(input) {
    //console.log("clearing not "+input+": "+$(input).val())
    if ($(input).val()) {
        [commentInput,compInput,techInput,ipInput,srvInput,netInput].forEach(item => {
            if (item !== input) {
                //console.log("clearing "+item)
            	$(item).val("").trigger("change");
            }
        })
	}
}
JS;

$this->registerJs($js,yii\web\View::POS_HEAD);

?>

<div class="acls-form">
    <?php $form = ArmsForm::begin([
		'model'=>$model,
		'id' => 'acls-form',
	]); ?>
	<?= $form->field($model,'schedules_id')->hiddenInput()->label(false)->hint(false) ?>

	<div class="for-alert"></div>
	<div class="row">
		<div class="<?= $model->isNewRecord?'col-md-8':'col-md-6' ?>">
			<?php if ($model->isNewRecord) {
				echo $this->render('/aces/_form_layout', ['model' => $ace,'form'=>$form]);
			} else { ?>
				<div class="card bg-light">
					<div class="card-header">Выберите кому и какой предоставляется доступ</div>
					<div class="card-body">
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
						]); ?>
					</div>
				</div>
			<?php }?>
		</div>
		<div class="<?= $model->isNewRecord?'col-md-4':'col-md-6' ?>">
			<div class="card bg-light">
				<div class="card-header">Выберите <b>один</b> ресурс к которому предоставляется доступ</div>
				<div class="card-body">
					<?= TabsX::widget([
						'items'=>[
							[
								'label'=>'ОС',
								'content'=>$form->field($model, 'comps_id')->select2(['options'=>['onchange' => 'onInputUpdate(compInput)']]),
								'active'=>(bool)$model->comps_id
							],
							[
								'label'=>'Оборудование',
								'content'=>$form->field($model, 'techs_id')->select2(['options'=>['onchange' => 'onInputUpdate(techInput)']]),
								'active'=>(bool)$model->techs_id
							],
							[
								'label'=>'IP адрес',
								'content'=>$form->field($model, 'ips_id')->select2(['options'=>['onchange' => 'onInputUpdate(ipInput)']]),
								'active'=>(bool)$model->ips_id
							],
							[
								'label'=>'IP сеть',
								'content'=>$form->field($model, 'networks_id')->select2(['options'=>['onchange' => 'onInputUpdate(netInput)']]),
								'active'=>(bool)$model->networks_id
							],
							[
								'label'=>'Сервис',
								'content'=>$form->field($model, 'services_id')->select2(['options'=>['onchange' => 'onInputUpdate(srvInput)']]),
								'active'=>(bool)$model->services_id
							],
							[
								'label'=>'Другое',
								'content'=>$form->field($model, 'comment')->textInput([
									'maxlength' => true,
									'onchange'=>'onInputUpdate(commentInput)'
								]),
								'active'=>!($model->services_id||$model->comps_id||$model->techs_id||$model->ips_id||$model->networks_id)
							],
						],
						'position'=>TabsX::POS_ABOVE,
						//'align'=>TabsX::ALIGN_CENTER,
						'encodeLabels'=>false,
						'bordered'=>true,
						
					]) ?>
				</div>
			</div>
			<?= $form->field($model, 'notepad')->text(['height'=>100,'rows'=>6]) ?>
		</div>
	</div>
	
	
	<?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
	<?= Html::Button('Применить',	[
			'class' => 'btn btn-primary',
			'onClick' => '$("form#acls-form").attr("action",
				$("#acls-form").attr("action") + ($("#acls-form").attr("action").indexOf("?")>=0?"&":"?") +	"accept=1"
			); $("form#acls-form").trigger("submit");'
		]) ?>

    <?php ArmsForm::end(); ?>

</div>
