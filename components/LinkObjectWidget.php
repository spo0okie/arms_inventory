<?php
namespace app\components;

use app\models\ArmsModel;
use app\models\HistoryModel;
use Yii;
use yii\base\Widget;
use yii\helpers\Html;
use app\helpers\StringHelper;
use yii\helpers\Url;

class LinkObjectWidget extends Widget
{
	
	
	public $updateHint=null;
	public $deleteHint=null;
	public $undeletableMessage=null;
	public $confirmMessage=null;
	public $hideUndeletable=null;			//скрывать замочек неудаляемого объекта (null значит скрывать если задано $undeletableMessage)
	public $archived=null;					//явное указание, что объект архивирован
	public $archivedProperty='archived';	//какое свойство объекта означает признак "архивирован"
	
	/**
	 * @var bool Отключить pjax. Есть проблема, когда Grid работает в режиме Pjax и содержит ссылки, то они по умолчанию
	 * тоже открываются в этом pjax блоке. Сценариев где такое нужно по умолчанию я не нашел, и поэтому по умолчанию
	 * отключаю pjax во всех ссылках на элементы.
	 */
	public $noPjax=true;
	
	public $static=false;
	public $noDelete=false;
	public $noUpdate=false;
	public $noSpaces=false;	//убирать пробелы перед редактированием и корзиной (в monospace выглядит стремно)
	public $modal=false;

	/**
	 * @var $model ArmsModel|HistoryModel
	 */
	public $model=null;		//модель на которую делаем ссылку
	public $links=null;		//имена полей обратных ссылок (кто ссылается на этот объект), чтобы понять можно ли его удалить или нет
	
	public $name=null;		//подмена имени объекта
	public $namePrefix='';	//префикс имени объекта
	public $nameSuffix='';	//постфикс имени объекта
	
	public $controller;
	
	public $url;			//ссылка куда переходить по клику
	public $ttipUrl;		//ссылка что показывать в тултипе
	public $deleteUrl;		//как удалять
	public $updateUrl;		//как обновлять
	
	public $cssClass=null;
	public $hrefOptions=[];
	
	
	private $samePage=false;//признак того что элемент отображается на той же странице куда ведет ссылка
	
	public function init()
	{
		parent::init();
		$id=null;
		
		//для объектов класса истории это мастер_ид, для обычных это ид
		if (is_object($this->model)) {
			$id=$this->model->master_id??$this->model->id;
		}
		
		if (!$this->controller && is_object($this->model)) {
			$this->controller= StringHelper::class2Id($this->model->masterClass ?? get_class($this->model));
		}
		
		if (!isset($this->url) && $this->controller && $id) {
			$this->url=Url::to(['/'.$this->controller.'/view','id'=>$id]);
			$this->samePage=(
				(
					Yii::$app->controller->route==$this->controller.'/view'
					||
					Yii::$app->controller->route==$this->controller.'/ttip'
				)
				&&
				Yii::$app->request->get('id')==$id
			);
		} elseif (is_array($this->url)) {
			$this->url=Url::to($this->url);
		}
		
		if (!isset($this->ttipUrl) && $this->controller && $id) {
			$this->ttipUrl=$this->model instanceof HistoryModel?
				Url::to([$this->controller.'/ttip','id'=>$id,'timestamp'=>$this->model->updated_at]):
				Url::to([$this->controller.'/ttip','id'=>$id]);
		}
		
		if (!isset($this->updateUrl) && $this->controller && $id) {
			$this->updateUrl=Url::to([$this->controller.'/update','id'=>$id]);
		}
		
		if (!isset($this->deleteUrl) && $this->controller && $id) {
			$this->deleteUrl=Url::to([$this->controller.'/delete','id'=>$id]);
		}
		
		$this->samePage=$this->samePage || Yii::$app->request->url==$this->url;
		
		if (is_null($this->name) && is_object($this->model)) {
			$this->name = $this->model->name;
		}
		$this->name=$this->namePrefix.
			Html::tag('span',$this->name,['class'=>'item-name'])
			.$this->nameSuffix;
		
		if (!isset($this->archived) && is_object($this->model) && $this->archivedProperty) {
			$archivedProperty = $this->archivedProperty;
			$this->archived = $this->model->hasProperty($archivedProperty) ? $this->model->$archivedProperty : false;
		}
		
		if (is_null($this->cssClass) && $this->archived)
			$this->cssClass='text-reset';
		
		
		$this->hrefOptions['class']=$this->cssClass;
		$this->hrefOptions['qtip_ajxhrf']=$this->ttipUrl;
		if ($this->noPjax) $this->hrefOptions['data']=['pjax'=>0];

	}
	
	public function run()
	{
		$space=$this->noSpaces?'':' ';
		if (!$this->static&&!$this->noDelete) {
			$deleteObject=$space.DeleteObjectWidget::widget([
					'model'=>$this->model,
					'deleteHint'=>$this->deleteHint,
					'undeletableMessage'=>$this->undeletableMessage,
					'confirmMessage'=>$this->confirmMessage,
					'hideUndeletable'=>$this->hideUndeletable,
					'links'=>$this->links,
					'url'=>$this->deleteUrl,
					'options'=>[
						'cssClass'=>$this->cssClass,
						'data'=>$this->noPjax?['pjax'=>0]:[],
					],
				]);
		} else $deleteObject='';
		
		//если мы уже на этой странице, то не делаем ссылки
		return (
				$this->samePage?
				$this->name
				:
				Html::a($this->name,$this->url,$this->hrefOptions)
			).(
				!$this->static&&!$this->noUpdate?
					$space.UpdateObjectWidget::widget([
						'model'=>$this->model,
						'updateHint'=>$this->updateHint,
						'modal'=>$this->modal,
						'url'=>$this->updateUrl,
							'options'=>[
								'cssClass'=>$this->cssClass,
								'data'=>$this->noPjax?['pjax'=>0]:[],
							],
					])
				:''
			).$deleteObject;
	}
}