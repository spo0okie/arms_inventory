<?php
namespace app\components;

use app\models\ArmsModel;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\Url;

class ShowArchivedWidget extends Widget
{
	
	public $state=null;
	public $reload=true;
	public $label='Архивные';
	public $hint=null;
	
	public function init() {
		if (is_null($this->state))
			$this->state = \Yii::$app->request->get('showArchived');
		
		if (is_null($this->hint)){
			$this->hint=$this->state?'Скрыть архивные объекты':'Показать архивные объекты';
		}
	}
	
	public function run()
	{
		return UrlParamSwitcherWidget::widget([
			'param'=>'showArchived',
			'hintOff'=>'Скрыть архивные объекты',
			'hintOn'=>'Показать архивные объекты',
			'label'=>$this->label,
			'reload'=>$this->reload,
			'scriptOn'=>"\$('.archived-item').show();",
			'scriptOff'=>"\$('.archived-item').hide();",
		]);
	}
}