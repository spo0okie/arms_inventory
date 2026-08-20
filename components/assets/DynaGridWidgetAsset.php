<?php
namespace app\components\assets;

use yii\helpers\Url;
use yii\web\AssetBundle;
use yii\web\View;

/**
* DynaGridWidget asset bundle.
*
*/
class DynaGridWidgetAsset extends AssetBundle
{
	public $sourcePath = __DIR__ . '/DynaGridWidgetAsset';
	public $css=['css/table.css'];
	public $js=['js/resize-columns.js'];
	public $jsOptions=['position'=>View::POS_END];
	public $publishOptions = [
		'forceCopy' => YII_DEBUG,
	];

	/**
	 * {@inheritdoc}
	 *
	 * resize-columns.js ходит на ui-tables-cols/set, а префикс адресов зависит от
	 * схемы публикации ({@see \yii\helpers\Url::appBase()}), поэтому объявляем его
	 * рядом с самим скриптом: грид рендерится и в AJAX-ответах, где основной
	 * бандл приложения не подключается.
	 */
	public function registerAssetFiles($view)
	{
		$view->registerJsVar('armsUrlBase',Url::appBase());
		parent::registerAssetFiles($view);
	}
}
