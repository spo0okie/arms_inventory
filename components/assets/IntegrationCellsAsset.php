<?php
namespace app\components\assets;

use yii\web\AssetBundle;

/**
 * Скрипт батч-наполнения ячеек интеграций в гридах
 * (docs/dev/integrations.md §5 «Колонки в списках», {@see \app\components\integrations\CellColumn}).
 * Зависит от YiiAsset: yii.js вешает CSRF-заголовок на ajax-POST.
 *
 * Позиция НЕ задаётся (по умолчанию и так POS_END): явная позиция у
 * бандла с depends пропагируется на YiiAsset и конфликтует с виджетами,
 * требующими его в POS_HEAD (select2-фильтры грида) —
 * InvalidConfigException «higher javascript file position».
 */
class IntegrationCellsAsset extends AssetBundle
{
	public $sourcePath = __DIR__ . '/IntegrationCellsAsset';
	public $js = ['js/integration-cells.js'];
	public $depends = ['yii\web\YiiAsset'];
	public $publishOptions = [
		'forceCopy' => YII_DEBUG,
	];
}
