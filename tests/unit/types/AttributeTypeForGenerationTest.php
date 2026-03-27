<?php

namespace tests\unit\types;

use app\models\AccessTypes;
use app\types\StringType;
use app\types\TextType;
use Codeception\Test\Unit;

class AttributeTypeForGenerationTest extends Unit
{
	/** @var \UnitTester */
	protected $tester;

	public function testGetAttributeTypeForGeneration(): void
	{
		\Helper\Yii2::initFromFileName('test-console.php');
		\Helper\Database::loadSqlDump();

		$model = new AccessTypes();
		$type = $model->getAttributeTypeForGeneration('notepad');
		$this->assertInstanceOf(TextType::class, $type);

		$commentType = $model->getAttributeTypeForGeneration('comment');
		$this->assertInstanceOf(StringType::class, $commentType);
	}
}