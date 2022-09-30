<?php
/**
 * Вывод портов устройства
 * User: aareviakin
 * Date: 01.03.2021
 * Time: 20:49
 */

/* @var \app\models\Techs $model */
/* @var $this yii\web\View */

if (!is_object($model->model) || !is_array($model->model->portsList) || !count($model->model->portsList)) { ?>
	<div class="alert alert-striped">
		Для этой модели оборудования не объявлено стандартных сетевых портов.
		Если это неверно - <?= \yii\helpers\Html::a('отредактируйте модель оборудования.',[
			'/tech-models/update',
			'id'=>$model->model_id,
			'return'=>'previous'
		]) ?>
	</div>
	<br/>
<?php }

if (count($model->portsList)) { ?>

	<table class="table table-striped">
		<tr>
			<th>
				Порт
			</th>
			<th>
				Пояснение
			</th>
			<th colspan="3">
				Соединение с
			</th>
		</tr>

		<?php foreach ($model->portsList as $port) {
			$port['model']=$model;
			echo $this->render('port-row',$port);
		}?>
	</table>
<?php }


echo \yii\helpers\Html::a(
	'Добавить нестандартный порт',
	[
		'/ports/create',
		'techs_id'=>$model->id,
		'return'=>'previous'
	],[
		'class'=>'btn btn-info',
		'title' => 'Стандартные порты редактируются в модели оборудования'
	]
) ?>
