<?php
namespace app\components;

use app\helpers\ArrayHelper;
use app\models\ArmsModel;
use app\models\ui\UiTablesCols;
use kartik\dynagrid\DynaGrid;
use kartik\grid\GridView;
use yii\base\Widget;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\web\JsExpression;



class DynaGridWidget extends Widget
{

public $testStore= <<<JS
	{
    	get: function (key,def) {},
    	set: function (key,val) {
    	    let tokens=key.split('-');
    	    if (!tokens.length) return;
    	    if (tokens[0]!=='kv') return; //must be like kv-2364-dynaGrid-arms-index-place
    	    tokens.splice(0,1); //remove 'kv'
    	    
    	    let userId=tokens[0];
    	    if (userId=='guest') return; //not saving for guests
    	    tokens.splice(0,1); //remove 'userId'
    	    
    	    let column=tokens[tokens.length-1];
    	    tokens.splice(tokens.length-1,1); //remove 'column'
    	    
    	    let table=tokens.join('-');
    	    console.log('SET',table,column,userId,val);
    	    
    	    $.ajax({
    	    	url: "/web/ui-tables-cols/set?table="+table+"&column="+column+"&user="+userId+"&value="+val
    	    })
        //.fail(function () {console.log("Err saving data")});}
	}
JS;
	
	
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
	public $id=null;
	public $header;
	public $createButton;
	public $hintButton=null;
	public $toolButton=null;
	public $resizableColumns=true;
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
		if (is_null($this->id)) {
			$this->id=\Yii::$app->controller->id.'-'.\Yii::$app->controller->action->id;
		}
		
		if (is_null($this->model)) {
			$this->model=$this->filterModel;
		}
		
		return DynaGrid::widget([
			'storage'=>DynaGrid::TYPE_DB,
			'columns' => $this->prepareColumns($this->columns,$this->defaultOrder),
			'gridOptions'=>[
				'id'=>$this->id,
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
				'resizableColumns'=>$this->resizableColumns,
				'resizableColumnsOptions'=>['store'=>new JsExpression($this->testStore)],
				'persistResize'=>true,
				'showFooter'=>$this->showFooter,
				/*'rowOptions'=>function($data) {
					return [
						'class'=>($data->hasProperty('archived') && $data->archived)?'row-archived':''
					];
					
				}*/
			],
			'options'=>[
				'id'=>$this->id,
				'resizable-columns-id'=>$this->id,
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
		
		//custom class if not set
		$data=ArrayHelper::setTreeDefaultValue($data,['contentOptions','class'],$attr.'_col');
		
		$colId=str_replace('-','_',$data['attribute']);
		
		//column id for resizable-column
		$data=ArrayHelper::setTreeDefaultValue($data,['headerOptions','data-resizable-column-id'],$colId);
		
		//fetching saved column width
		if ($width=UiTablesCols::fetchColWidth($this->id,$colId)) {
			$data=ArrayHelper::setTreeDefaultValue($data,['headerOptions','style'],"width:$width%");
		}
		
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