<?php

use app\components\Forms\ArmsForm;
use app\components\widgets\page\ModelWidget;
use app\models\Acls;
use app\models\Comps;
use app\models\NetIps;
use app\models\Networks;
use app\models\Services;
use app\models\Techs;
use yii\helpers\Html;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $anchor app\models\Acls эталонный ACL группы */
/* @var $members app\models\Acls[] текущие ресурсы (ACL) группы */
/* @var $model app\models\Acls носитель полей (ресурсы *_ids + notepad, сценарий group) */

$this->title='Редактирование группы';

$this->render('breadcrumbs',['model'=>$anchor,'show_item'=>false]);
$this->params['breadcrumbs'][]='Редактирование группы';
YiiAsset::register($this);
?>
<div class="acls-group-form">

	<h1><?= Html::encode($this->title) ?></h1>

	<div class="alert alert-warning">
		<span class="fas fa-info-circle"></span>
		Изменения применяются ко <b>всем <?= count($members) ?></b> ACL группы.
	</div>

	<?php $form = ArmsForm::begin([
		'model'=>$model,
		'id' => 'group-form',
		//валидируем массивы *_ids (сценарий group)
		'validationUrl'=>['/acls/validate','scenario'=>Acls::SCENARIO_GROUP],
	]); ?>

		<?= $form->field($model,'schedules_id')->hiddenInput()->label(false)->hint(false) ?>

		<div class="row">
			<div class="col-md-6">
				<div class="card bg-light">
					<div class="card-header">Кому и какой предоставляется доступ <small class="text-muted">(общий набор для всей группы)</small></div>
					<div class="card-body">
						<div id="aces-list">
							<?= ModelWidget::widget(['model'=>$anchor,'view'=>'ace-cards','groupMode'=>true]) ?>
						</div>
					</div>
				</div>
			</div>
			<div class="col-md-6">
				<div class="card bg-light mb-3">
					<div class="card-header">Ресурсы группы <small class="text-muted">(можно несколько разных типов)</small></div>
					<div class="card-body">
						<?= $form->field($model, 'comps_ids')->select2(['data'=>Comps::fetchNames()])->label('ОС') ?>
						<?= $form->field($model, 'techs_ids')->select2(['data'=>Techs::fetchNames()])->label('Оборудование') ?>
						<?= $form->field($model, 'ips_ids')->select2(['data'=>NetIps::fetchNames()])->label('IP адреса') ?>
						<?= $form->field($model, 'networks_ids')->select2(['data'=>Networks::fetchNames()])->label('IP сети') ?>
						<?= $form->field($model, 'services_ids')->select2(['data'=>Services::fetchNames()])->label('Сервисы') ?>
						<?= $form->field($model, 'comment')->textInput(['maxlength' => true])->label('Другое (несколько описаний — через запятую)') ?>
					</div>
				</div>
				<?= $form->field($model, 'notepad')->text(['height'=>100,'rows'=>6]) ?>
			</div>
		</div>

		<?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>

	<?php ArmsForm::end(); ?>

</div>
