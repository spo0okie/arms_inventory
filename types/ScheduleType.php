<?php

namespace app\types;

use app\generation\context\AttributeContext;
use app\models\base\ArmsModel;
use yii\helpers\Html;
use yii\web\View;

/**
 * Тип для хранения расписания рабочего/нерабочего времени.
 *
 * Формат: ЧЧ:ММ-ЧЧ:ММ или несколько интервалов через запятую
 * Пример: "09:00-12:00,13:00-18:00" или "-" (нерабочий день)
 */
class ScheduleType implements AttributeTypeInterface
{
	/**
	 * {@inheritdoc}
	 */
	public static function name(): string
	{
		return 'schedule';
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderInput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$inputOptions = $options['inputOptions'] ?? [];
		$inputOptions['placeholder'] = $inputOptions['placeholder'] ?? '09:00-18:00';
		return Html::activeTextInput($model, $attribute, $inputOptions);
	}

	/**
	 * {@inheritdoc}
	 */
	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value = $model->$attribute ?? null;
		if ($value === '-' || $value === null) {
			return '<span class="text-muted">выходной</span>';
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
			'format' => 'schedule',
			'example' => '09:00-12:00,13:00-18:00',
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
			'09:00-18:00',
			'00:00-23:59',
			'09:00-12:00,13:00-18:00',
			'-',
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

		// Варианты расписания для генерации
		$schedules = [
			'09:00-18:00',           // Стандартный рабочий день
			'00:00-23:59',           // Круглосуточно
			'09:00-12:00,13:00-18:00', // С перерывом на обед
			'10:00-19:00',           // Поздняя смена
			'08:00-17:00',           // Ранняя смена
			'09:00-17:00',           // Короткий день
			'09:00-13:00',           // Утро
			'14:00-18:00',           // Вечер
		];

		$index = mt_rand(0, count($schedules) - 1);
		$result = $schedules[$index];

		mt_srand(); // сброс
		return $result;
	}


	/**
	 * Проверка валидности записи времени на формат ЧЧ:ММ
	 * @param $time
	 * @return boolean
	 */
	public static function validateTime($time): ?string
	{
		$time=trim($time);
		
		if (!strlen($time)) return 'Пустое время это нарушение синтаксиса'; //пустое время
		
		$tokens=explode(':',$time);
		
		if (count($tokens)!==2) return 'Ожидается формат ЧЧ:ММ'; //ожидаем именно ЧЧ:ММ , т.е. токенов 2 и никак иначе

		foreach ($tokens as $token) if (strlen(trim($token))>2) return 'Ожидается формат ЧЧ:ММ'; //никаких ЧЧЧ или МММ
		
		if ((int)$tokens[0]>23) return 'Часы не могут быть больше 23'; //ограничение часов сверху
		if ((int)$tokens[1]>59) return 'Минуты не могут быть больше 59'; //ограничение минут сверху
		
		return null;
	}


	/**
	 * Проверка валидности записи времени на формат ЧЧ:ММ-ЧЧ:ММ{some_metadata}
	 * @param $schedule
	 * @return boolean
	 */
	public static function validateInterval($schedule): ?string
	{
		$schedule=trim($schedule);
		
		if (!strlen($schedule)) return 'Пустой интервал в расписании это нарушение синтаксиса'; //пустой токен это косяк. Пустым может быть атрибут, но не токен
		
		$pos = strpos($schedule, '{');

		if ($pos !== false) {
    		$json = substr($schedule, $pos);

			try {
 			   json_decode($json, true, 512, JSON_THROW_ON_ERROR);
			} catch (\JsonException $e) {
    			return "Ошибка в метаданных расписания: ".$json;
			}
    		$schedule = substr($schedule, 0, $pos);
			
		}

		//далее проверяем что расписание вида ЧЧ:MM-ЧЧ:ММ		
		$tokens=explode('-',$schedule);
		
		//ожидаем именно ЧЧ:ММ-ЧЧ:ММ, т.е. токенов 2 и никак иначе
		if (count($tokens)!==2) return "Ожидается формат интервала ЧЧ:ММ-ЧЧ:ММ, обнаружено $schedule";
		
		foreach ($tokens as $token) {
			$error=static::validateTime($token);
			if ($error) return $error; //проверяем каждый токен на формат ЧЧ:ММ
		}
		
		return null;
	}
	
	
	/**
	 * Проверка валидности записи времени на формат ЧЧ:ММ-ЧЧ:ММ,ЧЧ:ММ-ЧЧ:ММ,ЧЧ:ММ-ЧЧ:ММ
	 * @param $schedules
	 * @return boolean
	 */
	public static function validateSchedule($schedules):?string
	{
		$schedules=trim($schedules);
		
		if (!strlen($schedules)) return null; //пустое расписание само по себе не нарушение синтаксиса. только если оно required, но это не ограничение типа
		
		if ($schedules==='-') return null; //валидная запись "нерабочий день"
		
		//далее проверяем что расписание вида ЧЧ:MM-ЧЧ:ММ{}[,ЧЧ:MM-ЧЧ:ММ{}]
		
		$tokens=explode(',',$schedules);
		
		if (!count($tokens)) return 'Не удалось найти интервалы в расписании'; //ожидаем не меньше одного токена
		
		foreach ($tokens as $token) {
			$error=static::validateTime($token);
			if ($error) return 'Ошибка в интвервале '.$token.': '.$error; //проверяем каждый токен на формат ЧЧ:ММ
		}
			
		return null;
	}
	

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('string'),
			new RuleDefinition(function($model,$attribute) {
				$error=static::validateSchedule($model->$attribute);
				if ($error) {
					$model->addError($attribute, $error);
				}
			}),
		];
	}
}
