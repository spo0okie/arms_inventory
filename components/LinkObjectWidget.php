<?php
namespace app\components;

use app\models\ArmsModel;
use yii\base\Widget;
use yii\helpers\Html;use yii\helpers\Inflector;use yii\helpers\StringHelper;
use yii\helpers\Url;

class LinkObjectWidget extends Widget
{
	
	
	public $updateHint=null;
	public $deleteHint=null;
	public $undeletableMessage=null;
	public $confirmMessage=null;
	public $hideUndeletable=null;
	public $archived=null;
	
	public $static=false;
	public $noDelete=false;
	public $noUpdate=false;
	public $noSpaces=false;	//убирать пробелы перед редактированием и корзиной (в monospace выглядит стремно)
	public $modal=false;

	/**
	 * @var $model ArmsModel
	 */
	public $model=null;		//модель на которую делаем ссылку
	public $links=null;		//имена полей обратных ссылок (кто ссылается на этот объект), чтобы понять можно ли его удалить или нет
	
	public $name=null;		//подмена имени объекта
	
	public $url=null;		//ссылка куда переходить по клику
	public $ttipUrl=null;	//ссылка что показывать в тултипе
	
	public $cssClass=null;
	public $hrefOptions=[];
	
	private $samePage=false;//признак того что элемент отображается на той же странице куда ведет ссылка
	
	public function init()
	{
		parent::init();
		
		$controller=Inflector::camel2id(StringHelper::basename(get_class($this->model)));
		
		if (is_null($this->url)) {
			$this->url=Url::to(['/'.$controller.'/view','id'=>$this->model->id]);
			$this->samePage=(
				(
					\Yii::$app->controller->route==$controller.'/view'
					||
					\Yii::$app->controller->route==$controller.'/ttip'
				)
				&&
				\Yii::$app->request->get('id')==$this->model->id
			);
		} elseif (is_array($this->url)) {
			$this->url=Url::to($this->url);
		}
		
		if (is_null($this->ttipUrl)) {
			$this->ttipUrl=Url::to([$controller.'/ttip','id'=>$this->model->id]);
		}

		$this->samePage=$this->samePage||\Yii::$app->request->url==$this->url;
		
		if (is_null($this->name)) {
			$this->name = $this->model->name;
		}
		
		if (is_null($this->archived)) {
			$this->archived=$this->model->hasAttribute('archived')&&$this->model->getAttribute('archived');
		}
		
		if (is_null($this->cssClass) && $this->archived)
			$this->cssClass='text-reset';
		
		$this->hrefOptions['class']=$this->cssClass;
		$this->hrefOptions['qtip_ajxhrf']=$this->ttipUrl;
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
					'options'=>['cssClass'=>$this->cssClass],
				]);
		} else $deleteObject='';
		
		//если мы уже на этой странице то не делаем ссылки
		return (
				$this->samePage?$this->name
				:
				Html::a($this->name,$this->url,$this->hrefOptions)
			).(
				!$this->static&&!$this->noUpdate?$space.UpdateObjectWidget::widget([
					'model'=>$this->model,
					'updateHint'=>$this->updateHint,
					'modal'=>$this->modal,
					'options'=>['cssClass'=>$this->cssClass],
				]):''
			).$deleteObject;
	}
}