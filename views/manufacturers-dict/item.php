<?php
/**
 * Элемент словарь производителей
 * Created by PhpStorm.
 * User: spookie
 * Date: 10.11.2020
 * Time: 19:40
 */
use yii\helpers\Html;

/* @var $model \app\models\ManufacturersDict */

if (!isset($static_view)) $static_view=false;

if (is_object($model)) { ?>
	
	<span class="manufacturers-dict-item">
		<?= $model->word ?>
		<?php if (!$static_view) { ?>
			<?= Html::a('<span class="glyphicon glyphicon-pencil" />',['/manufacturers-dict/update','id'=>$model->id]) ?>
			<?= Html::a('<span class="glyphicon glyphicon-trash" />',
				[
					'/manufacturers-dict/delete',
					'id'=>$model->id,
				],[
					'data'=>[
						'method'=>'post',
						'confirm'=>'Удалить этот вариант написания производителя?',
					],
				])
			?>
		<?php } ?>
	</span>

<?php } else echo "Отсутствует";