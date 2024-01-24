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
use yii\data\ActiveDataProvider;
use yii\helpers\Inflector;
use yii\web\JsExpression;


class DynaGridWidget extends DynaGrid
{

	public $storage=DynaGrid::TYPE_DB;
	
	/**
	 * Колонки для вывода
	 * @var array
     */
	public $columns;
	public $defaultOrder=[];
	//public $id;
	public $header;
	public $panel;
	public $createButton;
	public $hintButton;
	public $toolButton;
	public $resizableColumns=true;
	public $showFooter=false;
	public $gridOptions=[];
	public $options=[];
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
	public $filterModel;
	public $model;
	
	public $visibleColumns;
	
	
	protected static $_icons = [
		'iconVisibleColumn' => ['eye-open', 'eye'],
		'iconHiddenColumn' => ['eye-close', 'eye-slash'],
		'iconSortableSeparator' => ['resize-horizontal', 'arrows-alt-h'],
		'iconPersonalize' => ['wrench', 'wrench fas-fw'],
		'iconFilter' => ['filter', 'filter fa-fw'],
		'iconSort' => ['sort', 'sort fa-fw'],
		'iconConfirm' => ['ok', 'check'],
		'iconRemove' => ['remove', 'times'],
	];
	
	
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
	
	
	protected function initWidget()
	{
		parent::initWidget();
		if (is_null($this->visibleColumns))
			$this->visibleColumns=static::fetchVisibleColumns($this->id);
		DynaGridWidgetAsset::register($this->view);
	}
	
	public function run()
	{
		//формируем ID по умолчанию
		if (!isset($this->id)) {
			$this->id= Yii::$app->controller->id.'-'. Yii::$app->controller->action->id;
		}
		
		//если явной модели у нас нет, то вытаскиваем ее сами
		if (!isset($this->model)) {
			if (!is_null($this->filterModel)) {
				$this->model=$this->filterModel;
			} elseif (count($models=$this->dataProvider->getModels())) {
				$this->model=reset($models);
			}
			
		}
		
		//переопределяем рендер конфига на наш кастомный в котором можно передать кастомный путь для формы
		Yii::$app->getModule('dynagrid')->configView='@app/components/views/dynagrid/config';
		//устанавливаем такой путь если надо
		if (isset($this->pageUrl)) $this->view->params['grid-settings-action']=$this->pageUrl;
		
		$this->gridOptions=ArrayHelper::recursiveOverride([
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
		],$this->gridOptions);
		
		$this->options=ArrayHelper::recursiveOverride([
			'id'=>$this->id,
			'resizable-columns-id'=>$this->id,
		],$this->options);
		
		return parent::run();
	}
	
	/**
	 * Проставляет ключи там где колонка определена без ключа
	 * @param $columns
	 * @return array
	 */
	public function setColumnKeys($columns) {
		foreach ($columns as $attr=>$data) {
			if (is_null($data)||empty($data)) {	//если определение колонки пустое - выкидываем ее вообще
				unset($columns[$attr]);
			} elseif (!is_array($data) && is_numeric($attr)) {//если оно определено как просто значение массива без именованного индекса
				unset($columns[$attr]);		//разопределяем старое
				$columns[$data]=[];			//определяем новое в виде 'attr'=>[]
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
	
	
	public function prepareColumns() {
		//порядок колонок по умолчанию
		$defaultOrder=$this->defaultOrder;
		
		//переопределяем вводный массив, если колонка задана как просто значение массива с именем атрибута
		$columns=$this->setColumnKeys($this->columns);
		
		$prepared=[];
		//кладем видимые колонки
		foreach ($defaultOrder as $attr) {
			if (isset($columns[$attr])) $prepared[]=$this->defaultColumn($attr,$columns[$attr]);
		}
		
		//кладем остальные
		foreach ($columns as $attr=>$data) if (array_search($attr,$defaultOrder)===false) {
			$column=$this->defaultColumn($attr,$columns[$attr]);
			if (count($defaultOrder)) $column['visible']=false;
			if (isset($column['footer'])) $this->showFooter=true;
			$prepared[]=$column;
		}
		
		$this->columns=$prepared;
		parent::prepareColumns();
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
	
	/**
	 * Fetches the column label
	 *
	 * @param  mixed  $key  the column key
	 * @param  mixed  $column  the column object / configuration
	 *
	 * @return string
	 * @throws InvalidConfigException
	 */
	protected function getColumnLabel($key, $column)
	{
		if (is_string($column)) {
			$matches = $this->matchColumnString($column);
			$attribute = $matches[1];
			if (isset($matches[5])) {
				return $matches[5];
			} //header specified is in the format "attribute:format:label"
			
			return $this->getAttributeLabel($attribute);
		} else {
			$label = $key;
			if (is_array($column)) {
				if (!empty($column['label'])) {
					$label = $column['label'];
				} elseif (!empty($column['header'])) {
					$label = $column['header'];
				} elseif (!empty($column['attribute'])) {
					$label = $this->getAttributeLabel($column['attribute']);
				} elseif (!empty($column['class'])) {
					$class = Lib::explode('\\', $column['class']);
					$label = Inflector::camel2words(end($class));
				}
			}
			
			return Lib::trim($label);
		}
	}
}