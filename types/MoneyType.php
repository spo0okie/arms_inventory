<?php

namespace app\types;

use app\helpers\ArrayHelper;
use app\models\base\ArmsModel;
use yii\web\View;

/**
 * Денежная сумма: число с разделителем разрядов + символ валюты.
 * Наследует ввод/валидацию/генерацию от FloatType, переопределяет только вывод.
 *
 * Параметры в attributeData атрибута:
 *  - 'decimals'     — знаков после запятой (по умолчанию 2);
 *  - 'currencyPath' — путь к объекту валюты с ->symbol (по умолчанию 'currency';
 *                     например 'service.currency', когда валюта берётся у сервиса).
 *
 * Нулевая/пустая сумма не выводится (0 = «нет стоимости»): совпадает с обёртками
 * `if ($model->cost)` в карточках, поэтому подпись без суммы не появляется.
 */
class MoneyType extends FloatType
{
	public static function name(): string
	{
		return 'money';
	}

	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value=ArrayHelper::getValue($model,$attribute);
		if ($value===null || $value==='' || $value==0) return '';

		$data=$model->getAttributeData($attribute);
		$decimals=$data['decimals'] ?? 2;
		$currency=ArrayHelper::getValue($model,$data['currencyPath'] ?? 'currency');
		$symbol=is_object($currency) ? $currency->symbol : '';

		$formatted=number_format((float)$value,$decimals,'.',' ');
		return $symbol==='' ? $formatted : $formatted.' '.$symbol;
	}
}
