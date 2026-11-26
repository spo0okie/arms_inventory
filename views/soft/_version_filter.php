<?php

use app\helpers\SoftwareVersionFilter;
use yii\helpers\Html;

/** @var \app\models\Soft $model */
/** @var \app\models\CompsSearch $searchModel */
$formats = Yii::$app->params['soft.version_formats'];
$key = is_string($searchModel->versionFormat) ? $searchModel->versionFormat : '';
$format = $formats[$key] ?? [];
$conditions = $searchModel->versionConditions[$key] ?? [];
$formName = $searchModel->formName();
$route = Yii::$app->request->queryParams;
// Сохраняем остальные фильтры таблицы и возвращаемся на первую страницу.
unset($route['r'], $route['page'], $route[$formName]['versionFormat'], $route[$formName]['versionConditions']);
$route[0] = '/soft/view';
$route['id'] = $model->id;
?>
<?= Html::beginForm($route, 'get', ['class'=>'mb-3']) ?>
	<?= Html::label('Формат версии', 'soft-version-format') ?>
	<?= Html::dropDownList($formName.'[versionFormat]', $key, array_map(static fn($format) => $format['label'], $formats), [
		'id'=>'soft-version-format', 'class'=>'form-select w-auto',
		'prompt'=>'Все форматы', 'onchange'=>'this.form.submit()',
	]) ?>
	<?php foreach ($format['fields'] ?? [] as $field=>[$label]) {
		$prefix = $formName.'[versionConditions]['.$key.']['.$field.']';
		$condition = $conditions[$field] ?? [];
		$id = 'soft-version-'.$field;
	?>
	<div class="d-flex align-items-center gap-2 mt-2">
		<?= Html::label($label, $id) ?>
		<?= Html::dropDownList($prefix.'[op]', is_string($condition['op'] ?? null) ? $condition['op'] : '=', SoftwareVersionFilter::OPERATORS,
			['class'=>'form-select w-auto', 'aria-label'=>'Сравнение: '.$label]) ?>
		<?= Html::textInput($prefix.'[value]', is_string($condition['value'] ?? null) ? $condition['value'] : '',
			['id'=>$id, 'class'=>'form-control w-auto', 'placeholder'=>'Любое значение']) ?>
	</div>
	<?php } ?>
	<?php if ($format) echo Html::submitButton('Применить', ['class'=>'btn btn-primary mt-2']); ?>
	<?= Html::error($searchModel, 'versionFormat', ['class'=>'text-danger']) ?>
<?= Html::endForm() ?>
