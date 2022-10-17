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
	public $modal=false;

	/**
	 * @var $model ArmsModel
	 */
	public $model=null;
	public $links=null;
	
	public $name=null;
	
	public $url=null;
	public $ttipUrl=null;
	
	public $class=null;
	
	private $samePage;
	
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
		}

		if (is_null($this->ttipUrl)) {
			$this->ttipUrl=Url::to([$controller.'/ttip','id'=>$this->model->id]);
		} else {
			$this->samePage=\Yii::$app->request->url==$this->url;
		}
		
		if (is_null($this->name)) {
			$this->name = $this->model->name;
		}
		
		if (is_null($this->archived)) {
			$this->archived=$this->model->hasAttribute('archived')&&$this->model->getAttribute('archived');
		}
		
		if (is_null($this->class) && $this->archived)
			$this->class='text-reset';
	}
	
	public function run()
	{
		//если мы уже на этой странице то не делаем ссылки
		return (
				$this->samePage?$this->name
				:
				Html::a($this->name,$this->url,['qtip_ajxhrf'=>$this->ttipUrl,'class'=>$this->class])
			).(
				!$this->static?' '.UpdateObjectWidget::widget([
					'model'=>$this->model,
					'updateHint'=>$this->updateHint,
					'modal'=>$this->modal,
					'options'=>['class'=>$this->class],
				]):''
			).(
				!$this->static&&!$this->noDelete?' '.DeleteObjectWidget::widget([
					'model'=>$this->model,
					'deleteHint'=>$this->deleteHint,
					'undeletableMessage'=>$this->undeletableMessage,
					'confirmMessage'=>$this->confirmMessage,
					'hideUndeletable'=>$this->hideUndeletable,
					'links'=>$this->links,
					'options'=>['class'=>$this->class],
				]):''
			);
	}
}