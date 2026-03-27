<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\generation\generators\DateTimeGenerator;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

class DatetimeType implements AttributeTypeInterface
{
	public static function name(): string
	{
		return 'datetime';
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
		return ['type' => 'string', 'format' => 'date-time'];
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
		$generator = new DateTimeGenerator();
		return $generator->generate($context);
	}
}