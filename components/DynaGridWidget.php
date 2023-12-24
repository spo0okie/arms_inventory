<?php
namespace app\components;

use app\components\assets\DynaGridWidgetAsset;
use app\helpers\ArrayHelper;
use app\models\ArmsModel;
use app\models\ui\UiTablesCols;
use kartik\base\Lib;
use kartik\dynagrid\DynaGrid;
use kartik\dynagrid\DynaGridStore;
use kartik\dynagrid\models\DynaGridConfig;
use kartik\dynagrid\Module;
use kartik\grid\GridView;
use NumberFormatter;
use Yii;
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
	public $id;
	public $header;
	public $panel;
	public $createButton;
	public $hintButton;
	public $toolButton;
	public $resizableColumns=true;
	public $showFooter=false;
	public $gridOptions=[];
	public $toggleButtonGrid;
	/**
	 * @var string Адрес страницы которую открыть при сохранении настроек. Нужно указывать когда страничка с виджетом
	 * подгружается асинхронно внутрь родительской странице, о которой ничего не знает. Тогда нужно указывать родительскую
	 * явно через этот параметр
	 */
	public $pageUrl;
	
	
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
	public static function matchColumnString(string $column)
	{
		$matches = [];
		if (!Lib::preg_match('/^([\w.]+)(:(\w*))?(:(.*))?$/u', $column, $matches)) {
			throw new InvalidConfigException(
				"Invalid column configuration for '{$column}'. The column must be specified ".
				"in the format of 'attribute', 'attribute:format' or 'attribute:format: label'."
			);
		}
		
		return $matches;
	}
	
	public static function getColumnKey($column)
	{
		if (!is_array($column)) {
			$matches = self::matchColumnString($column);
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
	
	public static function fetchVisibleColumns($id) {
		$dynaGridStore = new DynaGridStore([
				'id' => $id,
				'moduleId' => Module::MODULE,
				'storage' => DynaGrid::TYPE_DB,
				//'userSpecific' => $this->userSpecific,
				//'dbUpdateNameOnly' => $this->dbUpdateNameOnly,
			]
		);
		$data=$dynaGridStore->fetch('dataAttr');
		return isset($data['keys'])?$data['keys']:null;
	}
	
	public static function columnIsVisible($col,$visibleColumns) {
		if (is_null($visibleColumns)) return true;
		return !(array_search(static::getColumnKey($col),$visibleColumns)===false);
	}
	
	
	public function init()
	{
		parent::init();
		DynaGridWidgetAsset::register($this->view);
		if (is_null($this->visibleColumns))
			$this->visibleColumns=static::fetchVisibleColumns($this->id);
	}
	
	public function run()
	{
		if (is_null($this->id)) {
			$this->id= Yii::$app->controller->id.'-'. Yii::$app->controller->action->id;
		}
		
		if (is_null($this->model)) {
			if (!is_null($this->filterModel)) {
				$this->model=$this->filterModel;
			} elseif (count($models=$this->dataProvider->getModels())) {
				$this->model=reset($models);
			}
			
		}
		
		$columns=$this->prepareColumns($this->columns,$this->defaultOrder);
		
		/*foreach ($columns as $column) {
			if (!static::columnIsVisible($column,$this->visibleColumns)) {
				$attr=$column['attribute'];
				if (!$this->filterModel->$attr) continue;
				if ($this->filterModel->hasAttribute($attr))
					$this->filterModel->setAttribute($attr,null);
				elseif ($this->filterModel->hasProperty($attr))
					$this->filterModel->$attr=null;
			}
		}*/
		
		Yii::$app->getModule('dynagrid')->configView='@app/components/views/dynagrid/config';
		
		if (isset($this->pageUrl)) $this->view->params['grid-settings-action']=$this->pageUrl;
		
		return DynaGrid::widget([
			'storage'=>DynaGrid::TYPE_DB,
			'columns' => $columns,
			'toggleButtonGrid'=>$this->toggleButtonGrid,
			'gridOptions'=>ArrayHelper::recursiveOverride([
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
				'panel'=>$this->panel ?? [
					'type' => GridView::TYPE_DEFAULT,
					'heading' => $this->header,
					'before' => $this->createButton,
				],
				'toolbar' => [
					['content'=>$this->toolButton],
					['content'=>'{dynagrid}'],
					//['content'=>'{dynagridFilter}{dynagridSort}{dynagrid}'],
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
				'resizableColumnsOptions'=>[
					'store'=>new JsExpression('{
						get: function (key,def) {return def},
						set: function (key,val) {persistResizeColumn(key,val)}
					}'),
					'selector'=>'tr th',
					'visibilityWaitTimeout'=>500,
					'debug'=>1,
				],
				'persistResize'=>true,
				'responsive'=>!ArrayHelper::getValue($this->gridOptions,'floatHeader'),
				'showFooter'=>$this->showFooter,
			],$this->gridOptions),
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
		}
		foreach ($columns as $attr=>$data) if (array_search($attr,$defaultOrder)===false) {
			$column=$this->defaultColumn($attr,$columns[$attr]);
			if (count($defaultOrder)) $column['visible']=false;
			if (isset($column['footer'])) $this->showFooter=true;
			$prepared[]=$column;
		}
		return $prepared;
	}
	
	/**
	 * Выдернутый в отдельный кусок кода обработчик сохранения параметров DynaGrid.
	 * Зачем? Затем что Dynagrid виджет обрабатывает сохранение сам и при этом обновляет страницу.
	 * Если мы асинхронно подгружаем в свою страничку другую содержащую Dynagrid, то при сохранении откроется другая.
	 * Если мы при этом подменим URL на свой для обновления странички, то в корневой странице нет обработчика сохранения
	 * и изменения не сохранятся. Чтобы они сохранились, надо вставить вот этот обработчик.
	 * @param string $id идентификатор таблицы
	 * @return boolean
	 * @throws InvalidConfigException
	 */
	public static function handleSave(string $id) {
		$gridConfig=new DynaGridConfig([
			'id'=>$id,
			'moduleId'=>'dynagrid',
		]);
		if (!empty($_POST[$id.'-dynagrid'])
			&&
			$gridConfig->load(Yii::$app->request->post())
			&&
			$gridConfig->validate()
		) {
			$delete = ArrayHelper::getValue($_POST, 'deleteFlag', 0) == 1;
			$store = new DynaGridStore([
				'id' => $id,
				'moduleId' => 'dynagrid',
				'storage' => DynaGrid::TYPE_DB,
				//'userSpecific' => true,
				//'dbUpdateNameOnly' => $this->dbUpdateNameOnly,
			]);
			
			if ($delete) {
				$store->delete();
			} else {
				$store->save([
					'page' => $gridConfig->pageSize,
					'theme' => $gridConfig->theme,
					'keys' => ArrayHelper::explode(',', $_POST['visibleKeys']),
					'filter' => $gridConfig->filterId,
					'sort' => $gridConfig->sortId,
				]);
			}
			return true;
		}
		return false;
	}
}