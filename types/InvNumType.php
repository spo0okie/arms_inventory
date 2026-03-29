<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

/**
 * Тип для хранения инвентарного номера.
 *
 * Формат: токены через дефис <тип>-<серийный>
 * Пример: "T-001", "PC-12345", "MON-42"
 */
class InvNumType implements AttributeTypeInterface
{

	//порядок префиксов для инвентарного номера по умолчанию для настройки techs.prefixFormat
	private static $defaultPrefixFormat=['place','org','type'];	

	//количество знаков в цифровой части номера в зависимости
	//от количества токенов префикса по умолчанию для настройки techs.invNumStrPads
	private static $defaultNumStrPad=[9,6,4];
	
	//максимальная длина инвентарного номера (если получится уложиться
	//убирая нули в числовой части) по умолчанию для настройки techs.invNumMaxLen
	private static $defaultNumMaxLen=15;	

	/**
	 * {@inheritdoc}
	 */
	public static function name(): string
	{
		return 'inv-num';
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		$inputOptions['placeholder'] = $inputOptions['placeholder'] ?? 'T-00001';
		return Html::activeTextInput($model, $attribute, $inputOptions);
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute ?? null;
		if ($value === null) {
			return '<span class="text-muted">—</span>';
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
			'format' => 'inv-num',
			'example' => 'T-00001',
			'description' => 'Инвентарный номер в формате <тип>-<серийный>',
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
			'T-00001',
			'PC-12345',
			'MON-42',
			'NET-001',
			'SRV-007',
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

		// Детерминированная генерация на основе seed + имя атрибута
		$seed = $context->generationContext->seed + crc32($context->attribute);
		mt_srand($seed);

		// Префиксы для разных типов оборудования
		$prefixes = [
			'PC',     // Персональный компьютер
			'MON',    // Монитор
			'NET',    // Сетевое оборудование
			'SRV',    // Сервер
			'PRN',    // Принтер
			'PHN',    // Телефон
			'UPS',    // ИБП
			'SW',     // Коммутатор
			'RTR',    // Маршрутизатор
		];

		$prefixIndex = mt_rand(0, count($prefixes) - 1);
		$prefix = $prefixes[$prefixIndex];

		// Серийный номер (3-6 цифр)
		$serialLength = mt_rand(3, 6);
		$serial = '';
		for ($i = 0; $i < $serialLength; $i++) {
			$serial .= (string)mt_rand(0, 9);
		}

		$result = $prefix . '-' . $serial;

		mt_srand(); // сброс
		return $result;
	}

		/**
	 * Возвращает массив размеров числовой части номера в зависимости от количества токенов
	 */
	public static function invNumStrPads() {
		return \Yii::$app->params['techs.invNumStrPads']??static::$defaultNumStrPad;
	}
	
	public static function invNumMaxLen() {
		return \Yii::$app->params['techs.invNumMaxLen']??static::$defaultNumMaxLen;
	}
	
	public static function invNumPrefixFormat() {
		return \Yii::$app->params['techs.prefixFormat']??static::$defaultPrefixFormat;
	}
	
	/**
	 * Возвращает размер числовой части инв. номера в зависимости от количества токенов
	 * @param $tokens - количество токенов
	 * @return int
	 */
	public static function getNumStrPad($tokens) {
		$pads=static::invNumStrPads();
		return $pads[$tokens]??$pads[count($pads)-1];
	}
	
	public static function formatInvNum($value)
	{
		// выполняем определенные действия с переменной, возвращаем преобразованную переменную
		$tokens = explode('-', $value);
		//
		$num_str_pad = static::getNumStrPad(count($tokens)-1);
		
		$num = (int)$tokens[count($tokens) - 1];
		unset($tokens[count($tokens) - 1]);
		$prefix=implode('-', $tokens);
		$num_str_pad=min($num_str_pad,static::invNumMaxLen()-mb_strlen($prefix));
		$num = str_pad((string)$num, $num_str_pad, '0', STR_PAD_LEFT);
		return mb_strtoupper($prefix . '-' . $num);
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('string',['max' => 16]),							//строка не более 16 символов
	        new RuleDefinition( function ($model,$attribute) {					//проверяем синтаксис
        		$tokens=explode('-',$model->$attribute);
				$nonEmpty=true;
				foreach ($tokens as $tok) if (!strlen($tok)) $nonEmpty=false;
		        if (count($tokens)>4 || !$nonEmpty) {
		        	$model->addError($attribute, 
						'Инвентарный номер должен быть в формате "ПРЕФ1-[ПРЕФ2-][ПРЕФ3-]НОМЕР", '
						.'где ПРЕФ1-N - префикс филиала/организации/оборудования, '
						.'НОМЕР - целочисленный номер уникальный для этого набора префиксов.'
					);
		        }
	        }),
	        new RuleDefinition('filter', ['filter' => fn($v) => static::formatInvNum($v)]),
		];
	}
}
