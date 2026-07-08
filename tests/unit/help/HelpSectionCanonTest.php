<?php

namespace tests\unit\help;

use app\helpers\DocsHelper;
use Codeception\Test\Unit;
use Yii;

/**
 * Сторож канона секций модельных страниц (plans/help-inline.md):
 * H2-заголовки «Список/Просмотр/Добавление/Редактирование/Удаление»
 * адресуются трансклюзией по точному тексту, поэтому near-miss
 * (опечатка, регистр, лишние пробелы) молча выключил бы панель.
 * Неканонические H2 легальны (показываются только в /docs и на GitHub).
 */
class HelpSectionCanonTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	public function testCanonSectionsExactness()
	{
		\Helper\Yii2::initFromFileName('test-console.php');

		$pages = DocsHelper::pagesMap([Yii::getAlias('@app/docs/help')]);
		$problems = [];

		foreach ($pages as $path => $file) {
			if (!preg_match('~^models/~', $path)) continue;

			foreach (preg_split('/\R/u', file_get_contents($file)) as $line) {
				if (!preg_match('/^##(?!#)\s*(.+?)\s*$/u', $line, $matches)) continue;
				$heading = $matches[1];

				foreach (DocsHelper::CANON_SECTIONS as $canon) {
					if ($heading === $canon) continue 2; //канон, всё хорошо

					//близкий к канону, но не равный - вероятная опечатка
					if (levenshtein(mb_strtolower($heading), mb_strtolower($canon)) <= 2) {
						$problems[] = "$path: заголовок '## $heading' похож на канонический "
							."'## $canon' — опечатка молча выключит панель (DocsPanelWidget)";
						continue 2;
					}
				}
			}
		}

		$this->assertEmpty($problems, "Near-miss канонических секций:\n".implode("\n", $problems));
	}
}
