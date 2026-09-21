<?php

use app\components\HistoryWidget;
use app\components\ItemObjectWidget;
use app\components\LinkObjectWidget;
use app\components\ListObjectsWidget;
use kartik\markdown\Markdown;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\modules\schedules\models\Schedules */

if (!isset($static_view)) $static_view=false;
?>
<div class="schedules-view">
	<div class="d-flex flex-wrap flex-row-reverse">
		<div class="small opacity-75"><?= HistoryWidget::widget(['model'=>$model]) ?></div>
		<div class="flex-fill">
			<h1>
				<?= ItemObjectWidget::widget([
					'model'=>$model,
					'link'=> LinkObjectWidget::widget([
						'model'=>$model,
						'static'=>$static_view,
						'name'=>$model->displayName,
						'hideUndeletable'=>false,
					])
				]) ?>&nbsp;
			</h1>
		</div>
	</div>
	<?= $model->description?('<p>'.$model->description.'</p>'):'' ?>
	<?php if ($model->parent_id && !is_object($model->parent)) {
		//parent_id указывает на удаленное расписание (FK в БД нет): унаследованный график
		//потерян, расписание некорректно - сообщаем, а не делаем вид, что родителя не было
		?>
		<div class="alert alert-danger">
			<span class="fas fa-exclamation-triangle"></span>
			Расписание некорректно: исходное (родительское) расписание #<?= (int)$model->parent_id ?> не найдено.
			Унаследованный от него график недоступен — показано только то, что задано в этом расписании.
			Исправьте исходное расписание в форме редактирования или удалите это расписание.
		</div>
	<?php } ?>
	<?php if ($model->isPrivate && !$static_view) {
		//индивидуальное расписание (issue #139): поясняем режим и даем обратный ход -
		//«дать имя» переводит расписание в общий пул (имя предзаполняем вычисленным)
		?>
		<p class="text-muted">
			<span class="fas fa-user-lock"></span>
			Индивидуальное расписание: принадлежит одному объекту, в общем списке
			расписаний не показывается и для других объектов недоступно.
			Удаляется вместе со своим объектом.
			<?= Html::a('Сделать общим',[
				'update',
				'id'=>$model->id,
				'Schedules[name]'=>$model->generatedName,
			],[
				'qtip_ttip'=>'Дать расписанию название, чтобы его можно было '
					.'использовать для других объектов (и чтобы оно не удалилось вместе с этим)',
			]) ?>
		</p>
	<?php } ?>

	<div class="row">
		<div class="col-md-6">
			<h3 class="mb-3"><?= $this->render('week-description',['model'=>$model])?></h3>
			<?= is_object($model->parent)?('Родительское расписание :'.$this->render('item',['model'=>$model->parent])):'' ?>
			<?= $this->render('7days',['model'=>$model])?>
			<?= $this->render('services',['model'=>$model])?>
			<?= ListObjectsWidget::widget([
				'models'=>$model->childrenNonOverrides,
				'title'=>$model->getAttributeLabel('children')
			]) ?>
			<?= $this->render('@app/views/attaches/model-list',compact(['model','static_view'])) ?>
		</div>
		<div class="col-md-6">
			<?= $this->render('week/list',['model'=>$model])?>
			<?= $this->render('exceptions',['model'=>$model])?>
		</div>
	</div>
	<?php if (strlen($model->history??'')) { ?>
		<h3>Записная книжка:</h3>
		<p>
			<?= Markdown::convert($model->history) ?>
		</p>
		<br />
	<?php } ?>
</div>
