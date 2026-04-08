<?php

namespace app\types;

use app\generation\context\AttributeContext;
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
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : date('Y-m-d H:i:s');
		}

		$config = $context->generatorConfig();
		
		// Детерминированная генерация
		mt_srand($context->seed());

		$minYear = $config['min_year'] ?? 2020;
		$maxYear = $config['max_year'] ?? date('Y');
		
		$year = mt_rand($minYear, $maxYear);
		$month = mt_rand(1, 12);
		$day = mt_rand(1, 28);
		$hour = mt_rand(0, 23);
		$minute = mt_rand(0, 59);
		$second = mt_rand(0, 59);
		
		mt_srand(); // сброс
		return sprintf('%04d-%02d-%02d %02d:%02d:%02d', $year, $month, $day, $hour, $minute, $second);
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('string',['max'=>'20']),
			new RuleDefinition('match',[
				'pattern' => '/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}(:[0-9]{2})?$/',
				'message' => 'Дата и время должны быть в формате YYYY-MM-DD HH:MM:SS'
			])
		];
	}
}