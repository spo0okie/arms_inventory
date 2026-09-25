<?php

/*
 * Содержимое формы вынесено в отдельный файл, т.к. может быть использовано и в форме ACE и в форме ACL
 */

use app\models\Users;

/* @var $this yii\web\View */
/* @var $model app\models\Aces */
/* @var $form yii\widgets\ActiveForm */

if (!isset($modalParent)) $modalParent=null;

?>

	<div class="row">
		<div class="col-md-6">
			<div class="card bg-light">
				<div class="card-header">Кому предоставляется доступ</div>
				<div class="card-body pb-0">
					<?= $form->field($model,'users_ids')->select2(['data' => Users::fetchWorking()]) ?>

					<?= $form->field($model, 'comps_ids')->select2() ?>

					<?= $form->field($model, 'services_ids')->select2() ?>

					<?= $form->field($model, 'segments_ids')->select2() ?>

					<?= $form->field($model,'ips')->textAutoresize(['rows' => 1]) ?>

					<?= $form->field($model, 'comment') ?>
				</div>
			</div>



		</div>
		<div class="col-md-6">
			<div class="card bg-light mb-3">
				<div class="card-header">Зачем предоставляется доступ</div>
				<div class="card-body pb-0">
					<?= $form->field($model,'name');?>
				</div>
			</div>
			<?/*общий пикер типов доступа (фильтр, создание нового типа в модалке,
			инпуты IP-параметров); карточку с заголовком и иконкой «?» рисует сам*/?>
			<?= $this->render('/access-types/_picker',[
				'form'=>$form,
				'model'=>$model,
				'attribute'=>'access_types_ids',
				'paramsAttribute'=>'ipParams',
				'label'=>'Какой этим объектам предоставляется доступ',
				/* проброс - свойство записи (хопа), а не типа доступа: типы остаются обычными,
			       в их сетевых параметрах пишется "порт входа -> порт назначения" */
				'footer'=>$form->field($model, 'is_forward'),
			]) ?>
			<?php /* транзит: соседние хопы маршрута. Кандидаты подбираются по посреднику
			       (ресурс этой записи — для следующих, её субъекты — для предыдущих). Предыдущие
			       считаются по введённым субъектам — работают и в новой записи с предзаполненным
			       субъектом; следующим нужен ресурс ACL — у новой записи они появятся после сохранения */ ?>
			<div class="card bg-light mb-3">
				<div class="card-header">Транзит (маршрут из нескольких хопов)</div>
				<div class="card-body pb-0">
					<?= $form->field($model, 'prev_aces_ids')->select2(['data' => $model->prevCandidates()]) ?>
					<?= $form->field($model, 'next_aces_ids')->select2(['data' => $model->nextCandidates()]) ?>
				</div>
			</div>
			<?= $form->field($model, 'notepad')->text(['height'=>100,'rows'=>6]) ?>
		</div>
		<?= $form->field($model,"acls_id")->hiddenInput()->label(false)->hint(false) ?>
	</div>
