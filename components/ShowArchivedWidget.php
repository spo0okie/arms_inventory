<?php
namespace app\components;

use app\models\ArmsModel;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\Url;

class ShowArchivedWidget extends UrlParamSwitcherWidget
{
	
	public $state=null;
	public $label='Архивные';
	public $scriptOff="\$('.archived-item').show();";
	public $scriptOn="\$('.archived-item').hide();";
	public $param='showArchived';
	public $hintOff='Скрыть архивные объекты';
	public $hintOn='Показать архивные объекты';
	
	public function init() {
		parent::init();
		if (is_null($this->state))
			$this->state = \Yii::$app->request->get('showArchived');
		
		if (is_null($this->hint)){
			$this->hint=$this->state?'Скрыть архивные объекты':'Показать архивные объекты';
		}
	}
	
}