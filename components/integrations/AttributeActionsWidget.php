<?php

namespace app\components\integrations;

use app\helpers\StringHelper;
use app\models\base\ArmsModel;
use yii\base\Widget;
use yii\helpers\Html;

/**
 * Инлайн-действия интеграций рядом со значением атрибута
 * (docs/dev/integrations.md). Пример: иконка отправки SMS
 * рядом с номером телефона пользователя.
 *
 * Виджет generic: спрашивает у реестра attributeActions() всех
 * применимых провайдеров — ни одного упоминания конкретных интеграций
 * в ядре/view-файлах нет. Если атрибут содержит несколько значений
 * (телефоны через запятую) — вызывается для каждого значения отдельно
 * с передачей value.
 */
class AttributeActionsWidget extends Widget
{
	/** @var ArmsModel|null модель, атрибут которой отображается */
	public ?ArmsModel $model = null;

	/** @var string имя атрибута */
	public string $attribute = '';

	/** @var mixed конкретное отображаемое значение (default: значение атрибута) */
	public $value = null;

	public function run()
	{
		if (!is_object($this->model) || $this->model->isNewRecord || $this->attribute === '') return '';
		$model = $this->model;
		$classId = StringHelper::class2Id(get_class($model));

		$html = '';
		foreach (IntegrationsRegistry::providers() as $provider) {
			if (!$provider->appliesTo($model)) continue;
			foreach ($provider->attributeActions($model, $this->attribute, $this->value) as $actionId => $descriptor) {
				if (!IntegrationsRegistry::userCanRun($provider, $actionId)) continue;

				$html .= ' '.Html::a(
					'<i class="'.($descriptor['icon'] ?? 'fas fa-plug').'"></i>',
					IntegrationsRegistry::actionUrl($provider, $actionId, $descriptor, $model),
					[
						'class' => 'open-in-modal-form',
						'qtip_ttip' => $descriptor['title'] ?? $provider->getTitle(),
						'qtip_side' => 'top',
					]
				);
			}
		}
		return $html;
	}
}
