<?php
namespace app\components;

use app\components\assets\DynaGridWidgetAsset;
use app\helpers\ArrayHelper;
use app\models\ArmsModel;
use app\models\ui\UiTablesCols;
use kartik\base\Lib;
use kartik\dynagrid\DynaGrid;
use kartik\dynagrid\DynaGridStore;
use kartik\dynagrid\Module;
use kartik\grid\GridView;
use NumberFormatter;
use yii\base\InvalidConfigException;
use yii\base\Widget;
use yii\data\ActiveDataProvider;
use yii\web\JsExpression;



class GridWidget extends DynaGridWidget
{
	
	
	public function run()
	{
		if (is_null($this->id)) {
			$this->id=\Yii::$app->controller->id.'-'.\Yii::$app->controller->action->id;
		}
		
		if (is_null($this->model)) {
			$this->model=$this->filterModel;
		}
		
		$columns=$this->prepareColumns($this->columns,$this->defaultOrder);
		
		return GridView::widget([
			//'storage'=>DynaGrid::TYPE_DB,
			'columns' => $columns,
			'id'=>$this->id,
			'formatter' => [
				'class' => 'yii\i18n\Formatter',
				'nullDisplay' => '',
				'currencyCode'=>'',
				'decimalSeparator'=>',',
				'numberFormatterSymbols' => [
					NumberFormatter::CURRENCY_SYMBOL => '',
				],
			],
			'panel'=>false,
			'toolbar' => false,
			'condensed' => true,
			'dataProvider' => $this->dataProvider,
			'filterModel' => $this->filterModel,
			'tableOptions' => [
				'class'=>'table-condensed table-striped table-bordered table-hover'.($this->resizableColumns?' table-dynaGrid-noWrap':'')
			],
			'resizableColumns'=>$this->resizableColumns,
			'resizableColumnsOptions'=>['store'=>new JsExpression('{
				get: function (key,def) {return def},
				set: function (key,val) {persistResizeColumn(key,val)}
			}')],
			'persistResize'=>true,
			'showFooter'=>$this->showFooter,
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
		if (UiTablesCols::colWidthsExist($this->id)) {
			//если ширина сохранена - проставляем ее
			if ($width=UiTablesCols::fetchColWidth($this->id,$colId)) {
				$data=ArrayHelper::setTreeDefaultValue($data,['headerOptions','style'],"width:$width%");
			} else {
				//иначе 7%, т.к если совсем ничего не поставить (а в этом месте у нас точно есть сохраненные столбцы)
				//они поделят 100% ширины таблицы меж собой и эта будет с шириной 0
				$data=ArrayHelper::setTreeDefaultValue($data,['headerOptions','style'],"width:7%");
			}
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