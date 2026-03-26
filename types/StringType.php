<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\generation\generators\StringGenerator;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

class StringType implements AttributeTypeInterface
{
	public static function name(): string
	{
		return 'string';
	}

	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		return Html::activeTextInput($model, $attribute, $inputOptions);
	}

	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute ?? null;
		if (is_array($value)) {
			$value = implode(', ', $value);
		}
		return Html::encode((string)$value);
	}

	public function apiSchema(): array
	{
		return ['type' => 'string'];
	}

	public function gridColumnClass(): ?string
	{
		return null;
	}

	public function samples(): array
	{
		return [];
	}

	public function generate(AttributeContext $context): mixed
	{
		$generator = new StringGenerator();
		return $generator->generate($context);
	}
}
