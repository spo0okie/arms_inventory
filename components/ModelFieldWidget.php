<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 31.12.2023
 * Time: 15:45
 * Собственно решил я в новый год сделать таки виджет который выводит поле модели.
 * Задача оценить что в поле, объект, строка, массив объектов или массив строк и вывести как надо
 */

namespace app\components;

use app\helpers\ArrayHelper;
use app\models\base\ArmsModel;
use app\types\FloatType;
use app\types\IntegerType;
use app\types\LinkType;
use yii\base\Widget;
use yii\helpers\Html;

/**
* Class ItemObjectWidget
 * @package app\components
 * @property \app\models\base\ArmsModel[] $models
 */
class ModelFieldWidget extends Widget
{
	/**
	 * @var \app\models\base\ArmsModel модель, поле которой нам нужно
	 */
	public $model;
	public $models;
	public $field;				//поле модели, которое нам нужно
	public $title;				//заголовок поля (строка «как есть», БЕЗ иконки «?»; false — без заголовка)
	public $label;				//переопределение ИМЕНИ атрибута в заголовке С сохранением тултипа-иконки «?»
								//(для нестандартных подписей блока: «Участвует в работе сервисов» и т.п.)
	public $title_options=[];	//опции для рендера заголовка
	public $show_archived;		//флаг отображения архивного элемента
	public $item_options=[];	//опции для рендера элемента
	public $card=true;			//завернуть в карточку (false вместе с title=false - режим "только значение")
	public $card_options=['cardClass'=>'mb-3'];	//опции для рендера карточки
	public $archived;			//признак того что весь список состоит из архивных элементов
	public $lineBr=true;		//переносить строку между элементами
	public $glue=' ';			//чем разделять элементы
	public $show_empty=false;	//отображать карточку если список пуст
	public $message_on_empty='';//отображать сообщение вместо списка, если он пуст
	public $itemViewPath;		//путь для рендера элемента
	public $modelClass;			//класс объектов из списка значений поля
	public $raw_items=false;	//не конвертировать текстовые items в HTML (уже сконвертированы)
	
	private $data=[];
	
	/**
	 * Загружает в свой накопитель $data данные из модели
	 * @param \app\models\base\ArmsModel $model
	 * @return void
	 */
	public function loadModelData($model) {
		//значение атрибута рендерит класс его типа; null - рендера нет (объектный путь/пусто)
		$rendered=$this->typeRenderedValue($model);
		if (is_array($rendered)) {
			//список готовых элементов (например, ссылки UrlsType) - разделители расставит ListObjectsWidget
			$this->data=array_merge($this->data,$rendered);
			$this->raw_items=true;
			return;
		}
		if ($rendered!==null) {
			//готовый HTML значения целиком
			$this->data[]=$rendered;
			$this->raw_items=true;
			$this->lineBr=false;	//иначе вставленные в wiki-render элементы будут обрывать строку
			return;
		}

		//объектный путь: ссылки/загрузчики/ref - объекты через renderItem
		//(сюда же пустые значения и модели вне системы типов)
		$field=$this->field;
		if ($model->hasMethod('attributeIsLink') && $model->attributeIsLink($field)) {
			$field=$model->attributeLinkLoader($field);
		}
		
		//вытаскиваем поле в отдельную переменную, чтобы больше не городить такое
		$modelData=ArrayHelper::getValue($model,$field);
		if (is_array($modelData)) {
			$this->data=array_merge($this->data,$modelData);
		} else {
			if (!empty($modelData))
				$this->data[]=$modelData;
		}
		
	}
	
