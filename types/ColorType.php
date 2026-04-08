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
class ColorType implements AttributeTypeInterface
{
	/**
	 * {@inheritdoc}
	 */
	public static function name(): string
	{
		return 'color';
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		$inputOptions['type'] = 'color';
		$inputOptions['placeholder'] = $inputOptions['placeholder'] ?? '#FF5733';
		return Html::activeTextInput($model, $attribute, $inputOptions);
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute ?? null;
		if ($value === null || $value === '') {
			return '<span class="text-muted">—</span>';
		}

		// Валидация HEX цвета
		if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $value)) {
			return Html::tag('span', Html::encode($value), [
				'class' => 'badge',
				'style' => 'background-color: ' . Html::encode($value) . '; color: ' . $this->getContrastColor($value),
			]);
		}

		return Html::encode((string)$value);
	}

	/**
	 * Получить контрастный цвет для текста на фоне.
	 * @param string $hexColor HEX цвет фона
	 * @return string '#000000' или '#FFFFFF'
	 */
	private function getContrastColor(string $hexColor): string
	{
		// Удаляем # если есть
		$hexColor = ltrim($hexColor, '#');

		// Расширяем 3-символьный цвет до 6-символьного
		if (strlen($hexColor) === 3) {
			$hexColor = $hexColor[0] . $hexColor[0] . $hexColor[1] . $hexColor[1] . $hexColor[2] . $hexColor[2];
		}

		// Вычисляем яркость (luminance)
		$r = hexdec(substr($hexColor, 0, 2));
		$g = hexdec(substr($hexColor, 2, 2));
		$b = hexdec(substr($hexColor, 4, 2));

		$luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

		return $luminance > 0.5 ? '#000000' : '#FFFFFF';
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
		
		// Детерминированная генерация
		mt_srand($context->seed());

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

		$index = mt_rand(0, count($colors) - 1);
		$result = $colors[$index];

		mt_srand(); // сброс
		return $result;
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
