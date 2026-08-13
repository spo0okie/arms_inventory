<?php

/**
 * Колонки грида журнала интеграций (index).
 *
 * @var yii\web\View $this
 * @var app\models\IntegrationsLogSearch $searchModel
 */

use app\helpers\StringHelper;
use yii\helpers\Html;

$renderer = $this;

//бейдж результата: ok - зелёный, error - красный, run - серый (в процессе/прервано)
$resultBadge = static function ($model) {
	$map = ['ok' => 'bg-success', 'error' => 'bg-danger', 'run' => 'bg-secondary'];
	return Html::tag('span', Html::encode($model->result), ['class' => 'badge '.($map[$model->result] ?? 'bg-secondary')]);
};

return [
	'created_at',
	'users_id' => [
		'attribute' => 'users_id',
		'value' => static function ($model) use ($renderer) {
			return is_object($model->user) ? $model->user->renderItem($renderer) : '<span class="text-secondary">—</span>';
		},
		'format' => 'raw',
	],
	'provider' => [
		'attribute' => 'provider',
		'value' => static fn($model) => $model->providerTitle(),
	],
	'action',
	'object' => [
		'label' => 'Объект',
		'value' => static function ($model) {
			$route = $model->objectRoute();
			if (!$route) return '<span class="text-secondary">—</span>';
			return Html::a(Html::encode(StringHelper::className($model->class).' #'.$model->object_id), $route);
		},
		'format' => 'raw',
	],
	'result' => [
		'attribute' => 'result',
		'filter' => ['ok' => 'ok', 'error' => 'error', 'run' => 'run'],
		'value' => $resultBadge,
		'format' => 'raw',
	],
	'ext_login',
	'message',
];
