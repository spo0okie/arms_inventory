<?php
namespace app\components;

use app\components\assets\CollapsableCardWidgetAsset;
use yii\base\Widget;
use app\components\assets\ExpandableCardWidgetAsset;
use yii\helpers\Html;
use yii\web\JsExpression;

class CollapsableCardWidget extends Widget
{
	
	public $title='Сворачиваемый блок';
	public $openedTitle=null;
	public $closedTitle=null;
	public $buttonTag='div';
	public $contentTag='div';
	public $content='';
	public $id;
	public $initialCollapse=false;
	
	
	public function init()
	{
		parent::init();
		if (is_null($this->closedTitle)) $this->closedTitle=$this->title;
		if (is_null($this->openedTitle)) $this->openedTitle=$this->title;
		if (is_null($this->id)) $this->id = uniqid('collapsable-');
		CollapsableCardWidgetAsset::register($this->view);
	}
	
	public function showButton() {
		return Html::tag($this->buttonTag,$this->closedTitle,[
				'class'=>'collapsable-widget-show-button',
				'id'=>$this->id.'-show-button',
				'style'=>!$this->initialCollapse?'display:none':null,
				'onclick'=>new JsExpression('CollapsableCardWidgetSwitch("'.$this->id.'")'),
			]
		);
	}

	public function hideButton() {
		return Html::tag($this->buttonTag,$this->openedTitle,[
				'class'=>'collapsable-widget-hide-button',
				'id'=>$this->id.'-hide-button',
				'style'=>!$this->initialCollapse?null:'display:none',
				'onclick'=>new JsExpression('CollapsableCardWidgetSwitch("'.$this->id.'")'),
			]
		);
	}
	
	public function run()
	{
		return $this->hideButton().
			$this->showButton().
			Html::tag($this->contentTag,$this->content,[
				'class'=>'collapsable-card-widget',
				'style'=>$this->initialCollapse?'display:none':null,
				'id'=>$this->id,
			]);
	}
	
}