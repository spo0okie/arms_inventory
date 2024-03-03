<?php

use app\components\HistoryWidget;
use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use app\components\ShowArchivedWidget;
use app\components\StripedAlertWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceJobs */
?>

<div class="d-flex flex-wrap flex-row-reverse">
	<div class="ms-5 d-flex">
		<div class="text-end opacity-75 small"><?= HistoryWidget::widget(['model'=>$model]) ?></div>
		<div class="text-end ms-5"><?= ShowArchivedWidget::widget() ?></div>
	</div>
	<div class="d-flex flex-fill flex-row flex-nowrap">
		<div class="me-5">
			<h1>
				<?= LinkObjectWidget::widget([
						'model'=>$model,
						'confirmMessage' => 'Действительно удалить эти регламентные операции?',
						'undeletableMessage'=>'Нельзя удалить эту схему обслуживания, т.к. есть привязанные к ней объекты',
				]) ?>
			</h1>
			<?= Yii::$app->formatter->asNtext($model->description) ?>
		</div>
		<div class="me-5">
			<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'service']) ?>
			<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'responsible']) ?>
			<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'support']) ?>
		</div>
		<div class="me-5">
			<div class="mb-3">
				<h4>Расписание</h4>
				<?php if (is_object($model->schedule)) {
					echo Html::a(
						$this->render('/schedules/week-description',['model'=>$model->schedule]),
						'#',
						['onclick'=>'$("li#tab-schedule").children("a.nav-link").tab("show");$("li#tab-schedule").click();']
					);
				} else {
					echo StripedAlertWidget::widget(['title'=> Html::a('создать',['schedules/create','attach_job'=>$model->id],['class'=>'open-in-modal-form word-wrap','data-reload-page-on-submit'=>1])]);
				} ?>
			</div>
			<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'reqs']) ?>
		</div>
		<div>
			<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'links']) ?>
			<?= $this->render('/attaches/model-list',['model'=>$model,'static_view'=>false]) ?>
		</div>
	</div>
</div>
