<?php

namespace tests\unit\models;

use app\generation\ModelFactory;
use app\helpers\ModelHelper;
use Codeception\Test\Unit;

/**
 * Тест для проверки, что все модели ArmsModel могут быть собраны генератором
 */
class ModelGenerationTest extends Unit
{
    /** @var \UnitTester */
    protected $tester;

    /**
     * Возвращает данные для теста - массив [класс]
     * @return array Массив пар [класс, атрибут]
     */
    public function dataProviderArmsModels(): array
    {

		\Helper\Yii2::initFromFileName('test-console.php');
		\Helper\Database::loadSqlDump();

		$data=[];
        foreach(ModelHelper::getModelClasses() as $class) {
			$data[$class]=[$class];
		}

		return $data;
    }

    /**
     * Тест сборки модели через ModelFactory
     * @param string $modelClass Имя класса модели
     * @dataProvider dataProviderArmsModels
     */
    public function testModelGeneration(string $modelClass): void
    {
        try {            
			$model=ModelFactory::create($modelClass,['empty'=>true]);
			if (is_null($model)) {
				$this->fail('Не удалось создать модель класса '.$modelClass);
            }			
        } catch (\Exception $e) {
			$this->fail('Exception при генерации класса '.$modelClass.':'.PHP_EOL.$e->getMessage().PHP_EOL.$e->getTraceAsString());
        }
    }
}
