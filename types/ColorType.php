<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

/**
 * Тип для хранения цвета в HEX формате.
 *
 * Формат: #RRGGBB или #RGB
 * Пример: "#FF5733", "#fff", "#000000"
 */
class ColorType extends BaseType
{
	/**
	 * {@inheritdoc}
	 */
	public static function name(): string
	{
		return 'color';
	}

	/**
	 * Ввод — цветовой пикер с палитрой
	 * {@inheritdoc}
	 */
	public function renderInput(\app\components\Forms\ActiveField $field, array $options = []): mixed
	{
		return $field->widget(\kartik\color\ColorInput::class, [
			'options' => ['placeholder' => '#RRGGBB'],
			'pluginOptions' => [
				'showInput' => true,
				'showInitial' => true,
				'showPalette' => true,
				'showSelectionPalette' => true,
				'preferredFormat' => 'hex',
				'palette' => [
					['#FF0000', '#FF5733', '#FF8C00', '#FFD700', '#FFFF00'],
					['#00FF00', '#00FF7F', '#00CED1', '#0000FF', '#4B0082'],
					['#8B00FF', '#FF00FF', '#FF1493', '#C71585', '#808080'],
				],
			],
		]);
	}

	/**
	 * Валидный HEX-цвет - badge с контрастным текстом на фоне этого цвета,
	 * невалидный - как есть. Пустоту обрабатывает потребитель
	 * (show_empty/message_on_empty), рендер на пустом не вызывается.
	 */
	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute ?? '';

		// Валидация HEX цвета
		if (\app\helpers\ColorHelper::isValidHex($value)) {
			return Html::tag('span', Html::encode($value), [
				'class' => 'badge',
				'style' => 'background-color: ' . Html::encode($value) . '; color: ' . \app\helpers\ColorHelper::contrastColor($value),
			]);
		}

		return Html::encode((string)$value);
	}

	/**
	 * {@inheritdoc}
	 */
	public function apiSchema(): array
	{
		return [
			'type' => 'string',
			'format' => 'color',
			'example' => '#FF5733',
			'description' => 'HEX цвет в формате #RRGGBB или #RGB',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function gridColumnClass(): ?string
	{
		return null;
	}

	/**
	 * {@inheritdoc}
	 */
	public function samples(): array
	{
		return [
			'#FF5733',
			'#3498DB',
			'#2ECC71',
			'#E74C3C',
			'#9B59B6',
			'#F1C40F',
			'#1ABC9C',
			'#34495E',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function generate(AttributeContext $context): mixed
	{
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}
		
		// Детерминированная генерация с изолированным RNG
		$rng = $context->randomizer();

		// Предопределённые "хорошие" цвета (web-safe + популярные)
		$colors = [
			'#FF5733', // Оранжево-красный
			'#3498DB', // Синий
			'#2ECC71', // Зелёный
			'#E74C3C', // Красный
			'#9B59B6', // Фиолетовый
			'#F1C40F', // Жёлтый
			'#1ABC9C', // Бирюзовый
			'#34495E', // Тёмно-серый
			'#E67E22', // Оранжевый
			'#2980B9', // Тёмно-синий
			'#27AE60', // Тёмно-зелёный
			'#C0392B', // Тёмно-красный
			'#8E44AD', // Тёмно-фиолетовый
			'#D35400', // Тёмно-оранжевый
			'#16A085', // Тёмно-бирюзовый
			'#2C3E50', // Тёмно-синий серый
		];

		return AttributeContext::pickRandomValue($colors, $rng);
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('string',['max'=>'7']),
			new RuleDefinition('match',[
				'pattern' => '/^#[0-9A-Fa-f]{6}$/',
				'message' => 'Цвет должен быть в формате HEX (#RRGGBB)'
			])
		];
	}

}
