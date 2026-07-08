<?php

namespace app\components\formInputs;

use kartik\markdown\MarkdownEditor;

/**
 * Обёртка над kartik MarkdownEditor для PHP 8.1+: у части кнопок дефолтного
 * тулбара icon/label приходят null, а renderButton делает trim(null) -
 * deprecated роняет рендер формы. Нормализуем до вызова родителя.
 * Используется вместо MarkdownEditor во всех наших формах (ActiveField::text
 * и прямые вызовы) - при обновлении vendor-пакета можно будет убрать.
 */
class MarkdownEditorFix extends MarkdownEditor
{
	protected function renderButton($btn, $options = [], $markup = true)
	{
		$options['icon'] = $options['icon'] ?? '';
		$options['label'] = $options['label'] ?? '';
		return parent::renderButton($btn, $options, $markup);
	}
}
