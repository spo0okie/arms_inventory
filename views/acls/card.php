<?php

use app\components\HistoryWidget;
use app\components\TextFieldWidget;
use yii\helpers\Html;
use yii\helpers\Url;
use app\components\widgets\page\ModelWidget;

/** @var yii\web\View $this */
/** @var app\models\Acls $model */
/** @var app\models\Acls[] $models */

if (!isset($models)) $models=[];
$groupMode=(bool)count($models);
if ($groupMode) $model=reset($models);

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;
?>

<div class="card w-100 px-2 acl-card shadow mb-1" id="acl_card_<?= $model->id ?>">
		<div class="row g-2">
			<div class="col-md-9">
				<?= $this->render('ace-cards',['model'=>$model,'static_view'=>$static_view, 'groupMode'=>$groupMode]) ?>
			</div>

			<div class="col-md-3 py-2">
				<?php if ($groupMode) { ?>
					<div class="row">
						<div class="btn-group " role="group">
							<span class="btn btn-primary btn-sm" title="ACL с одинаковым набором ACE объединены в группу">
								<span class="fas fa-layer-group"></span> <?= count($models) ?> ресурсов
							</span>
							<?php if (!$static_view) { ?>
								<?= Html::a('<span class="fas fa-pencil-alt"></span>',
									['/acls/group-resources','id'=>$model->id],
									['class'=>'btn btn-primary btn-sm','title'=>'Добавить или убрать ресурсы группы']
								) ?>
								<?= Html::a('<span class="fas fa-trash"></span>',
									['/acls/delete-group','id'=>$model->id],
									[
										'class'=>'btn btn-danger btn-sm',
										'title'=>'Удалить всю группу (все её ACL)',
										'data'=>[
											'confirm'=>'Удалить всю группу — все '.count($models).' ACL? Действие необратимо',
											'method'=>'post',
										],
									]
								) ?>
							<?php } ?>
						</div>
					</div>
					<ul class="list-unstyled mb-0 acl-group-resources">
						<?php foreach ($models as $acl) { ?>
							<li class="mb-1" id="acl_card_<?= $acl->id ?>">
								<?= ModelWidget::widget(['model'=>$acl,'options'=>['static_view'=>true]]) ?>
							</li>
						<?php } ?>
					</ul>
				<?php } else { ?>
					<h5 class="card-title"><?= ModelWidget::widget(['model'=>$model,'options'=>['static_view'=>true]])?></h5>
					<div class="row">
						<div class="btn-group " role="group">
							<?=  Html::a('<span class="fas fa-plus"></span>',[
								'/aces/create',
								'Aces[acls_id]'=>$model->id,
							],[
								'class' => 'btn btn-primary btn-sm open-in-modal-form',
								'title' => 'Добавление элемента в список доступа',
								'data-update-element' => '#acl_card_'.$model->id,
								'data-update-url' => Url::to(['/acls/view','id'=>$model->id]),
							]) ?>

							<?= Html::a('<span class="fas fa-pencil-alt"></span>',['/acls/update','id'=>$model->id],['class'=>'btn btn-primary btn-sm']) ?>
							<?= HistoryWidget::widget([
								'model'=>$model,
								'showUser'=>false,
								'showDate'=>false,
								'empty'=>'',
								'prefix'=>'',
								'iconOptions'=>['class'=>'btn btn-sm btn-primary'],
							])?>
							<?= Html::a('<span class="fas fa-trash"/>', ['/acls/delete', 'id' => $model->id], [
								'data' => [
									'confirm' => 'Удалить этот элемент? Действие необратимо',
									'method' => 'post',
								],
								'class'=>'btn btn-danger btn-sm'
							]) ?>
						</div>
					</div>
				<?php } ?>
			</div>
		</div>
	<small><?= TextFieldWidget::widget(['model'=>$model,'field'=>'notepad']) ?></small>
</div>