	/**
	 * Значение атрибута в исполнении его типа (AttributeTypeInterface::renderOutput):
	 * string - готовый HTML, array - список готовых элементов.
	 * null - типового рендера не будет, дальше объектный/генерик путь:
	 * ссылка/загрузчик (LinkType) или вычисляемый ref-атрибут (объекты
	 * выводятся только через renderItem), пустое значение (подача пустоты -
	 * show_empty/message_on_empty ListObjectsWidget, а не тип), составной
	 * путь через связь, модель вне системы типов.
	 * Ошибка описания атрибута (тип не резолвится) роняет рендер -
	 * такие ошибки ищутся тестами страниц, виджет их не подавляет.
	 * @param \app\models\base\ArmsModel $model
	 * @return mixed
	 */
	private function typeRenderedValue($model) {
		if (!is_object($this->model) || !$this->model->hasMethod('getAttributeTypeClass'))
			return null;	//модель вне системы типов

		if (str_contains($this->field,'.'))
			return null;	//составной путь через связь: тип принадлежит другой модели, рендерим как есть

		if (isset($this->model->getAttributeData($this->field)['ref']))
			return null;	//вычисляемый объект-ссылка

		$type=$this->model->getAttributeTypeClass($this->field);
		if ($type instanceof LinkType) return null;	//ссылка/загрузчик

		//числовой ноль — значимое значение (счётчики: «Свободно: 0»), поэтому
		//для Integer/Float пустотой считаем только null/'', а не empty()-ноль;
		//для остальных типов пустота (null/''/[]/'0') типом не рендерится
		$value=ArrayHelper::getValue($model,$this->field);
		$isEmpty=($type instanceof IntegerType || $type instanceof FloatType)
			? ($value===null || $value==='')
			: empty($value);
		if ($isEmpty)
			return null;

		return $type->renderOutput($this->view,$model,$this->field);
	}

	/**
	 * Подпись атрибута для карточки. Содержимое тултипа собирает единый
	 * AttributeTooltip (ui-sources.md §0.1, режим view): смысл + источник
	 * значения (наследуемые/вычисляемые, блок 1б) + переходы на встроенную
	 * документацию. Подача единая: label чистый, тултип и pin-поведение
	 * висят на иконке «?» в составе label (AttributeTooltip::icon);
	 * options всегда пустые (оставлены для совместимости вызовов).
	 * @param ArmsModel $model
	 * @param string $field
	 * @param mixed $view не используется (оставлен для совместимости вызовов)
	 * @return array [label, options]
	 */
	public static function fieldTitle($model,$field,$view=null,$labelOverride=null)
	{
		$tooltip=AttributeTooltip::build($model,$field,AttributeTooltip::MODE_VIEW,$labelOverride);
		if (!$tooltip) return [$labelOverride??$model->getAttributeLabel($field),[]];
		return [
			$tooltip['title'].' '.AttributeTooltip::icon($tooltip),
			[]
		];
	}

	public static function renderFieldTitle($model,$field,$view=null,$tag='h4',$labelOverride=null)
	{
		[$title,$options]=static::fieldTitle($model,$field,$view,$labelOverride);
		return Html::tag($tag,$title,$options);
	}

	/**
	 * Заголовок СОСТАВНОГО блока — рендера, собранного из нескольких атрибутов
	 * кастомной логикой (значение одним типом не выразить). Чистый label + иконка
	 * «?», тултип которой перечисляет участвующие атрибуты и их смысл
	 * (AttributeTooltip::buildComposite). Санкционированный способ документировать
	 * композиты (Группа 5 и слитные блоки связей), где вывод значения через
	 * ModelFieldWidget невозможен, но блок должен оставаться самодокументируемым.
	 * @param object   $model
	 * @param string[] $fields участвующие в блоке атрибуты
	 * @param string   $label  подпись блока
	 * @param string   $tag    тег заголовка
	 * @return string
	 */
	public static function renderCompositeTitle($model,array $fields,$label,$tag='h4')
	{
		$tooltip=AttributeTooltip::buildComposite($model,$fields,$label);
		$icon=$tooltip ? ' '.AttributeTooltip::icon($tooltip) : '';
		return Html::tag($tag,$label.$icon);
	}

	/**
	 * Только значение атрибута - без подписи и карточки, для инлайн-мест
	 * свободной вёрстки. Типовая логика рендера (text/urls/списки объектов)
	 * та же, что у полной подачи: атрибут в карточке всегда рендерится
	 * этим виджетом (правило unification.md), прямые вызовы
	 * TextFieldWidget/UrlListWidget из вьюх не пишутся.
	 * @param ArmsModel $model
	 * @param string    $field
	 * @param array     $config переопределения конфига виджета
	 * @return string
	 */
	public static function renderFieldValue($model,$field,$config=[])
	{
		return static::widget(array_merge([
			'model'=>$model,
			'field'=>$field,
			'title'=>false,
			'card'=>false,
		],$config));
	}

