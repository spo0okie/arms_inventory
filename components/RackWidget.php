<?php
namespace app\components;

use app\components\assets\RackWidgetAsset;
use yii\base\Widget;

/**
 * Class RackWidget
 * Нам нужно нарисовать сетку/таблицу
 * @package app\components
 */
class RackWidget extends Widget
{
	/*
	 * {
	 * 		"cols":[{"1":"30"},{"0":"40"},{"1":"30"}], //колонки: 1 занимает 30%, потом 0(пусто) 40%, потом еще 1 30%
	 * 		"rows":[{"6":"30"},{"0":"40"},{"2":"30"}], //строки: 6рядов на 30%, потом пустота 40%, потом 2 ряда на 30%
	 * 		"heightRatio":"3.4" //отношение высоты к ширине (42U шкаф высотой 2040, шириной 60)
	 * 		"hEnumeration":"-1",		//нумерация по горизонтали - справа налево (таблица рисуется слева направо же)
	 *		"vEnumeration":"1",			//по вертикали сверху вниз - (также как таблица)
	 *		"priorEnumeration":"h",		//сначала по рядам (h), потом по колонкам (v)
	 *		"evenEnumeration":"1"		//на четных рядах прямая нумерация или обратная (нумеруем змейкой или пилой)
	 * 		"labelPre":"1"				//метки юнита слева
	 * 		"labelPost":"1"				//метки юнита справа
	 * 		"labelWidth":"5"			//ширина метки
	 * }
	 */
	
	public $id='default';
	public $title='';
	public $front=true;
	public $cols=[];			//ширины столбцов
	public $rows=[];			//высоты строк
	public $heightRatio=1;
	public $hEnumeration=1;
	public $vEnumeration=1;
	public $priorEnumeration='h';
	public $evenEnumeration=1;
	private $totalWidthCache=null;
	private $totalHeightCache=null;
	private $totalColsCache=null;
	private $totalRowsCache=null;
	private $unitColsCache=null;
	private $unitRowsCache=null;
	public $labelMode='h'; //h/v - метки по горизонтали или вертикали размещаем
	public $labelPre=false; //метка перед (слева/сверху)
	public $labelPost=false; ////метка после (справа/снизу)
	public $labelWidth=0;
	public $labelHeight=0;
	
	private function initRowsArray($param) {
		$total="${param}Total";
		$use="${param}Useful";
		
		if (is_array($this->objParams[$param])) {
			$this->$param=[];
			$this->$total=0;
			foreach ($this->objParams[$param] as $srcItem) {
				foreach ($srcItem as $count=>$width) {
					$this->$param[]=['count'=>$count,'size'=>$width];
					$this->$total+=max(1,$count);
					$this->$use+=$count;
				}
			}
		}
	}
	
	private function initOrDefault($param,$default=null) {
		if (!isset($this->objParams[$param])) {
			if (!is_null($default)) $this->$param=$default;
		} else {
			$this->$param=$this->objParams[$param];
		}
	}
	
	public function run()
	{
		return $this->render('rack/table',[
			'rack'=>$this
		]);
		
		
	}
	
	
	public function init() {
		RackWidgetAsset::register($this->view);

/*		$this->objParams=json_decode($this->params,true);

		$this->initRowsArray('cols');
		$this->initRowsArray('rows');
		$this->initOrDefault('vEnumeration');
		$this->initOrDefault('hEnumeration');
		$this->initOrDefault('evenEnumeration');
		$this->initOrDefault('priorEnumeration');
		$this->initOrDefault('labelMode');
		$this->initOrDefault('labelWidth');
		$this->initOrDefault('labelHeight');
		$this->initOrDefault('labelPre');
		$this->initOrDefault('labelPost');
*/
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
		return $row*$width+$col;
	}
	
	private function getTotalSize($cols,$labelSize) {
		$total=0;
		foreach ($cols as $i=>$item) {
			$total += $item['size'];
		}
		return $total;
	}
	
	
	public function getTotalWidth() {
		if (is_null($this->totalWidthCache))
			$this->totalWidthCache=$this->getTotalSize($this->cols,$this->labelMode == 'h'?$this->labelWidth:0);
		
		if (!$this->totalWidthCache) return 101;
		return $this->totalWidthCache;
	}
	
	public function getTotalHeight() {
		if (is_null($this->totalHeightCache))
			$this->totalHeightCache=$this->getTotalSize($this->rows,$this->labelMode == 'v'?$this->labelHeight:0);
		
		if (!$this->totalHeightCache) return 101;
		return $this->totalHeightCache;
	}
	
	public function getWidthPercent($width) {
		return $width/$this->getTotalWidth()*100;
	}
	public function getHeightPercent($height) {
		return 100*$height/$this->getTotalHeight();
	}
	private function getTotalCount($cols,$labels=0) {
		$total=0;
		foreach ($cols as $i=>$item) {
			$count=isset($item['count'])?$item['count']*(1+$labels):1;
			$total += $count;
		}
		return $total;
	}
	
	public function getLabelsCount() {
		$count=0;
		if ($this->labelPre) $count++;
		if ($this->labelPost) $count++;
		return $count;
	}
	
	public function getTotalCols() {
		if (is_null($this->totalColsCache)){
			$this->totalColsCache=$this->getTotalCount($this->cols,$this->labelMode=='h'?$this->getLabelsCount():0);
		}
		return $this->totalColsCache;
	}
	
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
	
}