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
	private static array $defaultPrefixFormat=['place','org','type'];

	//количество знаков в цифровой части номера в зависимости
	//от количества токенов префикса по умолчанию для настройки techs.invNumStrPads
	private static array $defaultNumStrPad=[9,6,4];
	
	//максимальная длина инвентарного номера (если получится уложиться
	//убирая нули в числовой части) по умолчанию для настройки techs.invNumMaxLen
	private static int $defaultNumMaxLen=15;

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
			'URAL-PC-0001',
			'PC-DMX-12345',
			'MON-MSK-0042',
			'NET-WIFI-0001',
			'SRV-MAIN-0007',
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

		// Префиксы для разных типов оборудования
		$types = [
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
		
		
		$places=['MSK','SPB','NSK','EKB','KZN','RND','SAM','VLD'];
		$orgs=['DMX','UUL','ZAO','XXN','RNK'];
		$tokens=[];
		
		foreach (static::invNumPrefixFormat() as $token) {
			switch ($token) {
				case 'place':
					$tokens[]=$places[mt_rand(0,count($places)-1)];
					break;
				case 'org':
					$tokens[]=$orgs[mt_rand(0,count($orgs)-1)];
					break;
				case 'type':
					$tokens[]=$types[mt_rand(0,count($types)-1)];
					break;
			}
		}
		
		//или цепочка из префиксов или пусто
		return self::formatInvNum(implode('-',$tokens).'-'.mt_rand(0,1000));
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
	 * Возвращает размер числовой части инвентарного номера в зависимости от количества токенов
	 * @param $tokens - количество токенов
	 * @return int
	 */
	public static function getNumStrPad($tokens):int {
		$pads=static::invNumStrPads();
		return $pads[$tokens]??$pads[count($pads)-1];
	}
	
	/**
	 * Переводит инвентарный номер в стандартный формат
	 * (приводит к верхнему регистру, дополняет нулями числовую часть)
	 * @param $value
	 * @return string
	 */
	public static function formatInvNum($value):string
	{
		// разбиваем на токены по дефису, последний токен - числовой, остальные - префиксы
		$tokens = explode('-', $value);
		//определяем размер числовой части в зависимости от количества токенов префикса
		$num_str_pad = static::getNumStrPad(count($tokens)-1);
		//числовая часть
		$num = (int)$tokens[count($tokens) - 1];
		
		//собираем отдельно префиксы
		unset($tokens[count($tokens) - 1]);
		$prefix=implode('-', $tokens);
		
		//ограничиваем размер числовой части, чтобы не превышать максимальную длину инвентарного номера
		$num_str_pad=min($num_str_pad,static::invNumMaxLen()-mb_strlen($prefix));
		//добиваем нулями
		$num = str_pad((string)$num, $num_str_pad, '0', STR_PAD_LEFT);
		//собираем обратно и приводим к верхнему регистру
		return mb_strtoupper($prefix . '-' . $num);
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('string',['max' => 16]),							//строка не более 16 символов
	        new RuleDefinition(function ($model, $attribute) {					//проверяем синтаксис
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
