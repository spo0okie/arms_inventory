<?php

use app\controllers\SoftController;
use yii\helpers\Html;

$formats = Yii::$app->params['soft.version_formats'];
$canEdit = match (SoftController::customizeAccessPermission('edit')) {
	SoftController::PERM_EVERYONE => true,
	SoftController::PERM_AUTHENTICATED => !Yii::$app->user->isGuest,
	default => Yii::$app->user->can('edit'),
};
?>
<details class="mb-3">
	<summary>Правила тегирования (<?= count($versionRules) ?>)</summary>
	<ul>
	<?php foreach ($versionRules as $rule):
		$format = $formats[$rule->format] ?? [];
		$parts = [$format['label'] ?? $rule->format];
		if ($error = $rule->conditionError()) $parts[] = $error;
		else foreach ($rule->getConditionsArray() as $field=>$condition) {
			if (trim($condition['value']) !== '') $parts[] = $format['fields'][$field][0].' '.$condition['op'].' '.$condition['value'];
		}
	?>
		<li><?= Html::encode(($rule->tag->name ?? 'Удалённый тег').' — '.implode(', ', $parts)) ?>
		<?php if ($canEdit): ?>
			<?= Html::a('Изменить',['version-rule','id'=>$model->id,'ruleId'=>$rule->id]) ?>
			<?= Html::a('Удалить',['delete-version-rule','id'=>$model->id,'ruleId'=>$rule->id],[
				'data-method'=>'post','data-confirm'=>'Удалить правило тегирования?',
			]) ?>
		<?php endif ?>
		</li>
	<?php endforeach ?>
	</ul>
	<?php if ($canEdit) echo Html::a('Добавить правило',['version-rule','id'=>$model->id],['class'=>'btn btn-outline-primary']); ?>
</details>
