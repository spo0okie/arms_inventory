<?php

namespace app\components;

use app\helpers\DocsHelper;
use app\helpers\FieldsHelper;
use Yii;
use yii\helpers\Html;
use yii\helpers\Inflector;

/**
 * Единый сборщик тултипов атрибутов. Спецификация: ui-sources.md §0.1.
 *
 * Тело тултипа — последовательность блоков (разделяются <hr/>, строки внутри
 * блока — <br>, пустые блоки пропускаются):
 *  1  смысл («что это»)      — hint/indexHint/viewHint по режиму
 *  1а формат («как вводить») — inputHint() типа, только в form, приглушённо
 *  1б источник значения      — только в view, приглушённо, автоматически из
 *     модели: наследуемый атрибут (задано здесь / унаследовано от
 *     предка-ссылки / не задано) / вычисляемое поле; хранимые колонки
 *     и ссылочные атрибуты блока не дают
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

		//блок 1б: источник значения - только в карточке, приглушенно, продолжением смысла
		if ($mode===self::MODE_VIEW && ($source=static::valueSourceBlock($model,$attr))) {
			$source='<span class="text-muted">'.$source.'</span>';
			$meaning=$meaning? $meaning.'<br>'.$source : $source;
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
	 * Тултип для СОСТАВНОГО блока — рендера, собранного из нескольких атрибутов
	 * кастомной логикой (значение через один тип не выразить). Значение остаётся
	 * во вьюхе как есть, а «?» над блоком документирует, какие атрибуты в нём
	 * участвуют: перечисляет их подписи + смысл (block 1 каждого, режим view).
	 * Так самодокументируемость (element-locality) сохраняется и для композитов.
	 * @param object      $model
	 * @param string[]    $fields участвующие атрибуты
	 * @param string|null $title  заголовок блока (тултипа)
	 * @param string      $intro  вводная строка над списком атрибутов
	 * @return array|null ['title'=>string,'body'=>string] либо null — нечего показывать
	 */
	public static function buildComposite($model, array $fields, ?string $title, string $intro='Использованы атрибуты:'): ?array
	{
		$items=[];
		foreach ($fields as $field) {
			$label=static::title($model,$field,self::MODE_VIEW);
			$meaning=static::meaning($model,$field,self::MODE_VIEW);
			$items[]='<li><b>'.$label.'</b>'.($meaning? ': '.$meaning : '').'</li>';
		}
		if (!count($items)) return null;
		return [
			'title'=>$title,
			'body'=>$intro.'<ul class="mb-0 ps-3">'.implode('',$items).'</ul>',
		];
	}

	/**
	 * Иконка «?» подсказки атрибута — единственная точка ПОДАЧИ тултипа
	 * (ui-sources.md §0.1, канон разметки): потребители дописывают иконку
	 * к label, сам label остаётся чистым (без qtip-атрибутов). Тултип
	 * и pin-поведение (qtip_pin: клик приколачивает тултип, см.
	 * web/tooltipster/js/qtip_ajax.js) висят на иконке; статусы цветом —
	 * web/css/qtip.css (.attr-hint-icon / :hover / .qtip-pinned).
	 * @param array|null $tooltip результат build(); null — иконки нет
	 * @param bool $onlyHelp скрытая подача: иконка не видна, проступает только
	 *   в режиме help-mode (класс attr-hint-icon--onlyhelp, web/css/qtip.css) —
	 *   для цепочек/значений в свободной вёрстке, где «?» рядом с каждым звеном
	 *   была бы шумом, но атрибут должен оставаться самодокументируемым.
	 */
	public static function icon(?array $tooltip, bool $onlyHelp=false): string
	{
		if (!$tooltip) return '';
		//В тултипах (ttip-действиях) иконку «?» не показываем: тултип внутри
		//тултипа — шум, а место компактное. По той же логике, что LinkObjectWidget
		//не делает ссылку на объект на его собственных страницах view/ttip.
		if (static::inTooltipRender()) return '';
		$options=static::iconOptions();
		if ($onlyHelp) Html::addCssClass($options,'attr-hint-icon--onlyhelp');
		return Html::tag('span',static::iconGlyph(),array_merge(
			$options,
			FieldsHelper::toolTipOptions($tooltip['title'],$tooltip['body'])
		));
	}

	/**
	 * Идёт ли рендер внутри тултипа объекта (ttip-действие: ttip/ttip-hw/ttips).
	 * В таком контексте иконки «?» атрибутов подавляются (тултип в тултипе — шум).
	 */
	protected static function inTooltipRender(): bool
	{
		try {
			$action=Yii::$app->controller->action ?? null;
		} catch (\Throwable $e) {
			return false;
		}
		return $action!==null && strncmp((string)$action->id,'ttip',4)===0;
	}

	/**
	 * Заготовка иконки без контента тултипа (qtip_ttip) — для JS, который
	 * подставляет контент динамически (см. views/techs/_form.php).
	 */
	public static function iconTemplate(): string
	{
		return Html::tag('span',static::iconGlyph(),array_merge(static::iconOptions(),[
			'qtip_side'=>'top,bottom,right,left',
			'qtip_theme'=>'tooltipster-shadow tooltipster-shadow-infobox',
		]));
	}

	/**
	 * Глиф — как у иконки помощи страницы (HintIconWidget).
	 */
	protected static function iconGlyph(): string
	{
		return '<i class="far fa-question-circle"></i>';
	}

	/**
	 * Общие атрибуты span-обёртки иконки; qtip_pin включает pin-поведение.
	 * Тултип и статусные классы висят на обёртке, а не на самом FA-элементе:
	 * FontAwesome заменяет <i> на <svg> и пересоздаёт его при изменении
	 * классов - tooltipster-инстанс и обработчики умирали бы вместе
	 * со старым элементом (по той же причине HintIconWidget вешает
	 * тултип на <a>).
	 */
	protected static function iconOptions(): array
	{
		return ['class'=>'attr-hint-icon','qtip_pin'=>'1'];
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
	 * Блок 1б «источник значения» (только view) — выводится из модели
	 * автоматически, руками не пишется:
	 *  - наследуемый атрибут (is_inheritable; алиасы link_id<->getter и
	 *    <attr>Recursive разрешает getAttributeData) у загруженной записи —
	 *    задано здесь / унаследовано от предка (ссылкой) / не задано;
	 *  - хранимая колонка и ссылочные атрибуты (значение по хранимой
	 *    ссылке) — блока нет (дефолт не шумит);
	 *  - прочее (нет колонки) — «вычисляемое поле».
	 * @return string|null null - блок не нужен
	 */
	protected static function valueSourceBlock($model, string $attr): ?string
	{
		if (!is_object($model) || !method_exists($model,'hasAttribute')) return null;

		//наследуемый атрибут: где фактически задано значение - интроспекция
		//конкретной записи, у пустой модели цепочки наследования нет
		if (!$model->getIsNewRecord()
			&& method_exists($model,'attributeIsInheritable') && $model->attributeIsInheritable($attr)
			&& method_exists($model,'findRecursiveAttrNode')
		) {
			try {
				$node=$model->findRecursiveAttrNode($attr);
				if ($node===$model) return 'наследуемый атрибут: значение задано в этой записи';
				//предок - стандартным рендером имени объекта (unification.md:
				//ItemObjectWidget/LinkObjectWidget, samePage-механика)
				if (is_object($node)) return 'наследуемый атрибут: унаследовано от '
					.$node->renderItem(Yii::$app->view,['static_view'=>true]);
				return 'наследуемый атрибут: значение не задано ни здесь, ни у предков';
			} catch (\Throwable $e) {
				//цепочка не разрешилась - падаем на общую логику ниже
			}
		}

		//хранимые колонки и ссылочные атрибуты - самоочевидно, не шумим
		if ($model->hasAttribute($attr)) return null;
		if (method_exists($model,'attributeIsLink') && $model->attributeIsLink($attr)) return null;
		if (method_exists($model,'attributeIsLoader') && $model->attributeIsLoader($attr)) return null;

		return 'вычисляемое поле';
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
