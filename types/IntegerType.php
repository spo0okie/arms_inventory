<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\generation\generators\IntegerGenerator;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

class IntegerType implements AttributeTypeInterface
{
	public static function name(): string
	{
		return 'integer';
	}

	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		return Html::activeTextInput($model, $attribute, $inputOptions);
	}

	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute ?? null;
		return Html::encode((string)$value);
	}

	public function apiSchema(): array
	{
		return ['type' => 'integer'];
	}

	public function gridColumnClass(): ?string
	{
		return null;
	}

	public function samples(): array
	{
		return [1];
	}

	public function generate(AttributeContext $context): mixed
	{
		$generator = new IntegerGenerator();
		return $generator->generate($context);
	}
}