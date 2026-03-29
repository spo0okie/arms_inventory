<?php

namespace tests\unit\generation;

use app\generation\context\AttributeContext;
use app\generation\context\GenerationContext;
use app\models\AccessTypes;
use app\types\TextType;
use Codeception\Test\Unit;

class TypeGenerationTest extends Unit
{
	/** @var \UnitTester */
	protected $tester;

	public function testTypeCanGenerate(): void
	{
		\Helper\Yii2::initFromFileName('test-console.php');
		\Helper\Database::loadSqlDump();

		$model = new AccessTypes();
		$attribute = 'notepad';
		$attributeData = $model->getAttributeData($attribute);

		$typeInstance = $model->getAttributeTypeForGeneration($attribute);
		
		$this->assertInstanceOf(TextType::class, $typeInstance);

		$context = new AttributeContext(
			attribute: $attribute,
			attributeData: $attributeData,
			empty: false,
			model: $model,
			generationContext: new GenerationContext(seed: 321)
		);

		$value = $typeInstance->generate($context);
		$this->assertNotNull($value);
	}
}