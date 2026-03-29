<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

class UrlsType extends TextType
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
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}

		$config = $context->generatorConfig();

		// Детерминированная генерация
		$seed = $context->generationContext->seed + crc32($context->attribute);
		mt_srand($seed);

		$min = $context->min ?? 1;
		$max = $context->max ?? 4;
		$count = mt_rand($min, $max);
		
		$protocols = $config['protocols'] ?? ['https','http'];
		$domains = $config['domains'] ?? ['example.com', 'test.org', 'demo.net'];
		
		$result = [];
		
		for ($i = 0; $i < $count; $i++) {
			$protocol = $protocols[mt_rand(0, count($protocols) - 1)];
			$domain = $domains[mt_rand(0, count($domains) - 1)];
			$path = $this->generatePath();
			
			$url = $protocol . '://' . $domain . $path;
			$result[] = $url;
		}

		mt_srand(); // сброс
		return implode("\n", $result);
	}

	private function generatePath(): string
	{
		$segments = mt_rand(1, 4);
		$path = '';
		
		for ($i = 0; $i < $segments; $i++) {
			if ($i > 0) {
				$path .= '/';
			}
			$path .= $this->randomString(mt_rand(3, 12));
		}
		
		// Иногда добавляем файл
		if (mt_rand(0, 1)) {
			$path .= '.' . ['html', 'php', 'json', 'xml'][mt_rand(0, 3)];
		}
		
		return $path;
	}

	private function randomString(int $length): string
	{
		$chars = 'abcdefghijklmnopqrstuvwxyz';
		$result = '';
		for ($i = 0; $i < $length; $i++) {
			$result .= $chars[mt_rand(0, strlen($chars) - 1)];
		}
		return $result;
	}
}