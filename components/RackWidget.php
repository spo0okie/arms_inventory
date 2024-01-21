<?php
namespace app\components;

use app\components\assets\RackWidgetAsset;
use app\models\Techs;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Class RackWidget
 * Нам нужно нарисовать сетку/таблицу
 * @package app\components
 *
 * @property integer totalWidth
 * @property integer totalHeight
 *
 * @property bool isErrorConfig
 * @property bool isSimpleConfig
 *
 * @property integer simpleCols
 * @property integer simpleRows
 * @property integer simpleLeftOffset
 * @property integer simpleRightOffset
 * @property integer simpleTopOffset
 * @property integer simpleBottomOffset
 * @property float smallestUnitHeight
 * @property Techs model
 */
class RackWidget extends Widget
{
	/*
	 * {
	"cols":[
		{"type":"units","count":1,"size":60},	//колонка с юнитами (размер всей колонки)
		{"type":"void","size":60},				//пустая колонка
		{"type":"units","count":1,"size":60}
	],
	"rows":[
		{"type":"title","size":"12"},			//строка заголовка
		{"type":"units","count":6,"size":120},	//строка юнитов (размер всей строки)
		{"type":"void","size":40},				//пустая строка
		{"type":"units","count":2,"size":60}
	],
	"EnumerationStart:1
	"hEnumeration":"1",		//нумерация по горизонтали - справа налево (таблица рисуется слева направо же)
	"vEnumeration":"1",		//по вертикали сверху вниз - (также как таблица)
	"priorEnumeration":"v",	//сначала по рядам (h), потом по колонкам (v)
	"evenEnumeration":"-1",	//на четных рядах прямая нумерация или обратная (нумеруем змейкой или пилой)
	"labelWidth":"20",		//ширина метки
	"labelMode":"h",		//метки юнита слева
	"labelPre":"1",			//метки юнита справа
	"labelPost":"1"
	}

	 */
	
	public $id='default';
	public $model=null;
	public $title='';
	public $front_rack=true;	//корзина передняя (иначе задняя)
	public $front=true;			//рендер корзины с основной стороны (задняя с обратной стороны это front=false)
	public $cols=[];			//ширины столбцов
	public $rows=[];			//высоты строк
	public $heightRatio=1;
	public $hEnumeration=1;
	public $vEnumeration=1;
	public $priorEnumeration='h';
	public $evenEnumeration=1;
	public $labelMode='h'; //h/v - метки по горизонтали или вертикали размещаем
	public $labelPre=false; //метка перед (слева/сверху)
	public $labelPost=false; ////метка после (справа/снизу)
	public $labelWidth=0;
	public $labelHeight=0;
	public $labelStartId=1;
	
	private $totalWidthCache=null;
	private $totalHeightCache=null;
	private $totalColsCache=null;
	private $totalRowsCache=null;
	private $unitColsCache=null;
	private $unitRowsCache=null;
	private $isSimpleConfigCache=null;
	private $smallestUnitHeightCache=null;
	
	
	public function run()
	{
		if ($this->isErrorConfig) return Html::tag('div','Некорректная конфигурация корзины',[
			'class'=>'alert alert-striped'
		]);
		
		return $this->render('rack/table',[
			'rack'=>$this
		]);
	}
	
	
	public function init() {
		RackWidgetAsset::register($this->view);
	}
	
	/**
	 * Определяет номер юнита в зависимости от его координат в таблице
	 * @param $x
	 * @param $y
	 * @return float|int
	 */
	public function getSectorId($x,$y) {
		if ($this->priorEnumeration=='h') {
			$width=$this->getUnitCols();
			$height=$this->getUnitRows();
			$col=$x;
			$row=$y;
			$hNum=$this->hEnumeration;
			$vNum=$this->vEnumeration;
		} else {
			$width=$this->getUnitRows();
			$height=$this->getUnitCols();
			$col=$y;
			$row=$x;
			$hNum=$this->vEnumeration;
			$vNum=$this->hEnumeration;
		}
		$evenNum=(($row+1) % 2 == 0)?$this->evenEnumeration:1; //изза того что нумерация с нуля - смысл четности обратный
		if ($vNum==-1) $row=$height-1-$row;
		if ($hNum*$evenNum==-1) $col=$width-1-$col;
		return $row*$width+$col+$this->labelStartId;
	}
	
