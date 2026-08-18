<?php

namespace tests\unit\helpers;

use app\helpers\WikiLinksScanner;
use Codeception\Test\Unit;

/**
 * Тесты сканера ссылок инвентаризации в wiki ({@see WikiLinksScanner},
 * страница /web/wiki/links, docs/help/admin/integrations/dokuwiki.md).
 *
 * Проверяются чистые части сканера (без БД): классификация ссылок DokuWiki,
 * разбор URL страниц wiki, обход включений {{page>...}} с подменённым
 * загрузчиком страниц и группировка интервики-ссылок.
 */
class WikiLinksScannerTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** сканер с фиксированным адресом wiki и без сети */
	protected function scanner(array $pages=[]): WikiLinksScanner
	{
		$scanner=new WikiLinksScanner();
		$scanner->wikiUrl='https://wiki.example.local/';
		$scanner->pageFetcher=function($page) use ($pages) {
			return $pages[$page]??false;
		};
		return $scanner;
	}

	/** виды ссылок определяются так же, как их видит парсер DokuWiki */
	public function testParseLinksClassification()
	{
		$links=WikiLinksScanner::parseLinks(
			'[[services:inventory|инвентаризация]] [[wp>DokuWiki]] [[https://example.com]] '
			.'[[\\\\server\\share\\file]] [[admin@example.com]] [[#секция]] [[.:соседняя]]'
		);

		$this->assertEquals(
			['internal','interwiki','external','share','email','anchor','internal'],
			array_column($links,'kind')
		);
		$this->assertEquals('services:inventory',$links[0]['target']);
		$this->assertEquals('инвентаризация',$links[0]['title']);
		$this->assertEquals('wp',$links[1]['shortcut']);
	}

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

	/** якорь секции остаётся частью цели интервики-ссылки (страница + #секция) */
	public function testParseKeepsSectionAnchor()
	{
		$links=WikiLinksScanner::parseInterwikiLinks('[[doku>syntax#internal|синтаксис]]');

		$this->assertCount(1,$links);
		$this->assertEquals('doku',$links[0]['shortcut']);
		$this->assertEquals('syntax#internal',$links[0]['target']);
	}

	/** URL страницы wiki распознаётся во всех употребимых формах */
	public function testUrlToWikiPage()
	{
		$wiki='https://wiki.example.local/';

		//классический адрес и адрес с дополнительными параметрами/якорем
		$this->assertEquals('net:vlans',
			WikiLinksScanner::urlToWikiPage($wiki.'doku.php?id=net:vlans',$wiki));
		$this->assertEquals('net:vlans',
			WikiLinksScanner::urlToWikiPage($wiki.'doku.php?id=net:vlans&do=edit#схема',$wiki));

		//человекочитаемые адреса (rewrite): через двоеточие и через слэш
		$this->assertEquals('net:vlans',
			WikiLinksScanner::urlToWikiPage($wiki.'net:vlans',$wiki));
		$this->assertEquals('net:vlans',
			WikiLinksScanner::urlToWikiPage($wiki.'net/vlans',$wiki));

		//кириллица в адресе приезжает раскодированной
		$this->assertEquals('сети:влан',
			WikiLinksScanner::urlToWikiPage($wiki.'doku.php?id='.rawurlencode('сети:влан'),$wiki));

		//корень wiki - стартовая страница
		$this->assertEquals('start',WikiLinksScanner::urlToWikiPage($wiki,$wiki));

		//чужой адрес и служебные пути страницами не являются
		$this->assertNull(WikiLinksScanner::urlToWikiPage('https://example.com/page',$wiki));
		$this->assertNull(WikiLinksScanner::urlToWikiPage($wiki.'lib/exe/fetch.php?media=x.png',$wiki));
	}

	/** в поле собираются вики-ссылки, включения и URL на страницы wiki */
	public function testScanTextCollectsWikiRefs()
	{
		$scanner=$this->scanner(['docs:common'=>'Внутри [[docs:deeper]]']);

		$scanner->scanText(
			'Регламент [[services:inventory|инвентаризация]], '
			.'сети [[https://wiki.example.local/doku.php?id=net:vlans]] '
			.'{{page>docs:common}}',
			['class'=>'app\models\Services','id'=>5,'attribute'=>'notepad']
		);

		$pages=$scanner->getWikiPages();
		$this->assertEquals(
			['docs:common','docs:deeper','net:vlans','services:inventory'],
			array_keys($pages)
		);

		//вид ссылки различается: обычная, включение, адрес
		$this->assertEquals([WikiLinksScanner::KIND_LINK=>1],$pages['services:inventory']['kinds']);
		$this->assertEquals([WikiLinksScanner::KIND_INCLUDE=>1],$pages['docs:common']['kinds']);
		$this->assertEquals([WikiLinksScanner::KIND_URL=>1],$pages['net:vlans']['kinds']);

		//ссылка со страницы, втянутой включением, помечена цепочкой
		$this->assertSame(['docs:common'],$pages['docs:deeper']['usages'][0]['via']);
		$this->assertSame([],$pages['services:inventory']['usages'][0]['via']);

		//контекст источника доезжает до каждой находки
		$this->assertEquals('notepad',$pages['docs:deeper']['usages'][0]['attribute']);
		$this->assertEquals('инвентаризация',$pages['services:inventory']['usages'][0]['title']);

		$totals=$scanner->getTotals();
		$this->assertEquals(4,$totals['refs']);
		$this->assertEquals(1,$totals['nested']);
	}

	/** относительные ссылки внутри включённой страницы разрешаются от неё самой */
	public function testRelativeLinksResolveFromIncludedPage()
	{
		$scanner=$this->scanner(['docs:common'=>'Рядом [[.соседняя]]']);

		$scanner->scanText('{{page>docs:common}}');

		$this->assertArrayHasKey('docs:соседняя',$scanner->getWikiPages());
	}

	/** includeNested=false - только то, что написано в самой инвентаризации */
	public function testNestedFindingsCanBeSkipped()
	{
		$scanner=$this->scanner(['docs:common'=>'Внутри [[docs:deeper]]']);
		$scanner->includeNested=false;

		$scanner->scanText('[[services:inventory]] {{page>docs:common}}');

		//включение написано в поле (учитывается), ссылка внутри страницы - нет
		$this->assertEquals(
			['docs:common','services:inventory'],
			array_keys($scanner->getWikiPages())
		);
		$this->assertEquals(0,$scanner->getTotals()['nested']);
	}

	/** followIncludes=false - только само поле, без запросов к wiki */
	public function testScanTextWithoutIncludes()
	{
		$scanner=new WikiLinksScanner();
		$scanner->wikiUrl='https://wiki.example.local/';
		$scanner->followIncludes=false;
		$scanner->pageFetcher=function($page) {
			$this->fail('При followIncludes=false страницы wiki запрашиваться не должны');
		};

		$scanner->scanText('[[services:inventory]] {{page>docs:common}}');

		$this->assertEquals(['services:inventory'],array_keys($scanner->getWikiPages()));
	}

	/** глубина обхода ограничена maxIncludeDepth */
	public function testScanTextRespectsDepthLimit()
	{
		$scanner=$this->scanner([
			'level:one'=>'[[first:page]] {{page>level:two}}',
			'level:two'=>'[[second:page]]',
		]);
		$scanner->maxIncludeDepth=1;

		$scanner->scanText('{{page>level:one}}');

		//вложенное включение уже не раскрывается
		$this->assertEquals(['first:page','level:one'],array_keys($scanner->getWikiPages()));
	}

	/** циклические включения не роняют обход */
	public function testScanTextSurvivesIncludeCycle()
	{
		$scanner=$this->scanner([
			'a:page'=>'[[link:a]] {{page>b:page}}',
			'b:page'=>'[[link:b]] {{page>a:page}}',
		]);

		$scanner->scanText('{{page>a:page}}');

		$this->assertEquals(
			['a:page','b:page','link:a','link:b'],
			array_keys($scanner->getWikiPages())
		);
	}

	/** недоступная страница: включение учтено, а сама страница попала в список ошибок */
	public function testScanTextRecordsUnreachablePage()
	{
		$scanner=$this->scanner();	//любую страницу wiki "не отдаёт"

		$scanner->scanText('{{page>docs:missing}}',[
			'class'=>'app\models\Comps','id'=>7,'attribute'=>'notepad',
		]);

		$this->assertArrayHasKey('docs:missing',$scanner->getWikiPages());
		$this->assertArrayHasKey('docs:missing',$scanner->getFailures());
		$this->assertStringContainsString('notepad',$scanner->getFailures()['docs:missing']);
	}

	/** интервики-ссылки идут отдельным списком и группируются по shortcut */
	public function testInterwikiGrouping()
	{
		$scanner=$this->scanner();

		$scanner->scanText('[[wp>Бета]] [[doku>syntax]] [[wp>Альфа]] [[wp>Альфа|вторая]]');

		//в страницы этой wiki интервики не попадают
		$this->assertSame([],$scanner->getWikiPages());

		$groups=$scanner->getInterwiki();
		//группы отсортированы по количеству ссылок (wp - 3, doku - 1)
		$this->assertEquals(['wp','doku'],array_keys($groups));
		$this->assertEquals(3,$groups['wp']['count']);
		//страницы внутри группы - по алфавиту
		$this->assertEquals(['Альфа','Бета'],array_keys($groups['wp']['targets']));
		$this->assertEquals(2,$groups['wp']['targets']['Альфа']['count']);
		$this->assertEquals('вторая',$groups['wp']['targets']['Альфа']['usages'][1]['title']);
	}
}
