<?php

namespace tests\unit\components;

use app\components\AttributeTooltip;
use app\models\Comps;
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
}