	/**
	 * Возвращает полное количество ячеек по колонкам или строкам
	 * @param $cols
	 * @param $pos
	 * @return array
	 */
	public function getViewportCellsCount($cols) {
		$count=0;
		foreach ($cols as $block)
			$count+=($block['count']??1);		//количество ячеек в блоке
		
		return $count;
	}
	
	/**
	 * Возвращает линейные (одномерные) координаты юнита по в строке/колонке
	 * @param $cols
	 * @param $pos
	 * @return array
	 */
	public function getColItemCoords($cols,$pos) {
		$i=0; //номер позиции
		$begin=0;
		$end=0;
		foreach ($cols as $block) {
			$count=$block['count']??1;		//количество ячеек в блоке
			$size=$block['size']/$count; 	//размер ячеек в блоке
			for ($k=0;$k<$count; $k++) {
				$end+=$size;
				if ($i==$pos) break(2); 	//если мы находимся в искомой строке - выходим из цикла
				$begin+=$size;
				$i++;
			}
		}
		
		return [$begin,$end];
	}
	
	/**
	 * Возвращает наименьшую высоту юнита в корзине
	 * @return float
	 */
	public function getSmallestUnitHeight() {
		if (!is_null($this->smallestUnitHeightCache))
			return $this->smallestUnitHeightCache;
		
		foreach ($this->rows as $block) {
			if (isset($block['count']) && $block['count']) {        //количество ячеек в блоке
				$size = $block['size'] / $block['count'];    //размер ячеек в блоке
				if (is_null($this->smallestUnitHeightCache))
					$this->smallestUnitHeightCache=$size;
				else
					$this->smallestUnitHeightCache=min($this->smallestUnitHeightCache,$size);
			}
		}
		
		return $this->smallestUnitHeightCache;
	}
	
	/**
	 * Считает координаты (Y↓;X→) ячейки в зависимости от его положения в таблице
	 * @param $row integer Строка в которой расположена ячейка (сверху вниз)
	 * @param $col integer Столбец в котором расположена ячейка (слева направо)
	 * @return array
	 */
	public function getCellViewportCoords($row,$col) {
		//вертикальные по положению строки в конфигурации строк
		[$top,$bottom]=$this->getColItemCoords($this->rows,$row);
		//горизонтальные по положению колонки в конфигурации колонок
		[$left,$right]=$this->getColItemCoords($this->cols,$col);
		return [
			$left,$top,$right,$bottom,
			'left'=>$left,
			'right'=>$right,
			'top'=>$top,
			'bottom'=>$bottom
		];
	}
	
	
	/**
	 * Полный размер колонок/строк
	 * @param $cols
	 * @param $labelSize
	 * @return int|mixed
	 */
	private function getTotalSize($cols,$labelSize) {
		$total=0;
		foreach ($cols as $i=>$item) {
			$total += $item['size'];
		}
		return $total;
	}
	
	
	/**
	 * Полная ширина шкафа
	 * @return int|mixed
	 */
	public function getTotalWidth() {
		if (is_null($this->totalWidthCache))
			$this->totalWidthCache=$this->getTotalSize($this->cols,$this->labelMode == 'h'?$this->labelWidth:0);
		
		if (!$this->totalWidthCache) return 101;
		return $this->totalWidthCache;
	}
	
	/**
	 * Полная высота шкафа
	 * @return int|mixed
	 */
	public function getTotalHeight() {
		if (is_null($this->totalHeightCache))
			$this->totalHeightCache=$this->getTotalSize($this->rows,$this->labelMode == 'v'?$this->labelHeight:0);
		
		if (!$this->totalHeightCache) return 101;
		return $this->totalHeightCache;
	}
	
	/**
	 * Процент ширины от полной
	 * @param $width
	 * @return float|int
	 */
	public function getWidthPercent($width) 	{return 100*$width/$this->getTotalWidth();}
	public function getHeightPercent($height)	{return 100*$height/$this->getTotalHeight();}
	
