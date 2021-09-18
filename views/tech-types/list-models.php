<?php

use yii\bootstrap5\Modal;

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
		
		<?php
		Modal::begin([
			'id'=>'tech_models-add',
			'title' => '<h2>Добавление модели оборудования</h2>',
			'size' => Modal::SIZE_LARGE,
			'toggleButton' => [
				'label' => 'Добавить модель',
				'tag' => 'a',
				'class' => 'btn btn-success',
			],
		]);
		
		$techModel=new \app\models\TechModels();
		$techModel->type_id=$model->id;
		
		echo $this->render(
			'/tech-models/_form',
			[
				'model'=>$techModel,
			]
		);
		
		$js = <<<JS
			$('#tech_models-add').removeAttr('tabindex'); //иначе не будет работать поиск в виджетах Select2
			$('#tech_models-edit-form').on('beforeSubmit', function(){
				var data = $(this).serialize();
				$.ajax({
					url: '/web/tech-models/create',
					type: 'POST',
					data: data,
					success: function(res){
						//alert();
						window.location.reload();// replace(window.location.toString()+'&manufacturers_id='+res[0].id);
					},
					error: function(){
						alert('Error!');
					}
				});
				return false;
			});
JS;
		
		$this->registerJs($js);
		Modal::end();
		?>
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

