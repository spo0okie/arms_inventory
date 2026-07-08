<?php

namespace tests\unit\helpers;

use app\helpers\DocsHelper;
use Codeception\Test\Unit;

/**
 * Тесты хелпера встроенной документации (plans/help-docs.md, этап 1):
 * нормализация путей (включая защиту от выхода за корень), цепочка
 * override-корней и переписывание относительных ссылок в HTML.
 */
class DocsHelperTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var string временный корень базовой документации */
	protected $baseRoot;

	/** @var string временный корень переопределений заказчика */
	protected $overrideRoot;

	protected function _before()
	{
		$this->baseRoot = codecept_output_dir() . 'docs-helper-test/base';
		$this->overrideRoot = codecept_output_dir() . 'docs-helper-test/override';
		mkdir($this->baseRoot . '/models', 0777, true);
		mkdir($this->overrideRoot . '/models', 0777, true);

		file_put_contents($this->baseRoot . '/README.md', "# Оглавление\n\nтекст");
		file_put_contents($this->baseRoot . '/models/comps.md', "# Компьютеры (база)\n");
		file_put_contents($this->overrideRoot . '/models/comps.md', "# Компьютеры (заказчик)\n");
		file_put_contents($this->overrideRoot . '/models/local-only.md', "# Локальная страница\n");
	}

	protected function _after()
	{
		$this->removeDir(codecept_output_dir() . 'docs-helper-test');
	}

	protected function removeDir(string $dir)
	{
		if (!is_dir($dir)) return;
		foreach (scandir($dir) as $item) {
			if ($item === '.' || $item === '..') continue;
			$path = $dir . '/' . $item;
			is_dir($path) ? $this->removeDir($path) : unlink($path);
		}
		rmdir($dir);
	}

	/**
	 * Нормализация: слэши, '.', '..' в пределах корня.
	 */
	public function testNormalizeRelPath()
	{
		$this->assertEquals('models/comps.md', DocsHelper::normalizeRelPath('models/comps.md'));
		$this->assertEquals('models/comps.md', DocsHelper::normalizeRelPath('./models/./comps.md'));
		$this->assertEquals('models/comps.md', DocsHelper::normalizeRelPath('models\\comps.md'));
		$this->assertEquals('comps.md', DocsHelper::normalizeRelPath('models/../comps.md'));
		$this->assertEquals('models/comps.md', DocsHelper::normalizeRelPath('/models/comps.md'));
	}

	/**
	 * Выход за корень документации недопустим.
	 */
	public function testNormalizeRelPathTraversal()
	{
		$this->assertNull(DocsHelper::normalizeRelPath('../config/params.php'));
		$this->assertNull(DocsHelper::normalizeRelPath('models/../../config/params.php'));
		$this->assertNull(DocsHelper::normalizeRelPath(''));
	}

	/**
	 * Страница ищется по цепочке корней: override приоритетнее базы,
	 * отсутствующие в override страницы берутся из базы.
	 */
	public function testFindPageOverrideChain()
	{
		$roots = [$this->overrideRoot, $this->baseRoot];

		//override перекрывает базу
		$this->assertStringContainsString('override', DocsHelper::findPage('models/comps.md', $roots));
		//страница только в базе
		$this->assertStringContainsString('base', DocsHelper::findPage('README.md', $roots));
		//страница только в override
		$this->assertStringContainsString('override', DocsHelper::findPage('models/local-only.md', $roots));
		//отсутствующая страница
		$this->assertNull(DocsHelper::findPage('no-such.md', $roots));
		//не-md файл страницей не является
		$this->assertNull(DocsHelper::findPage('models/comps.txt', $roots));
	}

	/**
	 * findImage допускает только картинки и тоже не выпускает за корень.
	 */
	public function testFindImage()
	{
		file_put_contents($this->baseRoot . '/img.png', 'fake');
		$roots = [$this->baseRoot];

		$this->assertNotNull(DocsHelper::findImage('img.png', $roots));
		$this->assertNull(DocsHelper::findImage('README.md', $roots));
		$this->assertNull(DocsHelper::findImage('../img.png', $roots));
	}

	/**
	 * Заголовок страницы - первый H1, при его отсутствии - имя файла.
	 */
	public function testPageTitle()
	{
		$this->assertEquals('Оглавление', DocsHelper::pageTitle($this->baseRoot . '/README.md'));

		file_put_contents($this->baseRoot . '/no-title.md', "просто текст\nбез заголовка");
		$this->assertEquals('no-title', DocsHelper::pageTitle($this->baseRoot . '/no-title.md'));
	}

	/**
	 * Относительные ссылки на .md и картинки переписываются на маршруты
	 * документации с учётом каталога текущей страницы; якоря сохраняются.
	 */
	public function testRewriteHtmlLinks()
	{
		$urlBuilder = function ($action, $relPath, $anchor) {
			return "[$action:$relPath]$anchor";
		};

		$html = '<a href="comps.md">сосед</a>'
			. ' <a href="../guides/start.md#step2">сценарий</a>'
			. ' <img src="../img/pic.png">';

		$this->assertEquals(
			'<a href="[page:models/comps.md]">сосед</a>'
			. ' <a href="[page:guides/start.md]#step2">сценарий</a>'
			. ' <img src="[img:img/pic.png]">',
			DocsHelper::rewriteHtmlLinks($html, 'models', $urlBuilder)
		);
	}

	/**
	 * Абсолютные URL, якоря, mailto и выходящие за корень ссылки не трогаем.
	 */
	public function testRewriteHtmlLinksUntouched()
	{
		$urlBuilder = function ($action, $relPath, $anchor) {
			return "[$action:$relPath]$anchor";
		};

		$untouched = [
			'<a href="https://example.com/page.md">внешняя</a>',
			'<a href="//example.com/page.md">протокол-относительная</a>',
			'<a href="/absolute/path.md">абсолютный путь</a>',
			'<a href="#anchor">якорь</a>',
			'<a href="mailto:user@example.com">почта</a>',
			'<a href="../../outside.md">за корнем</a>',
			'<a href="file.txt">не md и не картинка</a>',
		];

		foreach ($untouched as $html) {
			$this->assertEquals($html, DocsHelper::rewriteHtmlLinks($html, '', $urlBuilder));
		}
	}

	/**
	 * Список страниц: слияние корней, override приоритетнее при совпадении путей.
	 */
	public function testPagesList()
	{
		$pages = DocsHelper::pagesList([$this->overrideRoot, $this->baseRoot]);
		$byPath = [];
		foreach ($pages as $page) $byPath[$page['path']] = $page['title'];

		$this->assertEquals('Оглавление', $byPath['README.md']);
		$this->assertEquals('Компьютеры (заказчик)', $byPath['models/comps.md']);
		$this->assertEquals('Локальная страница', $byPath['models/local-only.md']);
		$this->assertCount(3, $pages);
	}

	/** MD-текст модельной страницы для тестов секций */
	const SECTIONED_MD = "# Заголовок\n\nпреамбула первая\n\nпреамбула [сосед](other.md)\n\n"
		."## Список\n\nтело списка\n\n### Подраздел списка\n\nвнутри подраздела\n\n"
		."## Просмотр\n\nтело просмотра";

	/**
	 * Выделение фрагментов: преамбула без H1, секция с H3 внутри,
	 * последняя секция файла, отсутствующая секция.
	 */
	public function testExtractSection()
	{
		//преамбула: H1 отброшен, до первого H2
		$preamble = DocsHelper::extractSection(static::SECTIONED_MD, null);
		$this->assertStringContainsString('преамбула первая', $preamble);
		$this->assertStringNotContainsString('Заголовок', $preamble);
		$this->assertStringNotContainsString('тело списка', $preamble);

		//секция с H3-подразделом внутри (H3 - не граница)
		$list = DocsHelper::extractSection(static::SECTIONED_MD, 'Список');
		$this->assertStringContainsString('тело списка', $list);
		$this->assertStringContainsString('внутри подраздела', $list);
		$this->assertStringNotContainsString('тело просмотра', $list);
		$this->assertStringNotContainsString('## Список', $list);

		//последняя секция файла
		$view = DocsHelper::extractSection(static::SECTIONED_MD, 'Просмотр');
		$this->assertStringContainsString('тело просмотра', $view);
		$this->assertStringNotContainsString('тело списка', $view);

		//отсутствующая секция
		$this->assertEquals('', DocsHelper::extractSection(static::SECTIONED_MD, 'Удаление'));
	}

	/**
	 * renderSection: markdown в HTML, ссылки на MD - модалками
	 * (класс open-in-modal-form), отсутствующая секция - пустая строка.
	 */
	public function testRenderSection()
	{
		$file = $this->baseRoot . '/models/sectioned.md';
		file_put_contents($file, static::SECTIONED_MD);

		$preamble = DocsHelper::renderSection($file, 'models/sectioned.md', null);
		$this->assertStringContainsString('преамбула первая', $preamble);
		$this->assertStringContainsString('open-in-modal-form', $preamble);
		$this->assertStringContainsString('models%2Fother.md', urlencode(urldecode($preamble)));

		$this->assertEquals('', DocsHelper::renderSection($file, 'models/sectioned.md', 'Удаление'));
	}

	/**
	 * Сторож mermaid: fenced-блок ```mermaid должен доезжать до
	 * <code class="mermaid"> в HTML (mermaid-init.js цепляется за него).
	 * Ловит молчаливую поломку при апгрейде markdown-парсера.
	 */
	public function testMermaidFencedBlockSurvives()
	{
		$file = $this->baseRoot . '/models/diagram.md';
		file_put_contents($file, "# T\n\nтекст\n\n```mermaid\ngraph TD\n  A-->B\n```\n");

		$html = DocsHelper::renderSection($file, 'models/diagram.md', null);
		$this->assertStringContainsString('code class="mermaid"', $html);
		$this->assertStringContainsString('graph TD', $html);
	}

	/**
	 * Кэш рендера: инвалидация по filemtime.
	 */
	public function testRenderSectionCache()
	{
		$file = $this->baseRoot . '/models/cached.md';
		file_put_contents($file, "# T\n\nстарый текст\n");
		touch($file, time() - 10);
		clearstatcache();

		$this->assertStringContainsString('старый текст',
			DocsHelper::renderSection($file, 'models/cached.md', null));

		//то же содержимое+mtime - из кэша (подменяем файл, mtime сохраняем)
		$mtime = filemtime($file);
		file_put_contents($file, "# T\n\nновый текст\n");
		touch($file, $mtime);
		clearstatcache();
		$this->assertStringContainsString('старый текст',
			DocsHelper::renderSection($file, 'models/cached.md', null));

		//новый mtime - кэш инвалидирован
		touch($file, $mtime + 5);
		clearstatcache();
		$this->assertStringContainsString('новый текст',
			DocsHelper::renderSection($file, 'models/cached.md', null));
	}
}
