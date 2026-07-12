<?php

use app\components\HistoryWidget;
use app\components\ListObjectsWidget;
use app\components\TextFieldWidget;
use app\models\Aces;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */
/** @var app\models\Aces $model */

/** @var boolean $groupMode режим группового редактирования ACL */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;
if (!isset($groupMode)) $groupMode=false;

$accessTypes=[];

foreach ($model->accessTypes as $accessType)
	$accessTypes[]=$accessType->name;

if (!count($accessTypes)) $accessTypes[]= Aces::$noAccessName;


?>

<div class="card w-100 my-2 ace-card shadow-sm g-0" id="ace_card_<?= $model->id ?>">
	<div class="d-flex g-0">
		<div class="p-2 text-wrap flex-fill small">
			<?php if (count($model->subjects)) {
				echo \app\components\ModelFieldWidget::widget([
					'model'=>$model,
					'field'=>'subjects',
					'title'=>false,
					'item_options'=>[
						'static_view'=>true,
						'show_ips'=>$model->hasIpAccess(),
						'show_phone'=>$model->hasPhoneAccess(),
						'short'=>true,
					],
					'lineBr'=>false,
					'card_options'=>['cardClass'=>'m-0 p-0'],
					'glue'=>'<br>'
				]);
			} else { ?>
				<span class="text-center divider2-striped">
					<span class="ace-card p-1">
						<span class="fas fa-exclamation-triangle"></span>
						НЕТ СУБЪЕКТОВ
						<span class="fas fa-exclamation-triangle"></span>
					</span>
				</span>
				<span class="row text-center"><small >в этой записи доступа не указано, кому он предоставляется — добавьте субъектов (пользователей, ОС, IP)</small></span>

			<?php } ?>
		</div>
		<div class="col-md-4 ace-access-card d-flex flex-column pt-2 pull-right">
			<div class="text-center text-white"><?= implode(', ',$accessTypes) ?></div>
			<?php if (!$static_view) { ?>
				<div class="row mt-auto g-0">
					<div class="btn-group" role="group">
						<?=  Html::a('<span class="fas fa-pencil-alt"></span>',$groupMode?
						[
							'/acls/group-ace-edit',
							'id'=>$model->acl->id,
							'ace'=>$model->id,
							'ajax'=>1,
							'modal'=>'modal_form_loader'
						]:[
							'/aces/update',
							'id'=>$model->id,
							'ajax'=>1,
							'modal'=>'modal_form_loader'
						],[
							'class' => 'btn btn-sm text-white ace-access-buttons open-in-modal-form',
							'title' => 'Правка элемента доступа',
							'data-update-element' => '#ace_card_'.$model->id,
							'data-update-url' => Url::to(['/aces/view','id'=>$model->id]),
						]) ?>
						<?=  HistoryWidget::widget([
							'model'=>$model,
							'showUser'=>false,
							'showDate'=>false,
							'empty'=>'',
							'prefix'=>'',
							'iconOptions'=>['class'=>'btn btn-sm text-white ace-access-buttons'],
						])?>
						<?=  Html::a('<span class="fas fa-trash"/>',
							$groupMode?
							['/acls/group-ace-delete', 'id' => $model->acl->id, 'ace'=>$model->id, 'return'=>'previous']:
							['/aces/delete', 'id' => $model->id, 'return'=>'previous'],
							[
								'data' => [
									'confirm' => $groupMode?
										'Удалить этого участника из доступа ко всем ресурсам группы? Действие необратимо!':
										'Удалить этого участника доступа? Действие необратимо!',
									'method' => 'post',
								],
								'class'=>'btn btn-sm text-white ace-access-buttons'
							]
						)?>
					</div>
				</div>
			<?php } ?>
		</div>
	</div>
	<?php if ($model->notepad) { ?>
		<div class="p-1 small text-wrap border-top">
			<?=  \app\components\ModelFieldWidget::renderFieldValue($model,'notepad') ?>
		</div>
	<?php } ?>

</div>
