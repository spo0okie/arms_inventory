<?php

namespace tests\unit\models;

use app\generation\ModelFactory;
use app\helpers\ModelHelper;
use Codeception\Test\Unit;

/**
 * Тест детерминизма генерации моделей через ModelFactory.
 * 
 * Проверяет, что одинаковый seed всегда produces одинаковые модели.
 */
class DeterministicGenerationTest extends Unit
{
    /** @var \UnitTester */
    protected $tester;

    /**
     * Инициализация Yii и БД
     */
    protected function _before()
    {
        \Helper\Yii2::initFromFileName('test-console.php');
        \Helper\Database::loadSqlDump();
    }

    /**
     * Тест: одинаковый seed produces одинаковые модели
     */
    public function testSameSeedProducesSameModel(): void
    {
        $modelClass = \app\models\AccessTypes::class;
        $seed = 42;
        
        $model1 = ModelFactory::create($modelClass, ['seed' => $seed, 'save' => false]);
        $model2 = ModelFactory::create($modelClass, ['seed' => $seed, 'save' => false]);
        
        $this->assertNotNull($model1, 'First model should be created');
        $this->assertNotNull($model2, 'Second model should be created');
        
        // Сравниваем все атрибуты кроме id (он автоинкрементный)
        $attrs1 = $model1->getAttributes();
        $attrs2 = $model2->getAttributes();
        
        unset($attrs1['id'], $attrs2['id']);
        unset($attrs1['created_at'], $attrs2['created_at']);
        unset($attrs1['updated_at'], $attrs2['updated_at']);
        unset($attrs1['created_by'], $attrs2['created_by']);
        unset($attrs1['updated_by'], $attrs2['updated_by']);
        
        $this->assertEquals($attrs1, $attrs2, 'Models with same seed should have identical attributes');
    }

    /**
     * Тест: разные seed produces разные модели
     */
    public function testDifferentSeedsProduceDifferentModels(): void
    {
        $modelClass = \app\models\AccessTypes::class;
        
        $model1 = ModelFactory::create($modelClass, ['seed' => 100, 'save' => false]);
        $model2 = ModelFactory::create($modelClass, ['seed' => 200, 'save' => false]);
        
        $this->assertNotNull($model1, 'First model should be created');
        $this->assertNotNull($model2, 'Second model should be created');
        
        // Сравниваем все атрибуты кроме id
        $attrs1 = $model1->getAttributes();
        $attrs2 = $model2->getAttributes();
        
        unset($attrs1['id'], $attrs2['id']);
        unset($attrs1['created_at'], $attrs2['created_at']);
        unset($attrs1['updated_at'], $attrs2['updated_at']);
        unset($attrs1['created_by'], $attrs2['created_by']);
        unset($attrs1['updated_by'], $attrs2['updated_by']);
        
        $this->assertNotEquals($attrs1, $attrs2, 'Models with different seeds should have different attributes');
    }

    /**
     * Тест: детерминизм сохраняется при генерации с relations
     */
    public function testDeterminismWithRelations(): void
    {
        // Выбираем модель с relations если доступна
        $modelClass = \app\models\AccessTypes::class;
        $seed = 12345;
        
        $model1 = ModelFactory::create($modelClass, ['seed' => $seed, 'save' => false, 'maxDepth' => 1]);
        $model2 = ModelFactory::create($modelClass, ['seed' => $seed, 'save' => false, 'maxDepth' => 1]);
        
        $this->assertNotNull($model1, 'First model should be created');
        $this->assertNotNull($model2, 'Second model should be created');
        
        // Сравниваем все атрибуты
        $attrs1 = $model1->getAttributes();
        $attrs2 = $model2->getAttributes();
        
        unset($attrs1['id'], $attrs2['id']);
        unset($attrs1['created_at'], $attrs2['created_at']);
        unset($attrs1['updated_at'], $attrs2['updated_at']);
        unset($attrs1['created_by'], $attrs2['created_by']);
        unset($attrs1['updated_by'], $attrs2['updated_by']);
        
        $this->assertEquals($attrs1, $attrs2, 'Models with relations should be deterministic');
    }

    /**
     * Тест: множественные создания с одним seed всегда одинаковы
     */
    public function testMultipleCreationsWithSameSeed(): void
    {
        $modelClass = \app\models\AccessTypes::class;
        $seed = 999;
        $iterations = 5;
        
        $firstModel = ModelFactory::create($modelClass, ['seed' => $seed, 'save' => false]);
        $firstAttrs = $firstModel->getAttributes();
        unset($firstAttrs['id'], $firstAttrs['created_at'], $firstAttrs['updated_at']);
        unset($firstAttrs['created_by'], $firstAttrs['updated_by']);
        
        for ($i = 0; $i < $iterations; $i++) {
            $model = ModelFactory::create($modelClass, ['seed' => $seed, 'save' => false]);
            $attrs = $model->getAttributes();
            unset($attrs['id'], $attrs['created_at'], $attrs['updated_at']);
            unset($attrs['created_by'], $attrs['updated_by']);
            
            $this->assertEquals(
                $firstAttrs, 
                $attrs, 
                "Iteration {$i}: Model should be identical to first creation with same seed"
            );
        }
    }
}
