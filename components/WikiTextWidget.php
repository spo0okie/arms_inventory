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
use app\models\ArmsModel;
use app\models\ui\WikiCache;
use Yii;
use yii\base\Widget;

class WikiTextWidget extends Widget
{
	// Заглушка для ожидания загрузки данных
	const PLACEHOLDER='<div class="spinner-border" role="status">'
		.'<span class="visually-hidden">Loading...</span>'
		.'</div>';
	
	/** @var ArmsModel */
	public $model;
	public $field;
	
	public function run()
	{
		DokuWikiAsset::register($this->view);
		$class=get_class($this->model);
		$id=StringHelper::class2Id($class)
			.'-'.$this->model->id
			.'-'.StringHelper::class2Id($this->field);
		
		$cache=WikiCache::fetchCache( //при кэшировании всегда ссылаемся на оригинальный атрибут без суффикса Recursive
			WikiCache::internalPath($class, $this->model->id, StringHelper::removeSuffix($this->field))
		);
		
		//данные - либо из кэша, либо надпись "Loading..."
		$data=$cache->data;
		if (!$data) $data=$this->model->{$this->field};
		//кладем данные в контент блок
		$content='<div id="'.$id.'" class="dokuwiki">'.$data.'</div>';
		
		$outdated=false;
		if ($this->model->hasAttribute('updated_at')) {
			$outdated=$this->model->updated_at>$cache->updated_at;
		}
		
		//если данные требуют обновления - добавляем скрипт обновления контент-блока
		if ($outdated || !$cache->valid) $content.='<script>
			$.get(
				"/web/wiki/render-field?class='.urlencode($class).'&id='.$this->model->id.'&field='.$this->field.'",
				function(data) {
					$("#'.$id.'").html(data);
					'.(DokuWikiAsset::$dokuWikiInit).'
				}
            )
		</script>';
		
		return $content;
			
	}
}