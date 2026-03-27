<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\generation\generators\UrlsGenerator;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

class UrlsType implements AttributeTypeInterface
{
	public static function name(): string
	{
		return 'urls';
	}

	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		return Html::activeTextarea($model, $attribute, $inputOptions);
	}

	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute ?? null;
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
		$generator = new UrlsGenerator();
		return $generator->generate($context);
	}
}