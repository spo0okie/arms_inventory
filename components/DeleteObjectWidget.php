<?php
namespace app\components;

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
	public $undeletable='Невозможно удалить этот объект,<br/>т.к. имеются другие объекты привязанные к нему';
	
	public $confirm='Удалить объект?<br/>>Операция необратима';
	public $links=[];
	/**
	 * @var $model ActiveRecord
	 */
	public $model=null;
	
	
	public $defaultObjectType='Прочее';
	
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
				}
				
				if (is_numeric($type)) $type=$this->defaultObjectType;
				if ($count) {
					if (! isset($types[$type])) $types[$type]=0;
					$types[$type]+=$count;
					$totalLinks+=$count;
				}
			}
		}

		if (count($types)) {
			$this->undeletable.=':<ul>';
			foreach ($types as $type=>$count) {
				$this->undeletable.='<li>'.$type.': '.$count.'</li>';
			}
			$this->undeletable.='</ul>';
		}
		
	
		return $totalLinks?
			'<span class="small text-muted" qtip_ttip="'.$this->undeletable.'" disabled>
				<span class="fas fa-lock"></span>
			</span>'
			:
			Html::a('<span class="fas fa-trash"></span>', [
				Inflector::camel2id(
					StringHelper::basename(
						get_class($this->model)
					)
				).'/delete', 'id' => $this->model->id
			], [
				'data' => [
					'confirm' => $this->confirm,
					'method' => 'post',
				]
			]);
	}
}