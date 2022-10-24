<?php
namespace app\components;

use app\models\ArmsModel;
use kartik\dynagrid\DynaGrid;
use kartik\grid\GridView;
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
	public $hintButton=null;
	public $toolButton=null;
	
	public $showFooter=false;
	
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
		
		return DynaGrid::widget([
			'storage'=>DynaGrid::TYPE_COOKIE,
			'columns' => $this->prepareColumns($this->columns,$this->defaultOrder),
			'gridOptions'=>[
				'formatter' => ['class' => 'yii\i18n\Formatter','nullDisplay' => ''],
				'panel'=>[
					'type' => GridView::TYPE_DEFAULT,
					'heading' => $this->header,
					'before' => $this->createButton,
				],
				'toolbar' => [
					['content'=>$this->toolButton],
					['content'=>'{dynagridFilter}{dynagridSort}{dynagrid}'],
					['content'=>'{export}'],
					['content'=>$this->hintButton],
				],
				'condensed' => true,
				'dataProvider' => $this->dataProvider,
				'filterModel' => $this->filterModel,
				'tableOptions' => ['class'=>'table-condensed table-striped table-bordered arms_index'],
				'resizableColumns'=>true,
				'persistResize'=>true,
				'showFooter'=>$this->showFooter,
				/*'rowOptions'=>function($data) {
					return [
						'class'=>($data->hasProperty('archived') && $data->archived)?'row-archived':''
					];
					
				}*/
			],
			'options'=>[
				'id'=>'dynaGrid-'.$this->id,
			]
		]);
	}
	
	public function setColumnKeys($columns) {
		foreach ($columns as $attr=>$data) {
			if (is_null($data)||empty($data)) {
				unset($columns[$attr]);
			} elseif (!is_array($data) && is_numeric($attr)) {
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
		
		$data['label']=AttributeHintWidget::widget([
			'model'=>$model,
			'attribute'=>$attribute,
			'label'=>isset($data['label'])?$data['label']:null,
			'hint'=>isset($data['hint'])?$data['hint']:null,
		]);
		$data['encodeLabel']=false;
		
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
			if (isset($column['footer'])) $this->showFooter=true;
			$prepared[]=$column;
		}
		return $prepared;
	}
}