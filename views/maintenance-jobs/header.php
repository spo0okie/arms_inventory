<?php

use app\components\DynaGridWidget;
use app\components\HistoryWidget;
use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use app\components\ShowArchivedWidget;
use app\components\StripedAlertWidget;
use app\components\WikiPageWidget;
use app\models\Comps;
use app\models\Services;
use app\models\Techs;
use yii\data\ArrayDataProvider;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceJobs */
?>
<div class="float-end text-end">
	<small class="opacity-75"><?= HistoryWidget::widget(['model'=>$model]) ?></small>
	<br>
	<?= ShowArchivedWidget::widget() ?>
</div>

<div class="flex-row d-flex">
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
		<h4>Расписание</h4>
		<?php if (is_object($model->schedule)) {
			echo Html::a($model->schedule->getWorkTimeDescription(),'#',['onclick'=>'$("li#tab-schedule").children("a.nav-link").tab("show");$("li#tab-schedule").click();']);
		} else {
			echo StripedAlertWidget::widget(['title'=> Html::a('создать',['schedules/create','attach_job'=>$model->id],['class'=>'open-in-modal-form','data-reload-page-on-submit'=>1])]);
		} ?>
		<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'reqs']) ?>
	</div>
	<div class="flex-fill">
		<?= ModelFieldWidget::widget(['model'=>$model,'field'=>'links']) ?>
		<?= $this->render('/attaches/model-list',['model'=>$model,'static_view'=>false]) ?>
	</div>
</div>
