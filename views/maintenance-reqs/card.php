<?php

use app\components\IsHistoryObjectWidget;
use app\components\ModelFieldWidget;
use app\components\LinkObjectWidget;


/* @var $this yii\web\View */
/* @var $model app\models\MaintenanceReqs */

$deleteable=true; //тут переопределить возможность удаления элемента
if (!isset($static_view)) $static_view=false;

$spread=[];
if ($model->spread_comps) $spread[]='<b>компьютеры</b>';
if ($model->spread_techs) $spread[]='<b>оборудование</b>';

?>

<?= IsHistoryObjectWidget::widget(['model'=>$model]) ?>
<h1>
	<?=  LinkObjectWidget::widget([
		'model'=>$model,
		'hideUndeletable'=>false,
		//'confirmMessage' => 'Действительно удалить этот документ?',
		'undeletableMessage'=>'Нельзя удалить эти требования к обслуживанию, т.к. есть привязанные объекты',
	]) ?>
</h1>

<div class="mb-3">
	<?= Yii::$app->formatter->asNtext($model->description) ?>
	<?php if (count($spread)) { ?>
		<p>
			Закрепленные за сервисами требования распространяются также на <?= implode(' и ', $spread)?> обеспечивающих работу сервисов.
		</p>
	<?php } ?>
</div>
<?php

//echo ModelFieldWidget::widget(['model'=>$model,'field'=>'spread_comps']);
//echo ModelFieldWidget::widget(['model'=>$model,'field'=>'spread_techs']);
echo ModelFieldWidget::widget(['model'=>$model,'field'=>'services']);
echo ModelFieldWidget::widget(['model'=>$model,'field'=>'includes']);
echo ModelFieldWidget::widget(['model'=>$model,'field'=>'includedBy']);
echo ModelFieldWidget::widget(['model'=>$model,'field'=>'links']);
//echo ModelFieldWidget::widget(['model'=>$model,'field'=>'updated_at']);
//echo ModelFieldWidget::widget(['model'=>$model,'field'=>'updated_by']);

