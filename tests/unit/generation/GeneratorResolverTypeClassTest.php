<?php

namespace tests\unit\generation;

use app\generation\context\AttributeContext;
use app\generation\context\GenerationContext;
use app\generation\generators\GeneratorResolver;
use app\models\AccessTypes;
use app\types\TextType;
use Codeception\Test\Unit;

class GeneratorResolverTypeClassTest extends Unit
{
	/** @var \UnitTester */
	protected $tester;

	public function testResolveUsesTypeClass(): void
	{
		\Helper\Yii2::initFromFileName('test-console.php');
		\Helper\Database::loadSqlDump();

		$model = new AccessTypes();
		$attribute = 'notepad';
		$attributeData = $model->getAttributeData($attribute);

		$context = new AttributeContext(
			attribute: $attribute,
			attributeData: $attributeData,
			empty: false,
			model: $model,
			generationContext: new GenerationContext(seed: 321)
		);

		$generator = GeneratorResolver::resolve($context);
		$this->assertInstanceOf(TextType::class, $generator);
	}
}