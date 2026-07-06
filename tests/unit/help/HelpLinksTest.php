<?php

namespace tests\unit\help;

use app\helpers\DocsHelper;
use Codeception\Test\Unit;
use Yii;

/**
 * Сторож ссылок слоя 2 (plans/help-docs.md, этап 4): относительные ссылки
 * и картинки в MD-страницах должны резолвиться в существующие файлы внутри
 * docs/help — иначе они битые либо на GitHub, либо в приложении.
 */
class HelpLinksTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	public function testRelativeLinksResolve()
	{
		\Helper\Yii2::initFromFileName('test-console.php');

		$roots = [Yii::getAlias('@app/docs/help')];
		$pages = DocsHelper::pagesMap($roots);
		$broken = [];

		foreach ($pages as $path => $file) {
			$content = file_get_contents($file);
			//markdown-ссылки и картинки: [текст](цель), ![alt](цель)
			if (!preg_match_all('/!?\[[^\]]*\]\(([^)\s]+)\)/u', $content, $matches)) continue;

			foreach ($matches[1] as $target) {
				//внешние URL, якоря и абсолютные пути не проверяем
				if (preg_match('~^([a-z][a-z0-9+.-]*:|//|/|#)~i', $target)) continue;

				//отрезаем якорь
				if (($pos = strpos($target, '#')) !== false) $target = substr($target, 0, $pos);
				if ($target === '') continue;

				$resolved = DocsHelper::normalizeRelPath(
					DocsHelper::relDir($path) . '/' . rawurldecode($target)
				);

				if ($resolved === null) {
					$broken[] = "$path: ссылка '$target' выходит за пределы docs/help "
						."(в приложении будет битой — используйте абсолютный URL)";
					continue;
				}

				if (!DocsHelper::findFile($resolved, $roots)) {
					$broken[] = "$path: ссылка '$target' -> '$resolved' не существует";
				}
			}
		}

		$this->assertEmpty(
			$broken,
			"Битые относительные ссылки в документации:\n".implode("\n", $broken)
		);
	}
}
