<?php

namespace app\modules\schedules\assets;

use yii\web\AssetBundle;

/**
 * Подключает JS-рантайм для работы со скомпилированными расписаниями (`compiled_json`).
 *
 * После регистрации на странице доступны глобальные:
 * - `window.ScheduleRuntime` — класс для работы с compiled_json;
 * - `strToTsm`, `tsmToStr`, `tsmToDateTsm`, `dayOfWeek`, `inBounds`,
 *   `intervalsContains`, `intervalsSubtract`, `intervalsAdd` — утилиты.
 *
 * Пример:
 * ```js
 * const rt = new ScheduleRuntime(compiledJson);
 * rt.isWorkDay('2024-01-15'); // true/false
 * ```
 */
class ScheduleRuntimeAsset extends AssetBundle
{
	public $sourcePath = '@app/modules/schedules/compile/lib/js';
	public $js = [
		'demo.js',
		'schedule-runtime-status.js',
	];
}
