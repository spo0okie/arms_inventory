<?php

namespace tests\unit\components;

use app\components\ModelFieldWidget;
use app\components\TextFieldWidget;
use app\types\HwListType;
use app\types\JsonType;
use app\types\LinkType;
use app\types\MacsType;
use app\types\StringType;
use app\types\SwListType;
use app\types\TextType;
use app\types\UrlsType;
use app\models\Acls;
use app\models\MaintenanceReqs;
use app\models\Segments;
use app\models\Services;
use app\models\Techs;
use Codeception\Test\Unit;
use kartik\markdown\Markdown;
use Yii;

/**
 * Тесты боевого renderOutput() системы типов и его подключения к
 * ModelFieldWidget (plans/view-hints.md, этап 4г). Критерий этапа - паритет:
 * рендер существующих вьюх на ModelFieldWidget не меняется, обогащение
 * рендеров - не здесь (4в).
 */
class ModelFieldRenderOutputTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * TextType::renderOutput, дефолтная ветка (ntext): та же логика, что
	 * была в TextFieldWidget; сам TextFieldWidget делегирует типу.
	 */
	public function testTextTypeNtext()
	{
		$model = new Acls(['comment' => "строка1\nстрока2 <b>"]);
		$rendered = (new TextType())->renderOutput(Yii::$app->view, $model, 'comment');

		$this->assertSame(Yii::$app->formatter->asNtext($model->comment), $rendered);
		$this->assertStringContainsString('<br', $rendered);
		$this->assertStringContainsString('&lt;b&gt;', $rendered);
		//делегирование: виджет выдает ровно то же
		$this->assertSame($rendered, TextFieldWidget::widget(['model' => $model, 'field' => 'comment']));
		//обертка outerClass (как было в TextFieldWidget)
		$wrapped = (new TextType())->renderOutput(Yii::$app->view, $model, 'comment', ['outerClass' => 'wrp']);
		$this->assertStringContainsString('<div class="wrp">', $wrapped);
	}

	/**
	 * TextType::renderOutput: выбор markdown-рендера по params['textFields'].
	 */
	public function testTextTypeMarkdown()
	{
		$model = new Acls(['comment' => '**жирный** текст']);
		$backup = Yii::$app->params['textFields'] ?? [];
		Yii::$app->params['textFields']['Acls.comment'] = 'markdown';
		try {
			$rendered = (new TextType())->renderOutput(Yii::$app->view, $model, 'comment');
			$this->assertSame(Markdown::convert($model->comment), $rendered);
			$this->assertStringContainsString('<strong>жирный</strong>', $rendered);
		} finally {
			Yii::$app->params['textFields'] = $backup;
		}
	}

	/**
	 * UrlsType::renderOutput: список готовых элементов-ссылок по
	 * фактическому атрибуту (логика UrlListWidget).
	 */
	public function testUrlsTypeRenderOutput()
	{
		$model = new MaintenanceReqs(['links' => "описание сервиса https://example.com/page\nhttps://test.org/doc"]);
		$rendered = (new UrlsType())->renderOutput(Yii::$app->view, $model, 'links');

		$this->assertIsArray($rendered);
		$this->assertCount(2, $rendered);
		$this->assertStringContainsString('https://example.com/page', $rendered[0]);
		$this->assertStringContainsString('описание сервиса', $rendered[0]);
		$this->assertStringContainsString('https://test.org/doc', $rendered[1]);

		//пустое значение - пустой список
		$this->assertSame([], (new UrlsType())->renderOutput(Yii::$app->view, new MaintenanceReqs(), 'links'));
	}

	/**
	 * Дефолт BaseType: простейший рендер значения - экранированный текст,
	 * массив значений - список экранированных элементов.
	 */
	public function testBaseTypePlainRender()
	{
		$model = new Techs(['num' => 'ARM-<b>&1']);
		$this->assertSame('ARM-&lt;b&gt;&amp;1', (new StringType())->renderOutput(Yii::$app->view, $model, 'num'));
	}

	/**
	 * Наследники TextType (json/hw-list/soft-list) пока рендерят
	 * стандартным скаляр-рендером, а не текстовым (ntext) рендером
	 * родителя - обогащение появится осознанно на этапе 4в.
	 */
	public function testTextTypeChildrenKeepPlainRender()
	{
		$model = new Techs(['mac' => "aa:bb:cc:dd:ee:ff\n11:22:33:44:55:66"]);
		foreach ([new JsonType(), new HwListType(), new SwListType()] as $type) {
			$rendered = $type->renderOutput(Yii::$app->view, $model, 'mac');
			$this->assertStringNotContainsString('<br', $rendered, get_class($type) . ' не должен наследовать ntext-рендер');
			$this->assertStringContainsString('aa:bb:cc:dd:ee:ff', $rendered);
		}
	}

	/**
	 * MacsType (обогащение 4в): форматированный многострочный вывод -
	 * канонический вид адресов, перенос между строками; работает и от
	 * сырого значения.
	 */
	public function testMacsTypeFormattedMultiline()
	{
		$model = new Techs(['mac' => "aabbccddeeff\n112233445566"]);
		$rendered = (new MacsType())->renderOutput(Yii::$app->view, $model, 'mac');
		$this->assertStringContainsString('AA:BB:CC:DD:EE:FF', $rendered);
		$this->assertStringContainsString('11:22:33:44:55:66', $rendered);
		$this->assertStringContainsString('<br', $rendered);
	}

	/**
	 * LinkType: значение ссылки типом не рендерится (объекты - только через
	 * renderItem), попытка - громкая ошибка использования.
	 */
	public function testLinkTypeThrows()
	{
		$this->expectException(\RuntimeException::class);
		(new LinkType())->renderOutput(Yii::$app->view, new Techs(), 'user_id');
	}

	/**
	 * Строгость: ошибка описания атрибута (тип не резолвится) роняет рендер,
	 * а не проглатывается виджетом - такие ошибки ищутся тестами страниц.
	 */
	public function testUnresolvableAttributeThrows()
	{
		$thrown = null;
		try {
			ModelFieldWidget::renderFieldValue(new Techs(), 'noSuchAttributeAtAll');
		} catch (\Throwable $e) {
			$thrown = $e;
		}
		$this->assertNotNull($thrown, 'ожидалось исключение резолвинга типа');
	}

	/**
	 * ModelFieldWidget, сценарий text: рендер через тип, raw-режим без
	 * обрыва строк (line-nobr) - как прежняя ветка TextFieldWidget.
	 */
	public function testWidgetTextScenario()
	{
		$model = new Acls(['comment' => "строка1\nстрока2"]);
		$out = ModelFieldWidget::widget(['model' => $model, 'field' => 'comment']);

		$this->assertStringContainsString(Yii::$app->formatter->asNtext($model->comment), $out);
		$this->assertStringContainsString('line-nobr', $out);
		$this->assertStringContainsString('<h4', $out); //полная подача с заголовком
	}

	/**
	 * ModelFieldWidget, сценарий urls: элементы-ссылки списком со штатными
	 * разделителями (line-break), как прежняя ветка UrlListWidget.
	 */
	public function testWidgetUrlsScenario()
	{
		$model = new MaintenanceReqs(['links' => "описание https://example.com/page\nhttps://test.org/doc"]);
		$out = ModelFieldWidget::widget(['model' => $model, 'field' => 'links']);

		$this->assertStringContainsString('https://example.com/page', $out);
		$this->assertStringContainsString('https://test.org/doc', $out);
		$this->assertStringContainsString('line-break', $out);
	}

	/**
	 * Починка hardcoded-links: для url-атрибута рендерится значение самого
	 * атрибута (раньше case 'urls' всегда брал $model->links).
	 */
	public function testWidgetUrlAttributeUsesOwnValue()
	{
		$model = new Techs(['url' => 'https://device.local/admin']);
		$out = ModelFieldWidget::renderFieldValue($model, 'url');
		$this->assertStringContainsString('https://device.local/admin', $out);
	}

	/**
	 * ModelFieldWidget, сценарий scalar: генерик-путь не изменился
	 * (тип без renderOutput не вмешивается в вывод значения).
	 */
	public function testWidgetScalarScenario()
	{
		$model = new Techs(['num' => 'ARM-0001']);
		$out = ModelFieldWidget::renderFieldValue($model, 'num');
		$this->assertStringContainsString('ARM-0001', $out);
	}

	/**
	 * Пустое значение: рендер типа не вызывается, подача пустоты - механизм
	 * виджета/ListObjectsWidget (show_empty/message_on_empty); по умолчанию
	 * карточка скрывается целиком, message_on_empty работает.
	 */
	public function testWidgetEmptyValueHidesCard()
	{
		$this->assertSame('', ModelFieldWidget::widget(['model' => new Acls(['comment' => '']), 'field' => 'comment']));
		$this->assertSame('', ModelFieldWidget::widget(['model' => new Techs(), 'field' => 'url']));
		$this->assertSame('', ModelFieldWidget::widget(['model' => new Techs(), 'field' => 'num']));

		$out = ModelFieldWidget::widget([
			'model' => new Techs(), 'field' => 'num',
			'show_empty' => true, 'message_on_empty' => 'не задано',
		]);
		$this->assertStringContainsString('не задано', $out);
	}

	/**
	 * ModelFieldWidget, сценарий link: объект рендерится объектным путём
	 * через renderItem (значение типом не рендерится).
	 */
	public function testWidgetLinkScenario()
	{
		$model = new Services();
		$model->setIsNewRecord(false);
		$model->populateRelation('segment', new Segments(['name' => 'Тестовый сегмент']));

		$out = ModelFieldWidget::renderFieldValue($model, 'segment');
		$this->assertStringContainsString('Тестовый сегмент', $out);
	}
}
