<?php

use app\components\LinkObjectWidget;
use app\components\ModelFieldWidget;
use app\components\TextFieldWidget;
use kartik\markdown\Markdown;

/* @var $this yii\web\View */
/* @var $model app\models\LicItems */
/* @var $linksData \yii\data\ArrayDataProvider */

$renderer = $this;

if (!isset($static_view)) $static_view=false;
if (!isset($keys)) $keys=null;
if (!isset($linksData)) $linksData=null;
?>


<h3>
	<?= $model->licGroup->renderItem($this,['static_view'=>$static_view]) ?>
	<br />
	<h4> Закупка: </h4>
	<?= LinkObjectWidget::widget([
		'model'=>$model,
		'static'=>$static_view,
		'undeletableMessage'=>'Закупка используется'
	]) ?>

</h3>
<?= ModelFieldWidget::widget([
	'model'=>$model,
	'field'=>'contracts',
	'show_empty'=>true,
	'glue'=>'<br>',
	'message_on_empty'=>'<div class="alert-striped text-center w-100 p-2">
			<span class="fas fa-exclamation-triangle"></span>
				ОТСУТСТВУЮТ
			<span class="fas fa-exclamation-triangle"></span>
		</div>'
]) ?>
<br />

<h4>Комментарий:</h4>
<p>
	<?= TextFieldWidget::widget(['model'=>$model,'field'=>'comment']) ?>
</p>
