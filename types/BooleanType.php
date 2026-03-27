<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\generation\generators\BooleanGenerator;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

class BooleanType implements AttributeTypeInterface
{
	public static function name(): string
	{
		return 'boolean';
	}

	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		return Html::activeCheckbox($model, $attribute, $inputOptions);
	}

	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute ?? null;
		return Html::encode($value ? '1' : '0');
	}

	public function apiSchema(): array
	{
		return ['type' => 'boolean'];
	}

	public function gridColumnClass(): ?string
	{
		return null;
	}

	public function samples(): array
	{
		return [0, 1];
	}

	public function generate(AttributeContext $context): mixed
	{
		$generator = new BooleanGenerator();
		return $generator->generate($context);
	}
}