	/**
	 * количество непустых колонок в таблице (юниты + метки)
	 * @param     $cols
	 * @param int $labels
	 * @return float|int
	 */
	private function getTotalCount($cols,$labels=0) {
		$total=0;
		foreach ($cols as $i=>$item) {
			$count=isset($item['count'])?$item['count']*(1+$labels):1;
			$total += $count;
		}
		return $total;
	}
	
	/**
	 * количество меток на юнит
	 * @return int
	 */
	public function getLabelsCount() {
		$count=0;
		if ($this->labelPre) $count++;
		if ($this->labelPost) $count++;
		return $count;
	}
	
	/**
	 * Полное количество непустых колонок в таблице (с учетом меток)
	 * @return float|int
	 */
	public function getTotalCols() {
		if (is_null($this->totalColsCache)){
			$this->totalColsCache=$this->getTotalCount($this->cols,$this->labelMode=='h'?$this->getLabelsCount():0);
		}
		return $this->totalColsCache;
	}
	
	/**
	 * Полное количество непустых строк в таблице (с учетом меток, которых пок не бывает сверху/снизу)
	 * @return float|int
	 */
	public function getTotalRows() {
		if (is_null($this->totalRowsCache))
			$this->totalRowsCache=$this->getTotalCount($this->rows);
		return $this->totalRowsCache;
	}
	
	
	private function getUnitCount($cols) {
		$total=0;
		foreach ($cols as $i=>$item)
			if ($item['type']=='units')
				$total+=$item['count'];
		return $total;
	}
	
	public function getUnitCols() {
		if (is_null($this->unitColsCache))
			$this->unitColsCache=$this->getUnitCount($this->cols);
		return $this->unitColsCache;
	}
	
	public function getUnitRows() {
		if (is_null($this->unitRowsCache))
			$this->unitRowsCache=$this->getUnitCount($this->rows);
		return $this->unitRowsCache;
	}
	
	/**
	 * Возвращает признак того, что столбцы и колонки настроены в тривиальном режиме, когда у нас одна корзина
	 */
	public function getIsErrorConfig() {
		return !count($this->cols) || !count($this->rows);
	}

	
	/**** методы для работы с упрощенной конфигурацией (вырожденной) - когда у нас всего один блок юнитов без промежутков *****/
	
	/**
	 * Проверяет что колонки собраны в одну кучу и нет несколько колонок с разными настройками
	 * @param $cols
	 */
	private function colsIsSimple($cols) {
		$uCount=0;
		$count=0;
		foreach ($cols as $item) {
			if ($item['type']=='units') $uCount++;
			$count++;
		}
		return ($count<4) && ($uCount==1); //если должна быть только одна группа и в целом не больше трех
	}
	
	/**
	 * Возвращает признак того, что столбцы и колонки настроены в тривиальном режиме, когда у нас одна корзина
	 */
	public function getIsSimpleConfig() {
		if (is_null($this->isSimpleConfigCache)) {
			$simple=true;
			$simple=$simple&&$this->colsIsSimple($this->cols);
			$simple=$simple&&$this->colsIsSimple($this->rows);
			if (count($this->rows)) {
				$simple=$simple&&$this->rows[0]['type']=='title';
			}
			$this->isSimpleConfigCache=$simple;
		}
		return $this->isSimpleConfigCache;
	}
	
	/**
	 * Отступ с края для простой конфигурации
	 * @param $cols
	 * @param $pos
	 * @return int|mixed
	 */
	public function getEmptyOffset($cols,$pos){
		if ($pos<0) $pos=count($cols)+$pos;
		$col=$cols[$pos];
		if ($col['type']=='units') return 0;
		return $col['size'];
	}
	
	public function getSimpleLeftOffset() {return $this->getEmptyOffset($this->cols,0);}
	public function getSimpleRightOffset() {return $this->getEmptyOffset($this->cols,-1);}
	public function getSimpleTopOffset() {return $this->getEmptyOffset($this->rows,0);}
	public function getSimpleBottomOffset() {return $this->getEmptyOffset($this->rows,-1);}
	public function getSimpleCols() {return $this->getUnitCount($this->cols);}
	public function getSimpleRows() {return $this->getUnitCount($this->rows);}
}