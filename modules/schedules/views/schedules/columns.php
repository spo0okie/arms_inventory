<?php

use app\modules\schedules\assets\ScheduleRuntimeAsset;
use app\modules\schedules\compile\SchedulesCompiler;
use app\modules\schedules\models\Schedules;
use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $searchModel app\modules\schedules\models\SchedulesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$renderer=$this;
// JS-рантайм нужен только для колонки 'statusJs'. Регистрируем один раз при рендере view.
ScheduleRuntimeAsset::register($renderer);

return [
	[
		'attribute'=>'name',
		'format'=>'raw',
		'value'=>function($data) {
			return Html::a($data->name,['view','id'=>$data->id]);
		}
	],

	'description',
	'workTimeDescription',

	// Серверная колонка: рассчитывается на каждый запрос по текущему серверному времени.
	'status' => [
		'format' => 'raw',
		'value' => function ($data) {
			if (!is_object($data)) return '';
			$active = (int)$data->status === 1;
			return Html::tag(
				'span',
				$active ? '●' : '○',
				[
					'class' => $active ? 'text-success' : 'text-muted',
					'title' => $active ? 'Активно сейчас' : 'Сейчас не активно',
				]
			);
		},
	],

	// Клиентская колонка: в ячейке — маркер + скрытый JSON с compiled_json;
	// JS-скрипт schedule-runtime-status.js обновляет отображение раз в минуту.
	'statusJs' => [
		'format' => 'raw',
		'value' => function ($data) {
			if (!is_object($data) || !($data instanceof Schedules)) return '';
			$compiled = $data->compiled_json;
			if (empty($compiled)) {
				// Фикстурные/legacy записи без compiled_json — пробуем посчитать на лету;
				// если компилятор упадёт, возвращаем нейтральный плейсхолдер,
				// чтобы не ломать рендер строки grid'а.
				try {
					$compiled = json_encode(SchedulesCompiler::compile($data), JSON_UNESCAPED_UNICODE);
				} catch (\Throwable $e) {
					\Yii::error("Schedules#{$data->id} live compile failed: " . $e->getMessage(), __METHOD__);
					return Html::tag('span', '—', ['class' => 'text-muted', 'title' => 'Расписание не скомпилировано']);
				}
			}
			$nodeId = 'ssrt-' . (int)$data->id;
			return Html::tag('span', '…', [
					'class' => 'schedule-runtime-status text-muted',
					'data-target' => '#' . $nodeId,
				])
				. Html::tag('script', $compiled, [
					'type' => 'application/json',
					'class' => 'ssrt-data',
					'id' => $nodeId,
				]);
		},
	],
];