	/**
	 * Значение атрибута + иконка «?» подсказки, скрытая до включения help-mode
	 * (AttributeTooltip::icon с onlyHelp=true, класс attr-hint-icon--onlyhelp).
	 * Для мест свободной вёрстки/цепочек (ChainWidget), где подпись не выводится,
	 * но атрибут должен оставаться самодокументируемым: в обычном виде вёрстка
	 * чистая, «?» проступает только в режиме справки. Пустое значение — пустая
	 * строка (иконка без значения не выводится).
	 * @param ArmsModel $model
	 * @param string    $field
	 * @param array     $config переопределения конфига виджета значения
	 * @return string
	 */
	public static function renderFieldValueHinted($model,$field,$config=[])
	{
		$value=static::renderFieldValue($model,$field,$config);
		if (!strlen($value)) return '';
		$icon=AttributeTooltip::icon(
			AttributeTooltip::build($model,$field,AttributeTooltip::MODE_VIEW),
			true
		);
		return $icon==='' ? $value : $value.' '.$icon;
	}

	/**
	 * Компактная строка «подпись: значение» для свободной вёрстки карточек.
	 * Пустое значение даёт пустую строку (подпись без значения не выводится),
	 * поэтому строки удобно собирать через implode('<br />',array_filter([...])).
	 * @param ArmsModel $model
	 * @param string    $field
	 * @param array     $config переопределения конфига виджета значения
	 * @param string    $tag    тег подписи
	 * @param string    $labelOverride переопределение текста подписи (тултип-«?» сохраняется)
	 * @return string
	 */
	public static function renderFieldRow($model,$field,$config=[],$tag='span',$labelOverride=null)
	{
		$value=static::renderFieldValue($model,$field,$config);
		if (!strlen($value)) return '';
		return static::renderFieldTitle($model,$field,null,$tag,$labelOverride).': '.$value;
	}

	/**
	 * Конфиг строки yii\widgets\DetailView: подпись и тултип атрибута
	 * от единого сборщика (см. fieldTitle). Табличная подача DetailView
	 * сохраняется, источник подписи - тот же, что у всех output-атрибутов.
	 * Использование:
	 *   'attributes'=>[ ModelFieldWidget::detailAttribute($model,'code'), ... ]
	 * @param ArmsModel $model
	 * @param string    $attr   атрибут, допустим формат DetailView 'attr:format'
	 * @param array     $config переопределения конфига строки
	 * @return array
	 */
	public static function detailAttribute($model,$attr,$config=[])
	{
		[$label,$options]=static::fieldTitle($model,explode(':',$attr)[0]);
		return array_merge([
			'attribute'=>$attr,
			'label'=>$label,
			'captionOptions'=>$options,
		],$config);
	}
	
	public function init(){
		parent::init();
		
		if (is_array($this->models)) {
			$this->model=reset($this->models);
			foreach ($this->models as $model)
				$this->loadModelData($model);
		} else {
			$this->loadModelData($this->model);
		}
		
		if (!isset($this->title)) {
			[$this->title,$title_options]=static::fieldTitle($this->model,$this->field,$this->view,$this->label);
			$this->title_options=ArrayHelper::recursiveOverride($title_options,$this->title_options);
		}
	}
	
	public function run()
	{
		return ListObjectsWidget::widget([
			'models'=>$this->data,
			'title'=>$this->title,
			'title_options'=>$this->title_options,
			'item_options'=>$this->item_options,
			'card'=>$this->card,
			'card_options'=>$this->card_options,
			'archived'=>$this->archived,
			'lineBr'=>$this->lineBr,
			'glue'=>$this->glue,
			'show_empty'=>$this->show_empty,
			'message_on_empty'=>$this->message_on_empty,
			'itemViewPath'=>$this->itemViewPath,
			'modelClass'=>$this->modelClass,
			'raw_items'=>$this->raw_items,
		]);
	}
}