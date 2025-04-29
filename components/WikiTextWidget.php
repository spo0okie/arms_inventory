<?php
/**
 * Created by PhpStorm.
 * User: spookie
 * Date: 16.11.2018
 * Time: 9:05
 */

namespace app\components;

use app\assets\DokuWikiAsset;
use app\helpers\StringHelper;
use app\helpers\WikiHelper;
use Yii;
use yii\base\Widget;

class WikiTextWidget extends Widget
{
	public $model;
	public $field;
	
	public function run()
	{
		DokuWikiAsset::register($this->view);
		$class=get_class($this->model);
		$id=StringHelper::class2Id($class)
			.'-'.$this->model->id
			.'-'.StringHelper::class2Id($this->field);
			
			return '<div id="'.$id.'" class="dokuwiki">
				<div class="spinner-border" role="status">
					<span class="visually-hidden">Loading...</span>
				</div>
			</div>
			<script>
				$.get(
                    "/web/wiki/render-field?class='.urlencode($class).'&id='.$this->model->id.'&field='.$this->field.'",
                    function(data) {
                    	$("#'.$id.'").html(data);
                    	'.(DokuWikiAsset::$dokuWikiInit).'
                    })
			</script>';
	}
}