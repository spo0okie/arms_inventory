<?php

namespace app\types;

use app\components\MacSearchWidget;
use app\generation\context\AttributeContext;
use app\helpers\ArrayHelper;
use app\helpers\MacsHelper;
use app\models\base\ArmsModel;
use app\models\Techs;
use yii\helpers\Html;
use yii\web\View;

class MacsType extends TextType
{
	public static function name(): string
	{
		return 'macs';
	}

	/**
	 * Форматированный многострочный вывод: каждый адрес/диапазон на своей
	 * строке в каноническом виде (AA:BB:CC:DD:EE:FF). Работает и от сырого
	 * значения (mac), и от уже форматированного (formattedMac).
	 *
	 * Рядом с каждым адресом — иконка поиска (issue #218,
	 * {@see MacSearchWidget}): поиск это функциональность самого адреса,
	 * поэтому живёт на его типе и одинаков везде, где адрес показывается
	 * (ОС, оборудование, любой mac-атрибут). У диапазона (issue #120) иконка
	 * своя у каждой границы: искать умеем только конкретный адрес, а границы
	 * диапазона — как раз конкретные адреса.
	 *
	 * $options['search'] => false - вывести значения без иконок (списки,
	 * печать, тултипы: там иконка была бы шумом, а в списках ту же задачу
	 * решает фильтр колонки).
	 */
	public function renderOutput(View $view, ArmsModel $model, string $attribute, array $options = []): mixed
	{
		$value=(string)ArrayHelper::getValue($model,$attribute);
		//в статичном режиме (печать, тултипы, вложенные списки) иконка не нужна:
		//там нет интерактива, а тултип внутри тултипа ещё и не работает
		$search=$options['search'] ?? !($options['static_view'] ?? false);

		$items=[];
		foreach (explode("\n",$value) as $line) {
			$rendered=[];
			foreach (static::searchable($line) as $part) {
				$text=Techs::formatMacs($part);
				if (!strlen($text)) continue;

				$rendered[]=Html::tag('span',Html::encode($text),['class'=>'mac_address'])
					.($search?MacSearchWidget::widget(['model'=>$model,'mac'=>$part]):'');
			}
			if (count($rendered)) $items[]=implode(' - ',$rendered);
		}

		return implode('<br />',$items);
	}

	/**
	 * Строка значения, разложенная на адреса, которые имеет смысл искать:
	 * одиночный адрес остаётся собой, диапазон (24 hex, issue #120) — это две
	 * его границы. Разбор тот же, что и в
	 * {@see \app\helpers\MacsHelper::fixList()}: разделители внутри адреса не
	 * важны, а 24 hex однозначно означают пару «начало-конец».
	 *
	 * @param string $line строка значения атрибута
	 * @return string[] адреса в том порядке, в каком их показывать
	 */
	protected static function searchable(string $line): array
	{
		$hex=preg_replace('/[^0-9a-f]/', '', mb_strtolower($line));
		if (strlen($hex)===24) return [substr($hex,0,12),substr($hex,12,12)];
		return [$line];
	}

	/**
	 * Типовая часть подсказки заполнения (см. AttributeDataModelTrait::getAttributeTypeHint):
	 * подклеивается к специфичному hint каждого mac-атрибута.
	 */
	public function inputHint(): ?string
	{
		return 'В каждой строке — один адрес (<b>00:11:22:33:44:55</b>) '
			.'или диапазон адресов через тире (<b>00:11:22:33:44:00-00:11:22:33:44:0F</b>). '
			.'Разделители внутри адреса не важны.';
	}

	/**
	 * Типовая часть подсказки поиска: подклеивается к общему синтаксису поиска.
	 */
	public function searchHint(): ?string
	{
		return 'Поиск по диапазону — только полным MAC-адресом: '
			.'частичный MAC, попадающий внутрь диапазона, не находится.';
	}

	public function renderInput(\app\components\Forms\ActiveField $field, array $options = []): mixed
	{
		return $field->textInput();
	}

	public function samples(): array
	{
		//одиночные адреса и диапазон «через тире» (issue #120)
		return ['83aa792053a2','83aa791953a0','001122334400-0011223344ff'];
	}

	public function generate(AttributeContext $context): mixed
	{
		// Режим пустых значений
		if ($context->empty) {
			return $context->isNullable() ? null : '';
		}
		
		// Детерминированная генерация с изолированным RNG
		$rng = $context->randomizer();

		$min = $context->min ?? 18;
		$max = $context->max ?? 128;
		$count = $rng->getInt(intdiv($min, 18), intdiv($max, 18));
		$result = [];
		
		for ($i = 0; $i < $count; $i++) {
			// Создаём новый randomizer для каждого MAC с уникальным seed
			$macContext = new AttributeContext(
				attribute: $context->attribute . '_mac_' . $i,
				empty: $context->empty,
				model: $context->model,
				generationContext: $context->generationContext,
			);
			$macRng = $macContext->randomizer();
			
			$mac = sprintf(
				'%02X:%02X:%02X:%02X:%02X:%02X',
				$macRng->getInt(0, 255),
				$macRng->getInt(0, 255),
				$macRng->getInt(0, 255),
				$macRng->getInt(0, 255),
				$macRng->getInt(0, 255),
				$macRng->getInt(0, 255)
			);
			$result[] = $mac;
		}

		return implode("\n", $result);
	}

	public function rules(AttributeRuleContext $context): array
	{
		return [
			new RuleDefinition('string'),
			new RuleDefinition('filter',['filter'=>fn($v)=>MacsHelper::fixList($v)]),
		];
	}
}