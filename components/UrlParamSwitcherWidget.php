<?php
namespace app\components;

use app\models\ArmsModel;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\web\JsExpression;

class UrlParamSwitcherWidget extends Widget
{
	
	public $state=null;
	public $label='Переключатель';
	public $param='urlParam';
	public $hint=null;
	public $hintOff='Выключить';
	public $hintOn='Выключить';
	public $reload=true;
	public $scriptOff='';
	public $scriptOn='';
	private $onChange;
	
	public function init() {
		if (is_null($this->state))
			$this->state = \Yii::$app->request->get($this->param);
		
		if (is_null($this->hint)){
			$this->hint=$this->state?
				$this->hintOff:
				$this->hintOn;
		}
		
		$newPageUrl=Url::current([$this->param=>!$this->state]);
		$reload=$this->reload?'true':'false';
		$js=<<<JS
	function on{$this->param}SelectorChange(selector) {
		if ($reload) {
		    window.location.href='$newPageUrl';
		    $(selector).attr('disabled',1);
		    $(selector).parent().attr('qtip_ttip','Загрузка...');
		    attach_qTip($(selector).parent(),true);
			$(selector).parent().tooltipster('show');
		} else {
			let url=new URL(window.location.href);
			url.searchParams.set('{$this->param}', selector.checked?1:0);
			window.history.pushState({},'',url.href);
		    $(selector).parent().attr('qtip_ttip',selector.checked?'{$this->hintOff}':'{$this->hintOn}');
		    attach_qTip($(selector).parent(),true);
		}
		$(selector).parent().tooltipster('show');
	}
JS;
		$this->view->registerJs($js,$this->view::POS_HEAD);
		
		$this->onChange='if (this.checked) {'.$this->scriptOn.'} else {'.$this->scriptOff.'}; on'.$this->param. 'SelectorChange(this)';
	}
	
	public function run()
	{
		return '<div class="form-switch '.$this->param.'-switcher-widget" qtip_ttip="'.$this->hint.'" qtip_side="top,bottom,right,left">
  			<input class="form-check-input"
  				type="checkbox"
  				id="'.$this->param.'SwitchWidget"
  				onchange="'.$this->onChange.'" '.($this->state?'checked':'').'>
  			<label class="form-check-label" for="'.$this->param.'SwitchWidget">'.$this->label.'</label>
			</div>';
	}
}