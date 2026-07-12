<?php

namespace tests\unit\help;

use app\helpers\ModelHelper;
use Codeception\Test\Unit;

/**
 * Сторож против дублей типовых подсказок в тултипах.
 *
 * Сборщик тултипа (AttributeDataModelTrait) САМ добавляет к hint/indexHint
 * типовые части — searchHint (QueryHelper::$*SearchHint или searchHint()
 * типа) в поисковом контексте и inputHint() типа в форме. Если модель
 * вручную дописывает тот же текст в hint/indexHint, пользователь видит
 * подсказку дважды (найдено на login-journal: «Время входа (корр.)»).
 *
 * Правило: hint/indexHint/viewHint в attributeData() не должны содержать
 * текст типовых поисковых подсказок.
 */
class HelpNoTypeDuplicationTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	//общее начало всех QueryHelper::$*SearchHint
	const MARKER = 'Можно делать сложные запросы';

	public function testNoSearchHintDuplication()
	{
		\Helper\Yii2::initFromFileName('test-console.php');

		$problems = [];
		foreach (ModelHelper::getModelClasses() as $class) {
			if (preg_match('/(History|Search)$/', $class)) continue;
			try {
				$model = new $class();
				$data = $model->attributeData();
			} catch (\Throwable $e) {
				continue;
			}
			if (!is_array($data)) continue;

			foreach ($data as $attr => $item) {
				if (!is_array($item)) continue;
				foreach (['hint', 'indexHint', 'viewHint'] as $key) {
					if (isset($item[$key])
						&& is_string($item[$key])
						&& str_contains($item[$key], static::MARKER)
					) {
						$problems[] = "$class::$attr [$key] содержит типовую поисковую подсказку — "
							. 'сборщик тултипа добавит её сам, в тултипе будет дубль';
					}
				}
			}
		}

		$this->assertEmpty(
			$problems,
			"Дубли типовых подсказок в attributeData:\n" . implode("\n", $problems)
		);
	}
}
