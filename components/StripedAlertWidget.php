<?php
namespace app\components;

use yii\base\Widget;

class StripedAlertWidget extends Widget
{
	
	public $title='void()';
	public function run()
	{
		return '<div class="d-flex w-100 my-2">
			<div class="flex-fill alert-striped"></div>
			<div class="text-center mx-2">
				<span class="fas fa-exclamation-triangle"></span>
				'.$this->title.'
				<span class="fas fa-exclamation-triangle"></span>
			</div>
			<div class="flex-fill alert-striped"></div>
		</div>';
	}
}