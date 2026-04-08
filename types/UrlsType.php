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
		
		// Детерминированная генерация с изолированным RNG
		$rng = $context->randomizer();

		$min = $context->min ?? 1;
		$max = $context->max ?? 4;
		$count = $rng->getInt($min, $max);
		
		$protocols = $config['protocols'] ?? ['https','http'];
		$domains = $config['domains'] ?? ['example.com', 'test.org', 'demo.net'];
		
		$result = [];
		
		for ($i = 0; $i < $count; $i++) {
			$protocol = AttributeContext::pickRandomValue($protocols, $rng);
			$domain = AttributeContext::pickRandomValue($domains, $rng);
			$path = $this->generatePath($rng);
			
			$url = $protocol . '://' . $domain . $path;
			$result[] = $url;
		}

		return implode("\n", $result);
	}

	private function generatePath(\Random\Randomizer $rng): string
	{
		$segments = $rng->getInt(1, 4);
		$path = '';
		
		for ($i = 0; $i < $segments; $i++) {
			if ($i > 0) {
				$path .= '/';
			}
			$path .= $this->randomString($rng->getInt(3, 12), $rng);
		}
		
		// Иногда добавляем файл
		if ($rng->getInt(0, 1)) {
			$path .= '.' . AttributeContext::pickRandomValue(['html', 'php', 'json', 'xml'], $rng);
		}
		
		return $path;
	}

	private function randomString(int $length, \Random\Randomizer $rng): string
	{
		$chars = 'abcdefghijklmnopqrstuvwxyz';
		$result = '';
		for ($i = 0; $i < $length; $i++) {
			$result .= $chars[$rng->getInt(0, strlen($chars) - 1)];
		}
		return $result;
	}
}