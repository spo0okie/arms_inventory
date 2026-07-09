<?php

namespace tests\unit\components;

use app\components\AttributeTooltip;
use app\models\Comps;
use app\models\Segments;
use app\models\Services;
use app\models\Techs;
use Codeception\Test\Unit;

/**
 * Тесты единого сборщика тултипов атрибутов (спецификация: ui-sources.md §0.1).
 * Проверяют состав блоков по режимам, чистоту getAttributeHint (композиция
 * ушла из уровня данных) и приоритет явного searchHint.
 */
class AttributeTooltipTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * getAttributeHint возвращает чистую смысловую часть - без типовых довесков.
	 */
	public function testGetAttributeHintIsClean()
	{
		foreach ([new Comps(), new Techs()] as $model) {
			$hint = (string)$model->getAttributeHint('mac');
			$this->assertStringContainsString('MAC адреса сетевых интерфейсов', $hint);
			$this->assertStringNotContainsString('диапазон', $hint);
			$this->assertStringNotContainsString('text-muted', $hint);
		}
	}

	/**
	 * Режим form: смысл + приглушенный формат типа (inputHint) + переход
	 * на страницу типа (docs/help/types/macs.md существует).
	 */
	public function testFormMode()
	{
		$tooltip = AttributeTooltip::build(new Techs(), 'mac', AttributeTooltip::MODE_FORM);
		$this->assertNotNull($tooltip);
		$this->assertEquals('MAC адреса', $tooltip['title']);
		//блок 1: смысл
		$this->assertStringContainsString('MAC адреса сетевых интерфейсов', $tooltip['body']);
		//блок 1а: формат, приглушенно
		$this->assertStringContainsString('диапазон адресов через тире', $tooltip['body']);
		$this->assertStringContainsString('text-muted', $tooltip['body']);
		//блок 3: переход на страницу типа
		$this->assertStringContainsString('подробнее о типе', $tooltip['body']);
	}

	/**
	 * Режим grid: формат ввода НЕ показывается (просачивание устранено).
	 */
	public function testGridModeHasNoInputFormat()
	{
		$tooltip = AttributeTooltip::build(new Techs(), 'mac', AttributeTooltip::MODE_GRID);
		$this->assertNotNull($tooltip);
		$this->assertStringContainsString('MAC адреса сетевых интерфейсов', $tooltip['body']);
		$this->assertStringNotContainsString('диапазон адресов через тире', $tooltip['body']);
	}

	/**
	 * Режим search: общий синтаксис поиска + приглушенная типовая особенность.
	 */
	public function testSearchMode()
	{
		$tooltip = AttributeTooltip::build(new Comps(), 'mac', AttributeTooltip::MODE_SEARCH);
		$this->assertNotNull($tooltip);
		//дефолт по типу данных (строковый синтаксис)
		$this->assertStringContainsString('сложные запросы', $tooltip['body']);
		//типовая особенность поиска (MacsType::searchHint), приглушенно
		$this->assertStringContainsString('Поиск по диапазону', $tooltip['body']);
		//формата ввода в search-режиме нет
		$this->assertStringNotContainsString('диапазон адресов через тире', $tooltip['body']);
	}

	/**
	 * Явный searchHint из attributeData полностью вытесняет дефолт и типовую часть.
	 */
	public function testExplicitSearchHintWins()
	{
		$model = new class extends Techs {
			public function attributeData()
			{
				$data = parent::attributeData();
				$data['mac']['searchHint'] = 'явная подсказка';
				return $data;
			}
		};
		$tooltip = AttributeTooltip::build($model, 'mac', AttributeTooltip::MODE_SEARCH);
		$this->assertStringContainsString('явная подсказка', $tooltip['body']);
		$this->assertStringNotContainsString('сложные запросы', $tooltip['body']);
		$this->assertStringNotContainsString('Поиск по диапазону', $tooltip['body']);
	}

	/**
	 * Явное переопределение смыслового блока (hintOverride) и заголовка.
	 */
	public function testOverrides()
	{
		$tooltip = AttributeTooltip::build(
			new Techs(), 'mac', AttributeTooltip::MODE_GRID,
			'Свой label', 'Свой hint'
		);
		$this->assertEquals('Свой label', $tooltip['title']);
		$this->assertStringContainsString('Свой hint', $tooltip['body']);
		$this->assertStringNotContainsString('MAC адреса сетевых интерфейсов', $tooltip['body']);
	}

	/**
	 * Типовые inputHint подклеиваются и другим типам (не только MAC):
	 * например ссылки (UrlsType) в форме получают формат заполнения.
	 */
	public function testUrlsTypeInputHint()
	{
		$tooltip = AttributeTooltip::build(new Techs(), 'url', AttributeTooltip::MODE_FORM);
		$this->assertNotNull($tooltip);
		$this->assertStringContainsString('последнее слово — сам URL', $tooltip['body']);
	}

	/**
	 * Блок 1б (источник значения): хранимая колонка блока не дает.
	 */
	public function testViewModeStoredAttributeGivesNoSource()
	{
		$tooltip = AttributeTooltip::build(new Techs(), 'mac', AttributeTooltip::MODE_VIEW);
		$this->assertNotNull($tooltip);
		$this->assertStringNotContainsString('вычисляемое поле', $tooltip['body']);
		$this->assertStringNotContainsString('унаследовано', $tooltip['body']);
	}

	/**
	 * Блок 1б: не-колонка помечается как вычисляемое поле, приглушенно
	 * и только в режиме view.
	 */
	public function testViewModeCalculatedAttribute()
	{
		$model = new Techs();
		$tooltip = AttributeTooltip::build($model, 'inv_sn', AttributeTooltip::MODE_VIEW);
		$this->assertNotNull($tooltip);
		$this->assertStringContainsString('вычисляемое поле', $tooltip['body']);
		$this->assertStringContainsString('text-muted', $tooltip['body']);

		foreach ([AttributeTooltip::MODE_FORM, AttributeTooltip::MODE_GRID, AttributeTooltip::MODE_SEARCH] as $mode) {
			$tooltip = AttributeTooltip::build($model, 'inv_sn', $mode);
			$this->assertStringNotContainsString('вычисляемое поле', $tooltip['body']??'');
		}
	}

	/**
	 * Блок 1б: у незагруженной записи рекурсивная часть опускается -
	 * остается «вычисляемое поле» (цепочка наследования зависит от объекта).
	 */
	public function testViewModeRecursiveOnNewRecord()
	{
		$tooltip = AttributeTooltip::build(new Services(), 'segmentRecursive', AttributeTooltip::MODE_VIEW);
		$this->assertNotNull($tooltip);
		$this->assertStringContainsString('вычисляемое поле', $tooltip['body']);
		$this->assertStringNotContainsString('унаследовано', $tooltip['body']);
	}

	/**
	 * Блок 1б: наследуемый атрибут (is_inheritable) со значением в самой записи.
	 */
	public function testViewModeRecursiveOwnValue()
	{
		$model = new Services();
		$model->setIsNewRecord(false);
		$model->populateRelation('segment', new Segments(['name' => 'Свой сегмент']));

		$tooltip = AttributeTooltip::build($model, 'segmentRecursive', AttributeTooltip::MODE_VIEW);
		$this->assertNotNull($tooltip);
		$this->assertStringContainsString('наследуемый атрибут', $tooltip['body']);
		$this->assertStringContainsString('значение задано в этой записи', $tooltip['body']);
	}

	/**
	 * Блок 1б: значение унаследовано - ссылка на предка-источника.
	 * Аннотация одинаково работает и для виртуального <attr>Recursive,
	 * и для plain-атрибута (alias-разрешение getAttributeData).
	 */
	public function testViewModeRecursiveInherited()
	{
		$parent = new Services(['name' => 'Родительский сервис']);
		$parent->setIsNewRecord(false);
		$parent->populateRelation('segment', new Segments(['name' => 'Сегмент предка']));
		$parent->populateRelation('parentService', null);

		$model = new Services();
		$model->setIsNewRecord(false);
		$model->populateRelation('segment', null);
		$model->populateRelation('parentService', $parent);

		foreach (['segmentRecursive', 'segment'] as $attr) {
			$tooltip = AttributeTooltip::build($model, $attr, AttributeTooltip::MODE_VIEW);
			$this->assertNotNull($tooltip);
			$this->assertStringContainsString('унаследовано от', $tooltip['body']);
			//ссылка на предка-источника (не на сегмент)
			$this->assertStringContainsString('Родительский сервис', $tooltip['body']);
			$this->assertStringContainsString('services', $tooltip['body']);
		}
	}

	/**
	 * Блок 1б: наследуемый атрибут без значения по всей цепочке.
	 */
	public function testViewModeRecursiveUnset()
	{
		$parent = new Services();
		$parent->setIsNewRecord(false);
		$parent->populateRelation('segment', null);
		$parent->populateRelation('parentService', null);

		$model = new Services();
		$model->setIsNewRecord(false);
		$model->populateRelation('segment', null);
		$model->populateRelation('parentService', $parent);

		$tooltip = AttributeTooltip::build($model, 'segmentRecursive', AttributeTooltip::MODE_VIEW);
		$this->assertNotNull($tooltip);
		$this->assertStringContainsString('не задано ни здесь, ни у предков', $tooltip['body']);
	}

	/**
	 * Блок 1б: ссылочный атрибут (значение по хранимой ссылке) не считается
	 * вычисляемым - блока нет.
	 */
	public function testViewModeLinkAttributeGivesNoSource()
	{
		$tooltip = AttributeTooltip::build(new Techs(), 'user', AttributeTooltip::MODE_VIEW);
		$this->assertStringNotContainsString('вычисляемое поле', $tooltip['body'] ?? '');
	}

	/**
	 * Подача: AttributeTooltip::icon - единственная точка рендера иконки «?».
	 * Тултип и pin-поведение (qtip_pin) висят на иконке; пустой тултип
	 * (build вернул null) - иконки нет.
	 */
	public function testIcon()
	{
		$this->assertSame('', AttributeTooltip::icon(null));

		$icon = AttributeTooltip::icon(['title' => 'Заголовок', 'body' => 'Тело']);
		$this->assertStringContainsString('fa-question-circle', $icon);
		$this->assertStringContainsString('attr-hint-icon', $icon);
		$this->assertStringContainsString('qtip_pin', $icon);
		$this->assertStringContainsString('qtip_ttip', $icon);
		$this->assertStringContainsString('Заголовок', $icon);
		$this->assertStringContainsString('Тело', $icon);

		//заготовка для JS: те же классы и pin, но без контента
		$template = AttributeTooltip::iconTemplate();
		$this->assertStringContainsString('attr-hint-icon', $template);
		$this->assertStringContainsString('qtip_pin', $template);
		$this->assertStringNotContainsString('qtip_ttip', $template);
	}

	/**
	 * В тултипах объекта (ttip-действиях) иконка «?» атрибута подавляется
	 * (тултип в тултипе — шум), но остаётся на обычных страницах. Аналогично
	 * тому, как LinkObjectWidget не делает ссылку на объект на его own view/ttip.
	 */
	public function testIconHiddenInTooltipContext()
	{
		$tooltip = ['title' => 'Заголовок', 'body' => 'Тело'];

		//вне ttip-действия иконка есть
		$this->assertStringContainsString('attr-hint-icon', AttributeTooltip::icon($tooltip));

		$prev = \Yii::$app->controller;
		try {
			foreach (['ttip', 'ttip-hw', 'ttips'] as $actionId) {
				$ctrl = new \app\controllers\CompsController('comps', \Yii::$app);
				$ctrl->action = new \yii\base\Action($actionId, $ctrl);
				\Yii::$app->controller = $ctrl;
				$this->assertSame('', AttributeTooltip::icon($tooltip), "иконка должна прятаться в действии $actionId");
			}

			//не-ttip действие иконку не прячет
			$ctrl = new \app\controllers\CompsController('comps', \Yii::$app);
			$ctrl->action = new \yii\base\Action('view', $ctrl);
			\Yii::$app->controller = $ctrl;
			$this->assertStringContainsString('attr-hint-icon', AttributeTooltip::icon($tooltip));
		} finally {
			\Yii::$app->controller = $prev;
		}
	}

	/**
	 * Форма (FieldsHelper::labelOption): тултип-опции на иконке в составе
	 * label, сами label-опции чистые (qtip на label не вешается).
	 */
	public function testFormLabelIconDelivery()
	{
		[$label, $options] = \app\helpers\FieldsHelper::labelOption(new Techs(), 'mac', []);
		$this->assertStringStartsWith('MAC адреса', $label);
		$this->assertStringContainsString('attr-hint-icon', $label);
		$this->assertStringContainsString('qtip_ttip', $label);
		$this->assertSame([], $options);
	}

	/**
	 * Grid/search-заголовки (AttributeHintWidget): label чистый, тултип
	 * на иконке «?» после него.
	 */
	public function testAttributeHintWidgetIconDelivery()
	{
		$model = new Techs();
		$out = \app\components\AttributeHintWidget::widget([
			'model' => $model,
			'attribute' => 'mac',
			'mode' => 'grid',
		]);
		//label чистый (без обёртки с qtip), тултип на иконке после него
		$this->assertStringStartsWith($model->getAttributeIndexLabel('mac').' <span', $out);
		$this->assertStringContainsString('attr-hint-icon', $out);
		$this->assertStringContainsString('qtip_ttip', $out);
	}

	/**
	 * ModelFieldWidget::fieldTitle делегирует сборщику: label по цепочке
	 * view-режима + иконка «?» с телом тултипа (включая блок 1б);
	 * options пустые (подача на иконке).
	 */
	public function testModelFieldWidgetTitleDelegates()
	{
		$model = new Techs();
		[$label, $options] = \app\components\ModelFieldWidget::fieldTitle($model, 'inv_sn');
		$this->assertStringStartsWith('Бух/SN/Доп.', $label);
		$this->assertStringContainsString('attr-hint-icon', $label);
		$this->assertStringContainsString('qtip_ttip', $label);
		$this->assertStringContainsString('вычисляемое поле', $label);
		$this->assertSame([], $options);
	}

	/**
	 * ModelFieldWidget::renderFieldValue: режим "только значение" -
	 * без h4-подписи и карточки, для инлайн-мест свободной вёрстки;
	 * полная подача при этом сохраняет заголовок.
	 */
	public function testRenderFieldValue()
	{
		$model = new Techs(['num' => 'ARM-0001']);

		$bare = \app\components\ModelFieldWidget::renderFieldValue($model, 'num');
		$this->assertStringContainsString('ARM-0001', $bare);
		$this->assertStringNotContainsString('<h4', $bare);
		$this->assertStringNotContainsString('card', $bare);

		$full = \app\components\ModelFieldWidget::widget(['model' => $model, 'field' => 'num']);
		$this->assertStringContainsString('ARM-0001', $full);
		$this->assertStringContainsString('<h4', $full);
	}

	/**
	 * ModelFieldWidget::renderFieldRow: строка «подпись: значение»;
	 * пустое значение - пустая строка (для implode+array_filter вёрстки):
	 * пустоту обрабатывает подача, рендер типа на пустом не вызывается.
	 */
	public function testRenderFieldRow()
	{
		$model = new Techs(['num' => 'ARM-0001']);

		$row = \app\components\ModelFieldWidget::renderFieldRow($model, 'num');
		$this->assertStringContainsString('Инвентарный номер', $row);
		$this->assertStringContainsString(': ', $row);
		$this->assertStringContainsString('ARM-0001', $row);
		$this->assertStringContainsString('qtip_ttip', $row);

		$this->assertSame('', \app\components\ModelFieldWidget::renderFieldRow($model, 'sn'));
	}

	/**
	 * ModelFieldWidget::detailAttribute: конфиг строки DetailView с подписью
	 * и тултипом от той же точки сборки; формат 'attr:format' сохраняется,
	 * переопределения конфига работают.
	 */
	public function testDetailAttribute()
	{
		$model = new Techs();
		$row = \app\components\ModelFieldWidget::detailAttribute($model, 'inv_sn:ntext', ['visible' => false]);
		$this->assertEquals('inv_sn:ntext', $row['attribute']);
		$this->assertStringStartsWith('Бух/SN/Доп.', $row['label']);
		//подача: тултип на иконке в составе label, captionOptions чистые
		$this->assertStringContainsString('attr-hint-icon', $row['label']);
		$this->assertStringContainsString('вычисляемое поле', $row['label']);
		$this->assertSame([], $row['captionOptions']);
		$this->assertFalse($row['visible']);
	}
}
