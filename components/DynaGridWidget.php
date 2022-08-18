<?php
namespace app\components;

use app\models\ArmsModel;
use yii\base\Widget;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Inflector;

class DynaGridWidget extends Widget
{
	

	/**
	 * Колонки для вывода
	 * @var array
     */
	public $columns=null;
	public $defaultOrder=[];
	
	/**
	 * Кнопка добавления нового элемента
	 * @var string
	 */
	public $id;
	public $header;
	public $createButton;
	public $hintButton;
	
	/**
	 * данные
	 * @var ActiveDataProvider
	 */
	public $dataProvider;
	
	/**
	 * фильтр
	 * @var ArmsModel
	 */
	public $filterModel=null;
	public $model=null;
	
	public function run()
	{
		if (is_null($this->model)) {
			$this->model=$this->filterModel;
		}
		
		return $this->render('dynaGrid/table', [
			'id'			=> $this->id,
			'header'		=> $this->header,
			'createButton'	=> $this->createButton,
			'hintButton'	=> $this->hintButton,
			'columns'		=> $this->prepareColumns($this->columns,$this->defaultOrder),
			'dataProvider'	=> $this->dataProvider,
			'filterModel'	=> $this->filterModel,
		]);
	}
	
	public function setColumnKeys($columns) {
		foreach ($columns as $attr=>$data) {
			if (!is_array($data) && is_numeric($attr)) {
				unset($columns[$attr]);
				$columns[$data]=[];
			}
		}
		return $columns;
	}
	
	public function defaultColumn($attr,$data=[]) {
		if (!isset($data['attribute']))
			$data['attribute']=$attr;
		
		$attribute=isset($data['modelAttribute'])?
			$data['modelAttribute']:
			$data['attribute'];
		
		
		if (!isset($data['format']))
			$data['format']='raw';
		
		//параметры ячейки
		if (!isset($data['contentOptions']))
			$data['contentOptions']=[];
		//custom class
		if (is_array($data['contentOptions']) && !isset($data['contentOptions']['class']))
			$data['contentOptions']['class']=$attr.'_col';
		
		
		$model=isset($data['model'])?
			$data['model']:$this->model;
		//если наш объект поддерживает getAttributeIndexLabel, а наименования столбца нет
		if (
			!isset($data['label'])
		) {
			if (is_object($model)) {
				$data['label']=method_exists($model,'getAttributeIndexLabel')
				?
				$model->getAttributeIndexLabel($attribute)
				:
				$model->getAttributeLabel($attribute);
			} else {
				$data['label']=Inflector::camel2words($attr, true);
			}
		}
		
		$data['label']=Html::encode($data['label']);
		$data['encodeLabel']=false;
		
		if (
			!isset($data['hint']) &&
			is_object($model) &&
			method_exists($model,'getAttributeIndexHint')
		) {
			$data['hint']=$model->getAttributeIndexHint($attribute);
		}

		if (isset($data['hint'])) {
			$fieldFullName=
				is_object($model)
					?
				$model->getAttributeLabel($attribute)
					:
				$data['label'];
			
			$data['label']=\yii\helpers\Html::tag(
				'span',
				$data['label'],
				\app\helpers\FieldsHelper::toolTipOptions($fieldFullName,$data['hint'])
			);
		}
		unset($data['hint']);
		unset($data['model']);
		unset($data['modelAttribute']);

		return $data;
	}
	
	
	public function prepareColumns($columns,$defaultOrder=[]) {
		if (is_null($defaultOrder))
			$defaultOrder=[];
		$prepared=[];
		$columns=static::setColumnKeys($columns);
		foreach ($defaultOrder as $attr) {
			if (isset($columns[$attr])) $prepared[]=$this->defaultColumn($attr,$columns[$attr]);
		};
		foreach ($columns as $attr=>$data) if (array_search($attr,$defaultOrder)===false) {
			$column=$this->defaultColumn($attr,$columns[$attr]);
			if (count($defaultOrder)) $column['visible']=false;
			$prepared[]=$column;
		}
		return $prepared;
	}
}