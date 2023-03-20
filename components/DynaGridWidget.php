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
	
	public $visibleColumns=null;
	/**
	 * Finds the matches for a string column format
	 *
	 * @param  string  $column
	 *
	 * @return array
	 * @throws InvalidConfigException
	 */
	protected function matchColumnString($column)
	{
		$matches = [];
		if (!Lib::preg_match('/^([\w\.]+)(:(\w*))?(:(.*))?$/u', $column, $matches)) {
			throw new InvalidConfigException(
				"Invalid column configuration for '{$column}'. The column must be specified ".
				"in the format of 'attribute', 'attribute:format' or 'attribute:format: label'."
			);
		}
		
		return $matches;
	}
	
	protected function getColumnKey($column)
	{
		if (!is_array($column)) {
			$matches = $this->matchColumnString($column);
			$columnKey = $matches[1];
		} elseif (!empty($column['attribute'])) {
			$columnKey = $column['attribute'];
		} elseif (!empty($column['label'])) {
			$columnKey = $column['label'];
		} elseif (!empty($column['header'])) {
			$columnKey = $column['header'];
		} elseif (!empty($column['class'])) {
			$columnKey = $column['class'];
		} else {
			$columnKey = null;
		}
		
		return hash('crc32', $columnKey);
	}
	
	
	public function columnIsVisible($col) {
		if (is_null($this->visibleColumns)) return true;
		return !(array_search($this->getColumnKey($col),$this->visibleColumns)===false);
	}
	
	
	public function init()
	{
		parent::init();
		DynaGridWidgetAsset::register($this->view);
		$dynaGridStore = new DynaGridStore(
			[
				'id' => $this->id,
				'moduleId' => Module::MODULE,
				'storage' => DynaGrid::TYPE_DB,
				//'userSpecific' => $this->userSpecific,
				//'dbUpdateNameOnly' => $this->dbUpdateNameOnly,
			]
		);
		$data=$dynaGridStore->fetch('dataAttr');
		$this->visibleColumns=isset($data['keys'])?$data['keys']:null;
	}
	
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
				'formatter' => [
					'class' => 'yii\i18n\Formatter',
					'nullDisplay' => '',
					'currencyCode'=>'',
					'decimalSeparator'=>',',
					'numberFormatterSymbols' => [
						NumberFormatter::CURRENCY_SYMBOL => '',
					],
				],
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