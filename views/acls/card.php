<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Acls */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1>
	<?= Html::encode(\app\models\Acls::$title.'#'.$model->id.': '.$model->sname) ?>
	<?php  if(!$static_view&&$deleteable) echo Html::a('<span class="glyphicon glyphicon-trash"/>', ['acls/delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить этот элемент? Действие необратимо',
			'method' => 'post',
		],
	]) ?>
</h1>


<h2>
	Ресурс:
	<?= $this->render('resource',['model'=>$model]) ?>
	<?= $static_view?'':(Html::a('<span class="glyphicon glyphicon-pencil"></span>',['acls/update','id'=>$model->id])) ?>
</h2>

<h2>Доступы к ресурсу</h2>
<?php
foreach ($model->aces as $ace) {
	echo $this->render('/aces/item',['model'=>$ace]).'<br />';
}
echo \yii\helpers\Html::a('Добавить',['aces/create','acls_id'=>$model->id],['class'=>'btn btn-success']);