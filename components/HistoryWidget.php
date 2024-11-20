<?php


namespace app\components;


use app\models\ArmsModel;
use app\models\HistoryModel;
use app\models\Users;
use yii\base\Widget;
use yii\helpers\Html;

class HistoryWidget extends Widget
{
	/** @var ArmsModel */
	public $model;
	public $icon='<i class="fas fa-history"></i>';
	public $prefix='Изменено: ';
	public $empty='Нет отметки об изменениях';
	public $showIcon=true;
	public $showUser=true;
	public $showDate=true;
	public $showTime=true;
	public $iconOptions=[];
	
	protected $updated_at;
	protected $updated_by;
	protected $user;
	protected $modelClass;
	protected $historyClass;
	protected $calledOnHistory;	//признак того что виджет вызвали на объекте класса журнала
	
	public function init()
	{
		if ($this->model instanceof HistoryModel) {
			$this->calledOnHistory=true;
		} else {
			$this->calledOnHistory=false;
			$this->modelClass=get_class($this->model);
			$this->historyClass=$this->modelClass.'History';
		}
		
		if ($this->model->hasAttribute('updated_at'))
			$this->updated_at=$this->model->updated_at;

		if ($this->model->hasAttribute('updated_by')) {
			$this->updated_by=$this->model->updated_by;
			if ($this->updated_by) $this->user=Users::find()
				->where(['Login'=>$this->updated_by])
				->one();
		}
	}
	
	public function run() {
		$tokens=[];
		if ($this->showUser) {
			//если у нас есть объект пользователя, то это самый ништяк
			if (isset($this->user)) $tokens[] = $this->render('/users/item', ['model' => $this->user, 'static_view' => true, 'short' => true]);
			//если пользователя нет, но есть логин, то тоже збс
			elseif (isset($this->updated_by)) $tokens[]=$this->updated_by;
		}

		if ($this->showDate && isset($this->updated_at)) {
			//время изменения
			if ($this->showTime) {
				$tokens[]=$this->updated_at;	//если время показываем то все отображаем
			} else {
				$tokens[]=explode(' ',$this->updated_at)[0]; //если не показываем, то только дату
			}
		}
		//если у нас ведется история по этому классу, то оформляем ссылку
		if ($this->showIcon && !$this->calledOnHistory && class_exists($this->historyClass)) {
			if (!count($tokens)) $tokens=[''];
			$tokens[count($tokens)-1].=' '.Html::a($this->icon,[
					'history/journal',
					'class'=>$this->historyClass,
					'id'=>$this->model->id
				],$this->iconOptions);
		}
		
		foreach ($tokens as $i=>$token) $tokens[$i]='<span class="text-nowrap">'.$token.'</span>';
		
		return count($tokens)?$this->prefix.implode(' ',$tokens):$this->empty;
	}
}