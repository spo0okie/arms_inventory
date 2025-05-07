<?php
namespace app\components\formInputs;

use app\components\assets\DokuWikiEditorAsset;
use app\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\InputWidget;

class DokuWikiEditor extends InputWidget
{
	public $rows=4;
	
	public function run()
	{
		$inputId=$this->options['id']??Html::getInputId($this->model, $this->attribute);
		
		// Выводим тулбар (наполняется позже через JS)
		echo Html::tag('div','', ['id' => 'dokuwiki-toolbar-container']);

		echo TextAutoResizeWidget::widget([
			'model'=>$this->model,
			'attribute'=>$this->attribute,
			'options'=>ArrayHelper::recursiveOverride([
				'rows' => $this->rows,
				'class' => 'form-control dokuwiki-textarea',
			],$this->options)
		]);
		

		
		$this->view->registerJs(
			//скрипты с библиотеками подгружаются не всегда сразу, поэтому инициализируем тулбар
			//тогда, когда функции уже загружены
			"window.waitToolbarFunction = setInterval(() => {
    			if (typeof initToolbar === 'function') {
        			clearInterval(window.waitToolbarFunction); // Останавливаем проверку
        			window.waitToolbarFunction=undefined;
        			initToolbar('dokuwiki-toolbar-container','$inputId',toolbar);
    			}
			}, 400);"
		);
		
		$this->initDokuWikiToolbar();
	}
	
	protected function initDokuWikiToolbar()
	{
	
		// Регистрируем JS/CSS
		DokuWikiEditorAsset::register($this->view);
		$this->view->registerJs(
			"window.DOKU_BASE='".\Yii::$app->params['wikiUrl']."';"
			."window.LANG={};"
			."window.JSINFO = {act: 'edit',id: 'inventory_internal.sys'};"
			,View::POS_BEGIN
		);
	}
}