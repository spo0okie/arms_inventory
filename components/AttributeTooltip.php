<?php

namespace app\components;

use app\helpers\DocsHelper;
use app\helpers\FieldsHelper;
use yii\helpers\Inflector;

/**
 * Единый сборщик тултипов атрибутов. Спецификация: ui-sources.md §0.1.
 *
 * Тело тултипа — последовательность блоков (разделяются <hr/>, строки внутри
 * блока — <br>, пустые блоки пропускаются):
 *  1  смысл («что это»)      — hint/indexHint/viewHint по режиму
 *  1а формат («как вводить») — inputHint() типа, только в form, приглушённо
 *  2  поиск («как искать»)   — только в search: явный searchHint attributeData
 *     вытесняет всё; иначе дефолт по типу данных + приглушённый searchHint() типа
 *  3  переходы на слой 2     — ссылки на MD-страницы атрибута и типа
 *
 * Все потребители (ActiveField, FieldsHelper, AttributeHintWidget, docs)
 * зовут сборщик и ничего не конкатенируют сами.
 */
class AttributeTooltip
{
	const MODE_FORM='form';
	const MODE_GRID='grid';
	const MODE_SEARCH='search';
	const MODE_VIEW='view';

	/**
	 * Собирает тултип атрибута.
	 * @param object|mixed $model         модель (обычно ArmsModel)
	 * @param string       $attr
	 * @param string       $mode          form|grid|search|view
	 * @param string|null  $labelOverride явный заголовок тултипа
	 * @param string|null  $hintOverride  явный текст смыслового блока
	 * @return array|null ['title'=>string,'body'=>string] либо null — тултип не нужен
	 */
	public static function build($model, string $attr, string $mode=self::MODE_FORM, ?string $labelOverride=null, ?string $hintOverride=null): ?array
	{
		$blocks=[];

		//блок 1: смысл («что это»)
		$meaning=$hintOverride??static::meaning($model,$attr,$mode);

		//блок 1а: формат («как вводить») - только в форме, приглушенно, продолжением смысла
		if ($mode===self::MODE_FORM && ($format=static::typeHint($model,$attr,'inputHint'))) {
			$format='<span class="text-muted">'.$format.'</span>';
			$meaning=$meaning? $meaning.'<br>'.$format : $format;
		}
		if ($meaning) $blocks[]=$meaning;

		//блок 2: поиск («как искать») - только в колонке с фильтром
		if ($mode===self::MODE_SEARCH && ($search=static::searchBlock($model,$attr))) {
			$blocks[]=$search;
		}

		//блок 3: переходы на слой 2 (по одной ссылке на строку)
		if (is_object($model) && ($links=DocsHelper::attributeDetailsLinks($model,$attr))) {
			$blocks[]=implode('<br>',$links);
		}

		if (!count($blocks)) return null;

		return [
			'title'=>$labelOverride??static::title($model,$attr,$mode),
			'body'=>implode('<hr/>',$blocks),
		];
	}

	/**
	 * То же, но сразу qtip-опциями для options=>[] HTML-элемента
	 * ([] — тултип не нужен).
	 */
	public static function options($model, string $attr, string $mode=self::MODE_FORM, ?string $labelOverride=null, ?string $hintOverride=null): array
	{
		$tooltip=static::build($model,$attr,$mode,$labelOverride,$hintOverride);
		return $tooltip? FieldsHelper::toolTipOptions($tooltip['title'],$tooltip['body']) : [];
	}

	/**
	 * Смысловая часть по режиму (первое непустое по цепочке фолбэка).
	 */
	protected static function meaning($model, string $attr, string $mode): string
	{
		if (!is_object($model)) return '';
		switch ($mode) {
			case self::MODE_GRID:
			case self::MODE_SEARCH:
				$methods=['getAttributeIndexHint','getAttributeHint'];
				break;
			case self::MODE_VIEW:
				$methods=['getAttributeViewHint','getAttributeHint'];
				break;
			default:
				$methods=['getAttributeHint'];
		}
		foreach ($methods as $method) {
			if (method_exists($model,$method) && ($hint=$model->$method($attr))) return $hint;
		}
		return '';
	}

	/**
	 * Заголовок тултипа по режиму.
	 */
	protected static function title($model, string $attr, string $mode): string
	{
		if (is_object($model)) {
			switch ($mode) {
				case self::MODE_GRID:
				case self::MODE_SEARCH:
					$methods=['getAttributeIndexLabel','getAttributeLabel'];
					break;
				case self::MODE_VIEW:
					$methods=['getAttributeViewLabel','getAttributeLabel'];
					break;
				default:
					$methods=['getAttributeLabel'];
			}
			foreach ($methods as $method) {
				if (method_exists($model,$method) && ($label=$model->$method($attr))) return $label;
			}
		}
		return Inflector::camel2words($attr,true);
	}

	/**
	 * Поисковый блок: явный searchHint из attributeData вытесняет и дефолт,
	 * и типовую часть; иначе дефолт по типу данных + приглушённый searchHint() типа.
	 */
	protected static function searchBlock($model, string $attr): string
	{
		if (!is_object($model) || !method_exists($model,'getAttributeSearchHint')) return '';

		$search=(string)$model->getAttributeSearchHint($attr);

		//явный searchHint ({same} уже подставлен) - блок состоит только из него
		if (method_exists($model,'getAttributeData')
			&& isset($model->getAttributeData($attr)['searchHint'])
		) return $search;

		if ($typed=static::typeHint($model,$attr,'searchHint'))
			$search.=($search?'<br>':'').'<span class="text-muted">'.$typed.'</span>';

		return $search;
	}

	/**
	 * Типовая подсказка (inputHint|searchHint) либо null.
	 */
	protected static function typeHint($model, string $attr, string $method): ?string
	{
		if (!is_object($model) || !method_exists($model,'getAttributeTypeHint')) return null;
		return $model->getAttributeTypeHint($attr,$method);
	}
}
