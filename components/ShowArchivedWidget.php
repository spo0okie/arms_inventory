<?php
namespace app\components;

use Yii;

class ShowArchivedWidget extends UrlParamSwitcherWidget
{
	/*
	 * Такой финт ушами. По умолчанию мы считаем что все архивные элементы на страничке надо рисовать
	 * Но если на страничке рисуется этот виджет - значит архивные по умолчанию спрятаны
	 *
	 * ЛОВУШКА ПОРЯДКА РЕНДЕРА: дефолт переключается в «скрывать» только в момент
	 * рендера этого виджета (init ниже). Тогглер обычно живет в CornerWidget ПЕРВЫМ
	 * элементом ПРАВОЙ колонки — все, что рендерится раньше него (карточка в левой
	 * колонке и т.п.), увидит дефолт «показывать» и нарисует архивные элементы
	 * видимыми-зачеркнутыми вместо скрытых. Блокам-спискам в таких местах НЕЛЬЗЯ
	 * полагаться на этот дефолт — резолвить флаг самим и прокидывать явно:
	 *   $show_archived=(bool)Yii::$app->request->get('showArchived',false);
	 * (образцы: views/aces/list.php, views/acls/list.php; подробно — ui-sources.md §3)
	 */
	public static $defaultValue=true;
	public static $itemClass='archived-item';
	public static $defaultParam='showArchived';
	
	public static function archivedClass($model,$attr='archived') {
		$archived=is_bool($model)?$model:($model->$attr);
		return $archived?static::$itemClass:'';
	}
	
	public static function archivedDisplay($model,$inverse=false,$attr='archived') {
		$archived=is_bool($model)?$model:($model->$attr);
		return ($inverse xor $archived)?'display:none;':'';
	}
	
	public static function isOn(){
		return (bool)Yii::$app->request->get(static::$defaultParam,static::$defaultValue);
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
	//public $param='showArchived';
	public $hintOff='Скрыть архивные объекты';
	public $hintOn='Показать архивные объекты';
	public $param='showArchived';
	
	public function init() {
		parent::init();
		static::$defaultValue=false||$this->state;
	}
	
}