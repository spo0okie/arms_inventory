<?php

namespace tests\unit\models;

use app\generation\context\GenerationContext;
use app\generation\ModelFactory;
use app\models\base\ArmsModel;
use Codeception\Test\Unit;

/** Проверяет контракт фабрики независимо от ролей предметных моделей. */
class ModelFactoryTest extends Unit
{
	/** Callback роли получает исходные параметры текущей генерации. */
	public function testRoleReceivesGenerationContext(): void
	{
		// Пробник без атрибутов и связей не требует таблицы и записей в БД.
		$probe = new class extends ArmsModel {
			/** @var GenerationContext|null Контекст, переданный фабрикой в роль. */
			public ?GenerationContext $roleContext = null;

			/** У пробника нет сохраняемых атрибутов. */
			public function attributes(): array { return []; }

			/** Генерация пробника не зависит от правил предметных моделей. */
			public function rules(): array { return []; }

			/** Пробнику не нужны поведения ActiveRecord. */
			public function behaviors(): array { return []; }

			/** Тестовая роль только фиксирует полученный контекст. */
			public static function roles(): array
			{
				return ['capture' => static function (self $model, GenerationContext $context): void {
					$model->roleContext = $context;
				}];
			}
		};
		$seedBefore = ModelFactory::$seed;
		$model = ModelFactory::create(get_class($probe), [
			'role' => 'capture',
			'empty' => true,
			'seed' => 12345,
			'depth' => 2,
			'maxDepth' => 2,
			'save' => false,
		]);
		$context = $model->roleContext;
		$this->assertInstanceOf(GenerationContext::class, $context);
		$this->assertTrue($context->empty);
		$this->assertSame(12345, $context->seed);
		$this->assertSame(2, $context->depth);
		$this->assertSame(2, $context->maxDepth);
		$this->assertSame($seedBefore, ModelFactory::$seed);
	}
}
