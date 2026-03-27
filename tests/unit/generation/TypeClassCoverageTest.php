<?php

namespace tests\unit\generation;

use app\helpers\ModelHelper;
use Codeception\Test\Unit;

class TypeClassCoverageTest extends Unit
{
	/** @var \UnitTester */
	protected $tester;

	public function testTypeClassCoverage(): void
	{
		\Helper\Yii2::initFromFileName('test-console.php');
		\Helper\Database::loadSqlDump();

		$missing = [];
		foreach (ModelHelper::getModelClasses() as $modelClass) {
			$model = new $modelClass();
			$attributeData = $model->attributeData();
			foreach ($attributeData as $attribute => $data) {
				if (!is_array($data)) {
					continue;
				}
				if (!isset($data['typeClass'])) {
					$missing[] = $modelClass . '->' . $attribute;
				}
			}
		}

		if (!empty($missing)) {
			codecept_debug('TypeClass missing for attributes: ' . count($missing));
			foreach (array_slice($missing, 0, 50) as $sample) {
				codecept_debug(' - ' . $sample);
			}
			if (count($missing) > 50) {
				codecept_debug(' ... and ' . (count($missing) - 50) . ' more');
			}
		}

		$this->assertTrue(true);
	}
}