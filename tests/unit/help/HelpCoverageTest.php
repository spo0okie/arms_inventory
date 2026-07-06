<?php

namespace tests\unit\help;

use app\helpers\ModelHelper;
use Codeception\Test\Unit;

/**
 * Сторож покрытия лёгкого слоя документации (plans/help-docs.md, этап 4).
 *
 * Правило: у каждой модели должно быть modelDescription(), у каждого safe
 * атрибута — подсказка хотя бы из одного источника (hint из attributeData
 * или inputHint() типа).
 *
 * Существующие пробелы зафиксированы в coverage-baseline.txt и не валят тест;
 * НОВЫЙ атрибут/модель без описания — валит. Baseline может только уменьшаться:
 * когда пробел закрыт, его нужно удалить из baseline (тест напомнит).
 */
class HelpCoverageTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	const BASELINE = __DIR__ . '/coverage-baseline.txt';

	/**
	 * Собирает текущие пробелы покрытия:
	 * 'Class' - нет modelDescription, 'Class::attr' - нет подсказки.
	 * @return string[]
	 */
	protected function collectMissing(): array
	{
		$missing = [];
		foreach (ModelHelper::getModelClasses() as $class) {
			//служебные зеркала не документируем: History повторяет мастер-модель,
			//Search наследует подсказки базовой модели
			if (preg_match('/(History|Search)$/', $class)) continue;

			try {
				$model = new $class();
			} catch (\Throwable $e) {
				continue;
			}

			if (!$class::modelDescription()) $missing[] = $class;

			foreach ($model->safeAttributes() as $attr) {
				try {
					$hint = $model->getAttributeHint($attr);
					$typeHint = $model->getAttributeTypeHint($attr, 'inputHint');
				} catch (\Throwable $e) {
					$hint = $typeHint = null;
				}
				if (!$hint && !$typeHint) $missing[] = $class . '::' . $attr;
			}
		}
		sort($missing);
		return $missing;
	}

	/**
	 * @return string[] пробелы, зафиксированные в baseline
	 */
	protected function baseline(): array
	{
		if (!is_file(static::BASELINE)) return [];
		$content = str_replace("\xEF\xBB\xBF", '', file_get_contents(static::BASELINE)); //без BOM
		$lines = preg_split('/\R/', $content, -1, PREG_SPLIT_NO_EMPTY);
		return array_values(array_filter(
			array_map('trim', $lines),
			fn($line) => $line !== '' && !str_starts_with($line, '#')
		));
	}

	public function testHelpCoverage()
	{
		\Helper\Yii2::initFromFileName('test-console.php');
		\Helper\Database::loadSqlDump();

		$missing = $this->collectMissing();
		$baseline = $this->baseline();

		$new = array_diff($missing, $baseline);
		$fixed = array_diff($baseline, $missing);

		$this->assertEmpty(
			$new,
			"Новые пробелы покрытия документацией (добавьте hint в attributeData, "
			."inputHint() типу или modelDescription() модели; про конвенцию см. docs/help/README.md):\n"
			.implode("\n", $new)
		);

		$this->assertEmpty(
			$fixed,
			"Эти пробелы уже закрыты — удалите их из tests/unit/help/coverage-baseline.txt "
			."(baseline может только уменьшаться):\n"
			.implode("\n", $fixed)
		);
	}
}
