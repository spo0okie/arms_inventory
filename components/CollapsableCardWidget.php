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
	public $buttonClass='';
	public $contentTag='div';
	public $contentClass='';
	public $content='';
	public $id;
	public $initialCollapse=false;
	public $saveState=false;
	protected $cookieName='';
	
	
	public function init()
	{
		parent::init();
		if (is_null($this->closedTitle)) $this->closedTitle=$this->title;
		if (is_null($this->openedTitle)) $this->openedTitle=$this->title;
		if (is_null($this->id)) $this->id = uniqid('collapsable-');
		CollapsableCardWidgetAsset::register($this->view);
		if ($this->saveState) {
			$this->cookieName=$this->id.'-show';
			$cookie=$_COOKIE[$this->cookieName]??false;
			$this->initialCollapse=($this->initialCollapse&&($cookie!=="true"));
		}
	}
	
	public function showButton() {
		return Html::tag($this->buttonTag,$this->closedTitle,[
				'class'=>'collapsable-widget-show-button '.$this->buttonClass,
				'id'=>$this->id.'-show-button',
				'style'=>!$this->initialCollapse?'display:none':null,
				'onclick'=>new JsExpression('CollapsableCardWidgetSwitch("'.$this->id.'")'),
			]
		);
	}

	public function hideButton() {
		return Html::tag($this->buttonTag,$this->openedTitle,[
				'class'=>'collapsable-widget-hide-button '.$this->buttonClass,
				'id'=>$this->id.'-hide-button',
				'style'=>!$this->initialCollapse?null:'display:none',
				'onclick'=>new JsExpression('CollapsableCardWidgetSwitch("'.$this->id.'")'),
			]
		);
	}
	
	public function switchButton() {
		return Html::tag($this->buttonTag,$this->openedTitle,[
				'class'=>$this->buttonClass,
				'id'=>$this->id.'-switch-button',
				'onclick'=>new JsExpression('CollapsableCardWidgetSwitch("'.$this->id.'")'),
			]
		);
	}
	public function switcher() {
		if ($this->closedTitle === $this->openedTitle)
			return $this->switchButton();
		else
			return $this->hideButton().$this->showButton();
	}
	
	public function card() {
		return Html::tag($this->contentTag,$this->content,[
			'class'=>'collapsable-card-widget '.$this->contentClass,
			'style'=>$this->initialCollapse?'display:none':null,
			'id'=>$this->id,
			'data'=>['cookie-name'=>$this->cookieName]
		]);
	}
	
	public function run()
	{
		return $this->switcher().$this->card();
	}
	
}