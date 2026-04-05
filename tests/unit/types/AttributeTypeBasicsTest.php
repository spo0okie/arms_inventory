<?php

namespace tests\unit\types;

use app\generation\context\AttributeContext;
use app\generation\context\GenerationContext;
use app\models\AccessTypes;
use app\types\BooleanType;
use app\types\DateType;
use app\types\DatetimeType;
use app\types\IntegerType;
use app\types\IpsType;
use app\types\JsonType;
use app\types\LinkType;
use app\types\StringType;
use app\types\TextType;
use app\types\UrlsType;
use Codeception\Test\Unit;

class AttributeTypeBasicsTest extends Unit
{
	/** @var \UnitTester */
	protected $tester;

	public function dataProviderTypes(): array
	{
		return [
			[BooleanType::class],
			[IntegerType::class],
			[StringType::class],
			[TextType::class],
			[DateType::class],
			[DatetimeType::class],
			[UrlsType::class],
			[IpsType::class],
			[JsonType::class],
			[LinkType::class],
		];
	}

	/**
	 * @dataProvider dataProviderTypes
	 */
	public function testTypeContracts(string $typeClass): void
	{
		\Helper\Yii2::initFromFileName('test-console.php');
		\Helper\Database::loadSqlDump();

		$model = new AccessTypes();
		$context = new AttributeContext(
			attribute: 'name',
			empty: false,
			model: $model,
			generationContext: new GenerationContext(seed: 123)
		);

		$type = new $typeClass();

		$this->assertIsArray($type->apiSchema());
		$this->assertIsArray($type->samples());

		$value = $type->generate($context);
		$this->assertNotNull($value);
	}
}
