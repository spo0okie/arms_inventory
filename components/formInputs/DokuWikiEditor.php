<?php
namespace app\components\formInputs;

use app\components\assets\DokuWikiEditorAsset;
use yii\helpers\Html;
use yii\web\View;
use yii\widgets\InputWidget;

class DokuWikiEditor extends InputWidget
{
	public $rows=4;
	public function run()
	{
		
		// ID текстового поля
		$inputId = $this->options['id'] ?? Html::getInputId($this->model, $this->attribute);
		
		$this->rows = max($this->rows??4, count(explode("\n", $this->model->{$this->attribute})));
		
		// Выводим HTML
		echo Html::tag('div','', ['id' => 'dokuwiki-toolbar-container']);
		//echo $this->renderToolbar();
		echo Html::activeTextarea($this->model, $this->attribute, [
			'id' => $inputId,
			'class' => 'form-control dokuwiki-textarea',
			'rows' => $this->rows
		]);
		

		
		$this->view->registerJs(
			//добавляем авторесайз
			"$('#$inputId').autoResize({extraSpace:25}).trigger('change.dynSiz');"
			//скрипты с библиотеками подгружаются не всегда сразу, поэтому инициализируем тулбар
			//тогда, когда функции уже загружены
			."window.waitToolbarFunction = setInterval(() => {
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