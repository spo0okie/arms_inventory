<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 19.05.2023
 * Time: 16:05
 */

namespace app\components;

use app\helpers\ArrayHelper;
use app\helpers\HtmlHelper;
use app\helpers\StringHelper;
use app\models\ArmsModel;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;

/**
* Class ItemObjectWidget
 * @package app\components
 * @property ArmsModel[] $models
 */
class ListObjectsWidget extends Widget
{
	public $models;				//модели, список которых нам нужен
	public $title;				//заголовок списка
	public $show_archived;		//флаг отображения архивного элемента
	public $item_options=[];	//опции для рендера элемента
	public $card=true;			//завернуть все в разворачиваемую карточку
	public $card_options=['cardClass'=>'mb-3'];	//опции для рендера карточки
	public $archived;			//признак того что весь список состоит из архивных элементов
	public $lineBr=true;		//переносить строку между элементами
	public $glue=' ';			//чем разделять элементы
	public $show_empty=false;	//отображать заголовок если список пуст
	public $message_on_empty='';//отображать сообщение вместо списка, если он пуст
	public $itemViewPath;		//путь для рендера элемента
	public $modelClass;
	public $raw_items=false;	//не конвертировать текстовые итемы в HTML (уже сконверчены)
	public $unique=true;		//показывать только уникальные модели (пропускать повторяющиеся)
	
	private $model;
	private $empty;
	private $emptyGlue=false;
	
	
	public function init(){
		parent::init();
		
		//пустой ли список?
		$this->empty=!count($this->models);
		
		//ищем в списке модель
		foreach ($this->models as $model) {
			if (is_object($model)) $this->model=$model;
			break;
		}
		
		
		//вытаскиваем заголовок из модели
		if (!isset($this->title)) {
			$this->title='$Title_error';
			if (isset($this->model)) {
				$class=get_class($this->model);
				if ($this->model->hasProperty('titles')) {
					$this->title=$class::$titles;
				} elseif ($this->model->hasProperty('title')) {
					$this->title=$class::$title;
				} else $this->title=StringHelper::className($class);
			}
		}
		
		//проверяем не содержит ли список только архивные элементы
		if (!isset($this->archived)) {
			$allArchived=true;
			foreach ($this->models as $model) $allArchived=$allArchived&&$this->isArchived($model);
			$this->archived=$allArchived;
		}
		
		if (!isset($this->card_options['cardClass']))
			$this->card_options['cardClass']='';
		
		$this->card_options['cardClass'].=' '.($this->lineBr?'line-break':'line-nobr');
		
		//если не знаем показывать ли архивные - смотрим по запросу
		if (!isset($this->show_archived)) $this->show_archived=Yii::$app->request->get(
			'showArchived',
			ShowArchivedWidget::$defaultValue
		);
		
		//склеивающий текст "пустой" его не надо скрывать между архивными элементами
		if (!strlen(trim($this->glue))) $this->emptyGlue=true;
	}
	
	private function isArchived($model) {
		if (!is_object($model)) return false;
		return $model->isArchived;
	}
	
	private function glueItem($archived=false){
		if ($this->emptyGlue) return $this->glue;
		return Html::tag(
			'span',
			$this->glue,
			[
				'class'=>$archived?ShowArchivedWidget::$itemClass:'',
				'style'=>HtmlHelper::ArchivedDisplay($archived,$this->show_archived),
			]
		);
	}
	
	public function run()
	{
		//если список пуст, а пустое не показываем
		if ($this->empty && !$this->show_empty) return '';
		
		//список
		$listItems=[];
		//все элементы по порядку
		$models=array_values($this->models);
		//признак того что все модели в архиве
		$allArchived=true;
		for ($i=0; $i<count($models); $i++) {
			$model=$models[$i];
			$allArchived=$allArchived&&$this->isArchived($model);
			if (is_object($model)) {
				$itemUUID=$model->uuid();
				//если мы его уже добавляли, но хотим только уникальные, то пропускаем
				if ($this->unique && isset($listItems[$itemUUID])) continue;
				
				if (isset($this->itemViewPath)) //если у нас явно указан путь рендер файла - используем его
					$listItems[$itemUUID]=$this->render($this->itemViewPath,ArrayHelper::recursiveOverride([
						'model'=>$model,'show_archived'=>$this->show_archived
					],$this->item_options));
				else //если рендер файл не заявлен, используем внутримодельный
					$listItems[$itemUUID]=$model->renderItem($this->view,ArrayHelper::recursiveOverride([
						'show_archived'=>$this->show_archived
					],$this->item_options));
			} else {
				//если мы его уже добавляли, но хотим только уникальные, то пропускаем
				if ($this->unique && isset($listItems[$model])) continue;
				if (!$this->raw_items) {
					$listItems[$model]=Yii::$app->formatter->asText($model);
				} else {
					$listItems[$model]=$model;
				}
			}
			// -- разделитель --
			//если это не последний элемент
			if ($i<count($models)-1) {
				$archived=false;	//по умолчанию архивных нет
				if (!$this->emptyGlue) {	//если разделитель не пустой (и его надо скрывать между скрытыми позициями)
					$archived=true; //то предполагаем что мы между двумя архивными элементами
					if (!$this->isArchived($model)) {	//если слева не архивный
						for ($j=$i+1;$j<count($models);$j++) { //проверяем что там справа
							if (!$this->isArchived($models[$j])) { //если справа есть хоть один не архивный
								$archived=false;
								break;
							}
						}
					}
				}
				$listItems[]=$this->glueItem($archived);
			}
		}
		
		$content=implode('',$listItems);
		if (!$content) $content=$this->message_on_empty;
		
		
		if ($this->title) {
			//если явно не заявлено что вся карточка архивная, то выставляем то что выяснили в процессе
			if (!isset($this->archived)) $this->archived=$allArchived;

			//заголовок
			$content=Html::tag(
				'h4',
				$this->title,
				[
					'class'=>($this->archived && !$this->show_empty)?ShowArchivedWidget::$itemClass:'',
					'style'=>HtmlHelper::ArchivedDisplay($this,$this->show_empty||$this->show_archived),
				]
			).$content;
		}
		
		if (!$this->card) return $content;
		
		//карточка
		$this->card_options['content']=$content;
		return ExpandableCardWidget::widget($this->card_options);
		
		//return "<span class=\"$cssClass\" $display>{$this->link}</span> ";
	}
}