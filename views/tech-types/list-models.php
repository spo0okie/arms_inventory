<?php

use yii\bootstrap5\Modal;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\TechTypes */

?>
<div class="row">
	<div class="col-md-6">
		
			<table class="table table-bordered table-condensed table-striped">
			<tr>
				<th>Модель</th>
				<th>Кол-во экз.</th>
			</tr>
			<?php foreach ($techModels as $techModel) { ?>
				<tr>
					<td>
						<?= $this->render('/tech-models/item',['model'=>$techModel,'long'=>true]) ?><br />
					</td>
					<td>
						<?= $techModel->usages ?>
					</td>
				</tr>
			<?php } ?>
		</table>
		
		<?= Html::a('Добавить', [
			'/tech-models/create',
			'TechModels[type_id]'=>$model->id
		], [
			'class' => 'btn btn-success open-in-modal-form',
			'data-reload-page-on-submit'=>1
		])?>
	</div>
	<div class="col-md-6">
		<h4><?= $model->getAttributeLabel('comment')?></h4>
		<i><?= $model->getAttributeHint('comment')?></i>
		<p>
			<?= Yii::$app->formatter->asNtext($model->comment) ?>
		</p>
		<br />
		
		<h4><?= $model->getAttributeLabel('code')?></h4>
		<i><?= $model->getAttributeHint('code')?></i>
		<p>
			<?= $model->code ?>
		</p>
	</div>
</div>

