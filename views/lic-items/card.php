<?php

use app\components\ModelFieldWidget;
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
	<?= $this->render('/lic-groups/item',['model'=>$model->licGroup,'static_view'=>$static_view]) ?>
	<br />
	<h4> Закупка: </h4>
	<?= $this->render('/lic-items/item',['model'=>$model,'static_view'=>$static_view,'name'=>$model->descr,'noDelete'=>false]) ?>

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
	<?= Markdown::convert($model->comment,[]) ?>
</p>
