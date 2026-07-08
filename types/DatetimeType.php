<?php

namespace app\types;

use app\generation\context\AttributeContext;

class DatetimeType extends BaseType
{
	public static function name(): string
	{
		return 'datetime';
	}

	public function inputHint(): ?string
	{
		return 'Дата и время в формате <b>ГГГГ-ММ-ДД ЧЧ:ММ</b>; можно выбрать в календаре.';
	}

	public function renderInput(\app\components\Forms\ActiveField $field, array $options = []): mixed
	{
		return $field->datetime();
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
		
		// Детерминированная генерация с изолированным RNG
		$rng = $context->randomizer();

		$minYear = $config['min_year'] ?? 2020;
		$maxYear = $config['max_year'] ?? date('Y');
		
		$year = $rng->getInt($minYear, $maxYear);
		$month = $rng->getInt(1, 12);
		$day = $rng->getInt(1, 28);
		$hour = $rng->getInt(0, 23);
		$minute = $rng->getInt(0, 59);
		$second = $rng->getInt(0, 59);
		
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