<?php

use kartik\markdown\Markdown;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<!---





<table class="acls-table">
	<tr>
		<?= $this->render('tdrow',['model'=>$model]) ?>
	</tr>
</table>

-->

<div class="card w-100 px-2 acl-card shadow">
		<div class="row g-2">
			<div class="col-md-9">
				<?php
				foreach ($model->aces as $ace) {
					echo $this->render('/aces/card',['model'=>$ace]);
				}
				
				?>
				<?= Markdown::convert($model->notepad) ?>
			</div>
			<div class="col-md-3 py-2">
				<h5 class="card-title"><?= $model->sname ?></h5>
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
</div>