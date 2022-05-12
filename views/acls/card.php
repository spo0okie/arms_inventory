<?php

use kartik\markdown\Markdown;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<div class="card w-100 px-2 acl-card shadow mb-1">
		<div class="row g-2">
			<div class="col-md-9">
				<?php
				foreach ($model->aces as $ace) {
					echo $this->render('/aces/card',['model'=>$ace]);
				}
				
				?>
			</div>
			<div class="col-md-3 py-2">
				<h5 class="card-title"></span><?php
					if (strlen($model->comment))
						echo $model->comment;
					
					elseif (($model->comps_id) and is_object($model->comp))
						echo $this->render('/comps/item',['model'=>$model->comp,'static_view'=>true,'icon'=>true]);

					elseif (($model->techs_id) and is_object($model->tech))
						echo $this->render('/techs/item',['model'=>$model->tech,'static_view'=>true,'icon'=>true]);

					elseif (($model->services_id) and is_object($model->service))
						echo $this->render('/services/item',['model'=>$model->service,'static_view'=>true,'icon'=>true]);

					elseif (($model->ips_id) and is_object($model->ip))
						echo $this->render('/net-ips/item',['model'=>$model->ip,'static_view'=>true,'icon'=>true,'no_class'=>true]);
					else
						echo \app\models\Acls::$emptyComment;
					?></h5>
				<div class="row">
					<div class="btn-group " role="group">
						<?= Html::a('<span class="fas fa-plus"></span>',['aces/create','acls_id'=>$model->id],['class'=>'btn btn-primary btn-sm']) ?>
						<?= Html::a('<span class="fas fa-pencil-alt"></span>',['acls/update','id'=>$model->id,'return'=>'previous'],['class'=>'btn btn-primary btn-sm']) ?>
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
	<small><?= Markdown::convert($model->notepad) ?></small>
</div>