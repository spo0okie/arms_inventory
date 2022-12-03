<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Ports */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

if (is_object($model->linkPort)) {
?>
<h1>Схема коммутации <?= $model->fullName ?></h1>
<br />
	<table>
		<?php if ($model->comment) { ?>
		<tr>
			<td></td>
			<td>
				<svg width="80" height="40" xmlns="http://www.w3.org/2000/svg">
					<path d="M0 40 l20 -20 l60 0"  fill="transparent" stroke="black" stroke-width="1%"/>
				</svg>
				<?= Yii::$app->formatter->asNtext($model->comment) ?>
			</td>
		</tr>
		<?php } ?>
		<tr>
			<td class="text-end align-top">
				<h2>
					<?= $this->render('/ports/item',['model'=>$model,'static_view'=>$static_view,'include_tech'=>true,'badge'=>true]) ?>
				</h2>
			</td>
			<td rowspan="3">
				<svg width="50" height="100" xmlns="http://www.w3.org/2000/svg">
					<circle cx="0" cy="50" r="40"  fill="transparent" stroke="black" stroke-width="3%"/>
					<circle cx="0" cy="10" r="3"  fill="black" stroke="black"/>
					<circle cx="0" cy="90" r="3"  fill="black" stroke="black"/>
				</svg>
			</td>
		</tr>
		<tr>
			<td>&nbsp;</td>
			<td></td>
		</tr>
		<tr>
			<td class="text-end align-bottom">
				<h4 class="p-0 m-0">
					<?= $this->render('/ports/item',['model'=>$model->linkPort,'static_view'=>$static_view,'include_tech'=>true,'badge'=>true]); ?>
				</h4>
			</td>
			<td></td>
		</tr>
		<?php if ($model->linkPort->comment) { ?>
			<tr>
				<td></td>
				<td>
					<svg width="80" height="40" xmlns="http://www.w3.org/2000/svg">
						<path d="M0 0 l20 20 l60 0"  fill="transparent" stroke="black" stroke-width="1%"/>
					</svg>
					<?= Yii::$app->formatter->asNtext($model->linkPort->comment) ?>
				</td>
			</tr>
		<?php } ?>
	</table>

<?php } else { ?>

	<h1>Схема коммутации <?= $model->fullName ?></h1>
	<br />
	<table>
		<tr>
			<td>
				<?= $this->render('/ports/item',['model'=>$model,'static_view'=>$static_view,'include_tech'=>true,'badge'=>true]) ?>
			</td>
			<td class="p-2">
				<?= $model->comment?(' - '.Yii::$app->formatter->asNtext($model->comment)):''?>
			</td>
		</tr>
	</table>
	<div class="alert alert-striped">
		Порт ни к чему не подключен
	</div>

<?php }