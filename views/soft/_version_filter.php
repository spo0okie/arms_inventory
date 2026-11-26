<?php

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
	<?= $this->render('_version_conditions', [
		'format'=>$format, 'conditions'=>$conditions, 'name'=>$formName.'[versionConditions]['.$key.']',
		'idPrefix'=>'soft-version-',
	]) ?>
	<?php if ($format) echo Html::submitButton('Применить', ['class'=>'btn btn-primary mt-2']); ?>
	<?= Html::error($searchModel, 'versionFormat', ['class'=>'text-danger']) ?>
<?= Html::endForm() ?>
