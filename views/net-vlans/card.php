<?php

use app\components\TextFieldWidget;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\NetVlans */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

?>

<h1>
	<?= Html::encode($model->sname) ?>
	<?= $static_view?'':(Html::a('<span class="fas fa-pencil-alt"></span>',['net-vlans/update','id'=>$model->id])) ?>
	<?php  if(!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash"/>', ['net-vlans/delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить этот элемент? Действие необратимо',
			'method' => 'post',
		],
	]) ?>
</h1>
<?= TextFieldWidget::widget(['model'=>$model,'field'=>'comment']) ?>
<br /><br />

<h4>L2 Домен</h4>
<?= $this->render('/net-domains/item',['model'=>$model->netDomain]) ?>



