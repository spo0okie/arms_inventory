<?php

namespace tests\unit\helpers;

use app\helpers\WikiLinksScanner;
use Codeception\Test\Unit;

/**
 * Тесты сканера интервики-ссылок ({@see WikiLinksScanner}, страница
 * /web/wiki/interwiki, docs/help/admin/integrations/dokuwiki.md).
 *
 * Проверяются чистые части сканера (без БД): разбор ссылок, обход включений
 * {{page>...}} с подменённым загрузчиком страниц и группировка результата.
 */
class WikiLinksScannerTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** интервики-ссылка распознаётся с подписью и без, shortcut может содержать точку */
	public function testParseInterwikiLinks()
	{
		$links=WikiLinksScanner::parseInterwikiLinks(
			'Смотри [[wp>DokuWiki]] и [[google.maps>Москва|карта города]].'
		);

		$this->assertCount(2,$links);

		$this->assertEquals('wp',$links[0]['shortcut']);
		$this->assertEquals('DokuWiki',$links[0]['target']);
		$this->assertEquals('',$links[0]['title']);
		$this->assertEquals('[[wp>DokuWiki]]',$links[0]['raw']);

		$this->assertEquals('google.maps',$links[1]['shortcut']);
		$this->assertEquals('Москва',$links[1]['target']);
		$this->assertEquals('карта города',$links[1]['title']);
	}

	/** внутренние, внешние ссылки и медиа/включения интервики не являются */
	public function testParseIgnoresNonInterwiki()
	{
		$links=WikiLinksScanner::parseInterwikiLinks(
			'[[namespace:page]] [[http://example.com|сайт]] [[.:relative]] '
			.'{{page>docs:common}} {{wiki:image.png}} [[#section]] '
			.'[[some page > другая]]'
		);

		$this->assertSame([],$links);
	}

	/** якорь секции остаётся частью цели ссылки (страница + #секция) */
	public function testParseKeepsSectionAnchor()
	{
		$links=WikiLinksScanner::parseInterwikiLinks('[[doku>syntax#internal|синтаксис]]');

		$this->assertCount(1,$links);
		$this->assertEquals('doku',$links[0]['shortcut']);
		$this->assertEquals('syntax#internal',$links[0]['target']);
	}

	/** ссылки собираются и из страниц, включённых через {{page>...}} - рекурсивно */
	public function testScanTextFollowsIncludes()
	{
		$scanner=new WikiLinksScanner();
		$scanner->pageFetcher=function($page) {
			$pages=[
				//относительное включение .deeper от страницы docs:common -> docs:deeper
				'docs:common'=>'Общее описание [[wp>Общая]] {{page>.deeper}}',
				'docs:deeper'=>'Подробности [[doku>syntax]]',
			];
			return $pages[$page]??false;
		};

		$found=$scanner->scanText(
			'Поле объекта [[wp>Локальная]] {{page>docs:common}}',
			['attribute'=>'notepad']
		);

		$targets=array_column($found,'target');
		$this->assertEquals(['Локальная','Общая','syntax'],$targets);

		//цепочка включений: ссылка из поля - без via, из вложенных страниц - с via
		$this->assertSame([],$found[0]['via']);
		$this->assertSame(['docs:common'],$found[1]['via']);
		$this->assertSame(['docs:common','docs:deeper'],$found[2]['via']);

		//контекст источника доезжает до каждой ссылки
		$this->assertEquals('notepad',$found[2]['attribute']);

		$this->assertEquals(2,$scanner->getTotals()['includes']);
		$this->assertSame([],$scanner->getFailures());
	}

	/** followIncludes=false - только само поле, без запросов к wiki */
	public function testScanTextWithoutIncludes()
	{
		$scanner=new WikiLinksScanner();
		$scanner->followIncludes=false;
		$scanner->pageFetcher=function($page) {
			$this->fail('При followIncludes=false страницы wiki запрашиваться не должны');
		};

		$found=$scanner->scanText('[[wp>Локальная]] {{page>docs:common}}');

		$this->assertCount(1,$found);
		$this->assertEquals('Локальная',$found[0]['target']);
	}

	/** глубина обхода ограничена maxIncludeDepth */
	public function testScanTextRespectsDepthLimit()
	{
		$scanner=new WikiLinksScanner();
		$scanner->maxIncludeDepth=1;
		$scanner->pageFetcher=function($page) {
			$pages=[
				'level:one'=>'[[wp>Первая]] {{page>level:two}}',
				'level:two'=>'[[wp>Вторая]]',
			];
			return $pages[$page]??false;
		};

		$found=$scanner->scanText('{{page>level:one}}');

		$this->assertEquals(['Первая'],array_column($found,'target'));
	}

	/** циклические включения не роняют обход */
	public function testScanTextSurvivesIncludeCycle()
	{
		$scanner=new WikiLinksScanner();
		$scanner->pageFetcher=function($page) {
			$pages=[
				'a:page'=>'[[wp>A]] {{page>b:page}}',
				'b:page'=>'[[wp>B]] {{page>a:page}}',
			];
			return $pages[$page]??false;
		};

		$found=$scanner->scanText('{{page>a:page}}');

		$this->assertEquals(['A','B'],array_column($found,'target'));
	}

	/** недоступная страница не теряется молча, а попадает в список ошибок */
	public function testScanTextRecordsUnreachablePage()
	{
		$scanner=new WikiLinksScanner();
		$scanner->pageFetcher=function($page) { return false; };

		$found=$scanner->scanText('[[wp>Локальная]] {{page>docs:missing}}',[
			'class'=>'app\models\Comps','id'=>7,'attribute'=>'notepad',
		]);

		$this->assertCount(1,$found);
		$this->assertArrayHasKey('docs:missing',$scanner->getFailures());
		$this->assertStringContainsString('notepad',$scanner->getFailures()['docs:missing']);
	}

	/** группировка: по shortcut и странице, группы по убыванию количества ссылок */
	public function testGroup()
	{
		$usages=[
			['shortcut'=>'wp','target'=>'Бета','title'=>'','raw'=>'','via'=>[]],
			['shortcut'=>'doku','target'=>'syntax','title'=>'','raw'=>'','via'=>[]],
			['shortcut'=>'wp','target'=>'Альфа','title'=>'','raw'=>'','via'=>[]],
			['shortcut'=>'wp','target'=>'Альфа','title'=>'вторая','raw'=>'','via'=>[]],
		];

		$groups=WikiLinksScanner::group($usages);

		//группы отсортированы по количеству ссылок (wp - 3, doku - 1)
		$this->assertEquals(['wp','doku'],array_keys($groups));
		$this->assertEquals(3,$groups['wp']['count']);
		//страницы внутри группы - по алфавиту
		$this->assertEquals(['Альфа','Бета'],array_keys($groups['wp']['targets']));
		$this->assertEquals(2,$groups['wp']['targets']['Альфа']['count']);
		$this->assertCount(2,$groups['wp']['targets']['Альфа']['usages']);
		$this->assertEquals('вторая',$groups['wp']['targets']['Альфа']['usages'][1]['title']);
	}
}
