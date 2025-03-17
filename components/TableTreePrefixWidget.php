<?php
namespace app\components;

use yii\base\Widget;
use yii\helpers\Html;

class TableTreePrefixWidget extends Widget
{
	
	public $prefix='';
	
	private function treeItem($pos=0,$content='',$height=400) {
		$pos*=15;
		return Html::tag('span',$content,[
			'class'=>'table-tree-prefix',
			'style'=>"height:${height}px;left:${pos}px"
		]);
	}

	private function vLine() {
		return Html::tag('span','',[
			'class'=>'table-tree-v-line'
		]);
	}
	
	private function hLine() {
		return Html::tag('span','',[
			'class'=>'table-tree-h-line'
		]);
	}
	
	private function symbolReplace($symbol,$pos) {
		switch ($symbol) {
			case ' ': return $this->treeItem($pos);
			case '│': return $this->treeItem($pos,$this->vLine());
			case '├': return $this->treeItem($pos,$this->vLine().$this->hLine());
			case '└': return $this->treeItem($pos,$this->vLine().$this->hLine(),9);
		}
		return '';
	}
	
	public function run()
	{
		$output='';
		$pos=0;
		foreach (mb_str_split($this->prefix) as $symbol) {
			$output.=$this->symbolReplace($symbol,$pos++);
		}
		$padding=$pos*15;
		$output.="<span style='padding-left:${padding}px'>";
		echo $output;
	}
	
}