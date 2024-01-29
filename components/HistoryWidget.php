<?php


namespace app\components;


use app\models\ArmsModel;
use app\models\Users;
use yii\base\Widget;
use yii\helpers\Html;

class HistoryWidget extends Widget
{
	/** @var ArmsModel */
	public $model;
	public $icon='<i class="fas fa-history"></i>';
	
	protected $updated_at;
	protected $updated_by;
	protected $user;
	protected $modelClass;
	protected $historyClass;
	
	public function init()
	{
		$this->modelClass=get_class($this->model);
		$this->historyClass=$this->modelClass.'History';
		
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
		//если у нас есть объект пользователя, то это самый ништяк
		if (isset($this->user)) $tokens[]=$this->render('/users/item',['model'=>$this->user,'static_view'=>true,'short'=>true]);
		//если пользователя нет, но есть логин, то тоже збс
		elseif (isset($this->updated_by)) $tokens[]=$this->updated_by;
		//время изменения
		if (isset($this->updated_at)) $tokens[]=$this->updated_at;
		
		$info=count($tokens)?'Изменено: '.implode(', ',$tokens):'Нет отметки об изменениях';
		
		//если у нас ведется история по этому классу, то оформляем ссылку
		if (class_exists($this->historyClass))
			$info.=' '.Html::a($this->icon,[
				'history/journal',
				'class'=>$this->historyClass,
				'id'=>$this->model->id
			]);
		return $info;
	}
}