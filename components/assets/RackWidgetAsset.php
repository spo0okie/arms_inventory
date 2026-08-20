<?php
namespace app\components\assets;

use yii\helpers\Url;
use yii\web\AssetBundle;
use yii\web\View;

/**
* DynaGridWidget asset bundle.
*
*/
class RackWidgetAsset extends AssetBundle
{
	public $sourcePath = __DIR__ . '/RackWidgetAsset';
	public $css=['css/rack.css'];
	//public $cssOptions=['appendTimestamp'=>true];
	public $js=['js/rack-ui.js'];
	public $jsOptions=['position'=>View::POS_END];
	public $publishOptions = [
		'forceCopy' => YII_DEBUG,
	];

	/**
	 * {@inheritdoc}
	 *
	 * rack-ui.js ходит на tech-models/render-rack, а префикс адресов зависит от
	 * схемы публикации ({@see \yii\helpers\Url::appBase()}), поэтому объявляем его
	 * рядом с самим скриптом: конструктор стойки рендерится и в AJAX-ответах,
	 * где основной бандл приложения не подключается.
	 */
	public function registerAssetFiles($view)
	{
		$view->registerJsVar('armsUrlBase',Url::appBase());
		parent::registerAssetFiles($view);
	}
}
