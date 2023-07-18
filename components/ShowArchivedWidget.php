<?php
namespace app\components;

use app\models\ArmsModel;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\Url;

class ShowArchivedWidget extends UrlParamSwitcherWidget
{
	/*
	 * Такой финт ушами. По умолчанию мы считаем что все архивные элементы на страничке надо рисовать
	 * Но если на страничке рисуется этот виджет - значит архивные по умолчанию спрятаны
	 */
	public static $defaultValue=true;
	public static $itemClass='archived-item';
	
	public static function archivedClass($model,$attr='archived') {
		return ($model->$attr)?static::$itemClass:'';
	}
	
	public static function archivedDisplay($model,$inverse=false,$attr='archived') {
		return ($inverse xor $model->$attr)?'display:none;':'';
	}

	public $state=null;
	public $label='Архивные';
	public $scriptOn=<<<JS
	$('.archived-item').show();
	if (typeof ExpandableCardOversizeCheck === 'function') {
		$('.expandable-card-outer').each(function (index,item){ExpandableCardOversizeCheck(item)});
	}
JS;
	public $scriptOff=<<<JS
	$('.archived-item').hide();
	if (typeof ExpandableCardOversizeCheck === 'function') {
		$('.expandable-card-outer').each(function (index,item){ExpandableCardOversizeCheck(item)});
	}
JS;
	public $param='showArchived';
	public $hintOff='Скрыть архивные объекты';
	public $hintOn='Показать архивные объекты';
	
	public function init() {
		parent::init();
		static::$defaultValue=false;
	}
	
}