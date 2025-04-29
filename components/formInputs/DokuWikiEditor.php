<?php
namespace app\components\formInputs;

use app\components\assets\DokuWikiEditorAsset;
use yii\helpers\Html;
use yii\widgets\InputWidget;

class DokuWikiEditor extends InputWidget
{
	public $rows=4;
	public function run()
	{
		// Регистрируем JS/CSS
		DokuWikiEditorAsset::register($this->view);
		
		// ID текстового поля
		$inputId = $this->options['id'] ?? Html::getInputId($this->model, $this->attribute);
		
		$this->rows = max($this->rows??4, count(explode("\n", $this->model->{$this->attribute})));
		
		// Выводим HTML
		echo Html::beginTag('div', ['class' => 'dokuwiki-editor']);
		//echo $this->renderToolbar();
		echo Html::activeTextarea($this->model, $this->attribute, [
			'id' => $inputId,
			'class' => 'form-control dokuwiki-textarea',
			'rows' => $this->rows
		]);
		
		//добавляем авторесайз
		$this->view->registerJs("$('#$inputId').autoResize({extraSpace:25}).trigger('change.dynSiz');");
		
		echo Html::endTag('div');
	}
	
	protected function renderToolbar()
	{
		return <<<HTML
<div class="dokuwiki-toolbar">
    <button type="button" data-command="bold"><b>B</b></button>
    <button type="button" data-command="italic"><i>I</i></button>
    <button type="button" data-command="heading">H</button>
    <button type="button" data-command="link">Link</button>
    <button type="button" data-command="code">Code</button>
</div>
HTML;
	}
}