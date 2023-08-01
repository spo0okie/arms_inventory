<?php
namespace app\components;

use yii\base\Widget;
use app\components\assets\ExpandableCardWidgetAsset;
use yii\helpers\Html;

class ExpandableCardWidget extends Widget
{
	
	public $content='';
	public $initialExpand=false;
	public $maxHeight='150';
	public $cardClass='';
	public $switchOnlyOnButton=false;
	
	private $currentClass=null;
	
	public function init()
	{
		parent::init();
		if ($this->switchOnlyOnButton) $this->cardClass.=' switch-only-on-button';
		$this->currentClass=$this->initialExpand?'':'compressed';
		ExpandableCardWidgetAsset::register($this->view);
	}
	
	public function run()
	{
		return '<div class="expandable-card-outer '.$this->currentClass.' '.$this->cardClass.'" data-expandable-max-height="'.$this->maxHeight.'">'.
			'<div class="expandable-card-content">'.$this->content.'</div>'.
			'</div>';
	}
	
}