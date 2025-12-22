<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 19.05.2023
 * Time: 8:05
 */

namespace app\components;

use app\helpers\StringHelper;
use app\models\ArmsModel;
use Yii;
use yii\helpers\Html;


/**
 * Class ItemObjectWidget
 * То же самое что LinkObjectWidget, только еще
 *   - оборачивает в SPAN
 *   - может скрывать архивные элементы
 *   - прикручивает дополнительный класс к архивному элементу
 * @package app\components
 * @property ArmsModel $model
 */
class ItemObjectWidget extends LinkObjectWidget
{
	public $link;				//ссылка на модель (Html::a)
	public $show_archived=null;	//флаг отображения архивного элемента
	public $archived_class='text-muted text-decoration-line-through';	//класс, который добавлять к архивному элементу
	public $item_class=null;	//класс, который добавлять к элементу для обозначения его модели
	public $style='';

	public function run()
	{

		//если прямо не сказано, что за класс элемента - стряпаем сами
		if (is_null($this->item_class) && is_object($this->model)) {
				$this->item_class= StringHelper::class2Id(get_class($this->model)).'-item';
		}

		//к архивному классу кроме оформительской части всегда должен быть добавлен флажок архивного элемента
		//(для переключателя отображения)
		if (!strpos($this->archived_class,'archived-item')) $this->archived_class .= ' archived-item';
		
		//если не знаем показывать ли архивные - смотрим по запросу
		if (is_null($this->show_archived)) $this->show_archived=Yii::$app->request->get(
			'showArchived',
			ShowArchivedWidget::$defaultValue
		);
		
		//если мы не подменили ссылку, то формируем ее
		if (!isset($this->link)) $this->link=parent::run();
		
		if ($this->archived&&!$this->show_archived)
			StringHelper::appendToDelimitedString($this->style,';','display:none');
		
		$cssClass='object-item '. $this->item_class.' '.($this->archived?$this->archived_class:'');
		
		return Html::tag('span',$this->link,[
			'class'=>$cssClass,
			'style'=>$this->style,
		]);
		
	}
}