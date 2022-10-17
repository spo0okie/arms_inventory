<?php
namespace app\components;

use app\models\ArmsModel;
use yii\base\Widget;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\Url;

class StripedRowWidget extends Widget
{
	
	public $title='void()';
	public function run()
	{
		return '<div class="d-flex nowrap justify-content-center p-0">'.
			'<span class="archived-card-alert flex-fill p-0 m-0"></span>'.
			'<span class="p-0 mx-2">'.$this->title.'</span>'.
			'<span class="archived-card-alert flex-fill p-0 m-0"></span>'.
		'</div>';
	}
}