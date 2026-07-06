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
}
