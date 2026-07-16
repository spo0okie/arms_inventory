<?php
/**
 * Карточка изменений одной записи журнала истории (issue #194):
 * дата/время, автор, пояснение и список изменений.
 * Скаляры подаются как «старое → новое», множественные значения
 * (ссылки _ids, JSON, списки строк) - как выбывшие/добавленные.
 */

use app\components\ListObjectsWidget;
use app\components\ModelFieldWidget;
use app\components\widgets\page\ModelWidget;
use app\models\HistoryModel;
use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var HistoryModel $model запись журнала */

$previous=$model->previousRecord;
$isFirst=!is_object($previous);
$changed=$model->changedAttributesList();
$empty='<span class="text-muted">(пусто)</span>';

//элементы множества: ссылки рендерим объектами в состоянии на дату записи $record, прочее - текстом
$renderSetItems=static function(HistoryModel $record,string $attr,array $items) {
	if ($record->attributeIsLink($attr)) {
		$objects=[];
		foreach ($items as $id) {
			//объект не нашёлся ни в оперативной таблице, ни в журнале - показываем хотя бы ID
			$objects[]=is_object($object=$record->fetchLinkOnRecordDate($attr,$id))?$object:'#'.$id;
		}
		$items=$objects;
	}
	return ListObjectsWidget::widget([
		'title'=>false,
		'card'=>false,
		'models'=>$items,
		'glue'=>', ',
		'lineBr'=>false,
		'item_options'=>['static_view'=>true],
	]);
};

?>
<div class="card mb-3">
	<div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
		<span>
			<strong><?= Html::encode($model->updated_at) ?></strong>
			<?php if (is_object($user=$model->getUpdatedByUser())): ?>
				— <?= ModelWidget::widget(['model'=>$user,'options'=>['short'=>true,'static_view'=>true]]) ?>
			<?php elseif ($model->updated_by): ?>
				— <?= Html::encode($model->updated_by) ?>
			<?php endif; ?>
		</span>
		<?php if ($model->isDeletionRecord()): ?>
			<span class="badge bg-danger">Объект удалён</span>
		<?php elseif ($isFirst): ?>
			<span class="badge bg-secondary">Первая запись журнала</span>
		<?php endif; ?>
	</div>
	<?php if (!empty($model->updated_comment)): ?>
		<div class="card-body py-2 fst-italic"><?= Html::encode($model->updated_comment) ?></div>
	<?php endif; ?>
	<?php if (count($changed)): ?>
	<ul class="list-group list-group-flush">
		<?php foreach ($changed as $attr): ?>
		<li class="list-group-item">
			<?= ModelFieldWidget::renderFieldTitle($model->masterInstance,$attr,null,'strong') ?>:
			<?php if (!is_null($typed=$model->attributeTypedDiff($attr))):
				//типовой diff (отпечатки софта/железа): только изменения
				$lines=[];
				foreach ($typed['added'] as $item)
					$lines[]='<span class="text-success">+</span> '.$item;
				foreach ($typed['removed'] as $item)
					$lines[]='<span class="text-danger">−</span> <del class="text-muted">'.$item.'</del>';
				foreach ($typed['changed'] as $item)
					$lines[]='<span class="text-primary">±</span> '.$item;
				echo count($lines)
					?implode('<br/>',$lines)
					:'<span class="text-muted">(изменение только форматирования)</span>';
			?>
			<?php elseif ($model->attributeIsMultiValue($attr)):
				$diff=$model->attributeSetDiff($attr); ?>
				<?php if (count($diff['added'])): ?>
					<span class="text-success">+</span> <?= $renderSetItems($model,$attr,$diff['added']) ?>
				<?php endif; ?>
				<?php if (count($diff['added']) && count($diff['removed'])): ?><br/><?php endif; ?>
				<?php if (count($diff['removed'])): ?>
					<span class="text-danger">−</span> <del class="text-muted"><?= $renderSetItems($previous??$model,$attr,$diff['removed']) ?></del>
				<?php endif; ?>
				<?php if (!count($diff['added']) && !count($diff['removed'])) echo $empty; ?>
			<?php else:
				$new=ModelFieldWidget::renderFieldValue($model,$attr);
				$old=$isFirst?'':ModelFieldWidget::renderFieldValue($previous,$attr); ?>
				<?php if (!$isFirst): ?>
					<span class="text-muted"><?= strlen($old)?$old:$empty ?></span> &rarr;
				<?php endif; ?>
				<?= strlen($new)?$new:$empty ?>
			<?php endif; ?>
		</li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>
</div>
