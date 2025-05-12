<?php

use app\components\TextFieldWidget;
use app\components\UrlListWidget;
use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\LicTypes */

if (!isset($static_view)) $static_view=false;
$deleteable=!count($model->licGroups);
?>
<h1>
	<?= $this->render('item',compact('model')) ?>

	<?php if(!$static_view&&$deleteable) echo Html::a('<span class="fas fa-trash"/>', ['delete', 'id' => $model->id], [
		'data' => [
			'confirm' => 'Удалить эту схему лицензирования? Это действие необратимо!',
			'method' => 'post',
		],
	]); else { ?>
		<span class="small">
			<span class="fas fa-lock"	title="Невозможно в данный момент удалить эту схему лицензирования, т.к. присутствуют группы лицензий с этой схемой лицензирования."></span>
		</span>
	<?php } ?>
</h1>


<p>
	<?= TextFieldWidget::widget(['model'=>$model,'field'=>'comment']) ?>
</p>

<br />

<div class="row">
	<div class="col-md-6">
		<h4>Ссылки:</h4>
		<?= UrlListWidget::Widget(['list'=>$model->links]) ?>
	</div>
	<div class="col-md-6">
		<?= $this->render('/attaches/model-list',compact(['model','static_view'])) ?>
	</div>
</div>

<br />

<h4>Группы лицензий:</h4>
<p>
	<?php foreach ($model->licGroups as $licGroup) { ?>
		<?= $licGroup->renderItem($this,['model'=>$licGroup]) ?> <br />
	<?php }	?>
</p>


