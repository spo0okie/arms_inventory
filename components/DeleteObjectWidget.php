<?php
namespace app\components;

use app\models\ArmsModel;
use yii\base\Widget;
use yii\db\ActiveRecord;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

class DeleteObjectWidget extends Widget
{
	

	/**
	 * Сообщение по умолчанию, если объекты не переданы
	 * @var string
     */
	public $deleteHint=null;
	public $undeletableMessage=null;
	public $confirmMessage=null;
	public $links=null;
	
	/**
	 * @var $model ArmsModel
	 */
	public $model=null;
	public $hideUndeletable=null;
	public $options=[];
	
	public $url=null;
	
	
	public $defaultObjectType='Прочее';
	
	public function init()
	{
		parent::init();
		$modelClass=get_class($this->model);
		$modelTitle=($this->model->hasProperty('title'))?
			$modelTitle=$modelClass::$title:'объект';
		
		if (is_null($this->deleteHint)) $this->deleteHint='Удалить';
		
		if (is_null($this->hideUndeletable)) {
			$this->hideUndeletable=is_null($this->undeletableMessage);
		}
		
		if (is_null($this->undeletableMessage)) {
			$this->undeletableMessage='Невозможно удалить этот '.$modelTitle.','.
				'<br/>т.к. имеются другие объекты привязанные к нему';
		}
		
		if (is_null($this->confirmMessage)) {
			$this->confirmMessage='Удалить '.$modelTitle.'? (Операция необратима!)';
		}
		
		if (is_null($this->links)) {
			$this->links=$this->model->hasMethod('reverseLinks')?
				$this->model->reverseLinks():[];
		}
		
		$this->options['data'] = [
			'confirm' => $this->confirmMessage,
			'method' => 'post',
		];
		$this->options['qtip_ttip']=$this->deleteHint;
		$this->options['qtip_side']='bottom,top,right,left';
		
		if (is_null($this->url)) {
			$this->url=[
				'/'.Inflector::camel2id(
					StringHelper::basename(
						get_class($this->model)
					)
				).'/delete', 'id' => $this->model->id
			];
		}
	}
	
	public function run()
	{
		$types=[];
		$totalLinks=0;
		if (count($this->links)) {
			foreach ($this->links as $type=>$count) {
				
				//передали пачку объектов (в противном случае это должно быть число)
				if (is_array($count)) {
					$first=reset($count);
					//если не сделали ключ для наименования объекта - вытаскиваем его из первого объекта
					if (is_numeric($type) && is_object($first) && is_subclass_of($first,ActiveRecord::class)) {
						/**
						 * @var $first ActiveRecord
						 */
						$class=get_class($first);
						if ($first->hasProperty('titles')) $type=$class::$titles;
						elseif ($first->hasProperty('title')) $type=$class::$title;
					}
					$count=count($count);
				} else $count=1;
				
				if (is_numeric($type)) $type=$this->defaultObjectType;
				if ($count) {
					if (! isset($types[$type])) $types[$type]=0;
					$types[$type]+=$count;
					$totalLinks+=$count;
				}
			}
		}

		if (count($types)) {
			$this->undeletableMessage.=':<ul>';
			foreach ($types as $type=>$count) {
				$this->undeletableMessage.='<li>'.$type.': '.$count.'</li>';
			}
			$this->undeletableMessage.='</ul>';
		}
		
	
		return $totalLinks?
			(
				$this->hideUndeletable?'':
				'<span class="small text-muted" qtip_ttip="'.$this->undeletableMessage.'" disabled>
					<span class="fas fa-lock"></span>
				</span>'
			)
			:
			Html::a('<span class="fas fa-trash delete-item-button"></span>', $this->url, $this->options);
	}
}