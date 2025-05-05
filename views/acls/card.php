<?php

use app\components\HistoryWidget;
use app\components\TextFieldWidget;
use kartik\markdown\Markdown;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;
?>

<div class="card w-100 px-2 acl-card shadow mb-1" id="acl_card_<?= $model->id ?>">
		<div class="row g-2">
			<div class="col-md-9">
				<?= $this->render('ace-cards',['model'=>$model,'static_view'=>$static_view]) ?>
			</div>
			
			<div class="col-md-3 py-2">
				<h5 class="card-title"><?= $this->render('item',['model'=>$model,'static_view'=>true])?></h5>
				<div class="row">
					<div class="btn-group " role="group">
						<?php //Html::a('<span class="fas fa-plus"></span>',['aces/create','acls_id'=>$model->id],['class'=>'btn btn-primary btn-sm']) ?>
						<?=  Html::a('<span class="fas fa-plus"></span>',[
							'aces/create',
							'Aces[acls_id]'=>$model->id,
						],[
							'class' => 'btn btn-primary btn-sm open-in-modal-form',
							'title' => 'Добавление элемента в список доступа',
							'data-update-element' => '#acl_card_'.$model->id,
							'data-update-url' => Url::to(['/acls/view','id'=>$model->id]),
						]) ?>
						
						<?= Html::a('<span class="fas fa-pencil-alt"></span>',['acls/update','id'=>$model->id],['class'=>'btn btn-primary btn-sm']) ?>
						<?= HistoryWidget::widget([
							'model'=>$model,
							'showUser'=>false,
							'showDate'=>false,
							'empty'=>'',
							'prefix'=>'',
							'iconOptions'=>['class'=>'btn btn-sm btn-primary'],
						])?>
						<?= Html::a('<span class="fas fa-trash"/>', ['acls/delete', 'id' => $model->id], [
							'data' => [
								'confirm' => 'Удалить этот элемент? Действие необратимо',
								'method' => 'post',
							],
							'class'=>'btn btn-danger btn-sm'
						]) ?>
					</div>
				</div>

			</div>
		</div>
	<small><?= TextFieldWidget::widget(['model'=>$model,'field'=>'notepad']) ?></small>
</div>