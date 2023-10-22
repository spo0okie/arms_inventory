<?php

use app\models\Acls;
use yii\helpers\Html;
use yii\web\YiiAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

$this->title = $model->sname;
$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;


$this->render('breadcrumbs',['model'=>$model,'static_view'=>true]);

YiiAsset::register($this);

?>
<div class="acls-view">
	<h1>
		<?= Html::encode(Acls::$title.'#'.$model->id.': '.$model->sname) ?>
		<?php  if(!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash"/>', ['acls/delete', 'id' => $model->id], [
			'data' => [
				'confirm' => 'Удалить этот элемент? Действие необратимо',
				'method' => 'post',
			],
		]) ?>
	</h1>
	<?= $this->render('card',['model'=>$model]) ?>
</div>
