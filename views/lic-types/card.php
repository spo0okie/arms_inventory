<?php

use app\components\LinkObjectWidget;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\LicTypes */

if (!isset($static_view)) $static_view=false;
?>
<h1>
	<?= LinkObjectWidget::widget([
		'model' => $model,
		'name' => Html::encode($model->descr),
		'static' => $static_view,
		'hideUndeletable' => false,
		'confirmMessage' => 'Удалить эту схему лицензирования? Это действие необратимо!',
	]) ?>
</h1>


<p>
	<?= \app\components\ModelFieldWidget::renderFieldValue($model,'comment') ?>
</p>

<br />

<div class="row">
	<div class="col-md-6">
		<?= \app\components\ModelFieldWidget::renderFieldTitle($model,'links',tag:'h4',labelOverride:'Ссылки:') ?>
		<?= \app\components\ModelFieldWidget::renderFieldValue($model,'links') ?>
	</div>
	<div class="col-md-6">
		<?= $this->render('/attaches/model-list',compact(['model','static_view'])) ?>
	</div>
</div>

<br />

<?= \app\components\ModelFieldWidget::renderFieldTitle($model,'licGroups',null,'h4','Группы лицензий') ?>
<p>
	<?php foreach ($model->licGroups as $licGroup) { ?>
		<?= $licGroup->renderItem($this,['model'=>$licGroup]) ?> <br />
	<?php }	?>
</p>


