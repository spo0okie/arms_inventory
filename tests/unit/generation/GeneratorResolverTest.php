<?php

namespace tests\unit\generation;

use app\generation\generators\GeneratorInterface;
use app\generation\generators\GeneratorResolver;
use app\helpers\ModelHelper;
use Codeception\Test\Unit;

/**
 * Тест для проверки что GeneratorResolver может подобрать генератор к каждому типу атрибутов моделей
 * 
 * Проверяет что для всех типов атрибутов, используемых в моделях ARMS,
 * существует соответствующий генератор в GeneratorResolver.
 */
class GeneratorResolverTest extends Unit
{
    /** @var \UnitTester */
    protected $tester;

    /**
     * Получает все типы атрибутов из моделей
     * @return array Массив с информацией о типах
     */
    protected static function getModelAttributeTypes(): array
    {
        return ModelHelper::getModelAtributesTypes();
    }

    /**
     * DataProvider: возвращает все типы атрибутов из моделей
     * @return array Массив типов атрибутов
     */
    public function dataProviderAllModelTypes(): array
    {
        // Инициализация Yii для работы с моделями
        \Helper\Yii2::initFromFileName('test-console.php');
        \Helper\Database::loadSqlDump();
        
        // Подключаем базовый класс модели
        $armsModelPath = codecept_root_dir() . '/models/base/ArmsModel.php';
        if (file_exists($armsModelPath) && !class_exists(\app\models\base\ArmsModel::class, false)) {
            require_once $armsModelPath;
        }

        $types = self::getModelAttributeTypes();
        
        codecept_debug("Найдено типов атрибутов: " . count($types));
        
        // Форматируем для dataProvider
        $result = [];
        foreach ($types as $type => $info) {
            $result[] = [
                'type' => $type,
                'count' => $info['count'] ?? 0,
                'sample' => $info['sample'] ?? '',
            ];
        }
        
        return $result;
    }

    /**
     * Тест проверяет что для конкретного типа атрибута существует генератор
     * @param string $type Тип атрибута
     * @param int $count Количество использований типа
     * @param string $sample Пример использования
     * @dataProvider dataProviderAllModelTypes
     */
    public function testTypeHasGenerator(string $type, int $count, string $sample): void
    {
        // Проверяем что генератор может определить класс для типа
        try {
            $generatorClass = GeneratorResolver::getGeneratorClass($type);
            
            // Проверяем что класс реализует интерфейс GeneratorInterface
            $this->assertTrue(
                class_exists($generatorClass),
                "Класс генератора '$generatorClass' не существует для типа '$type'"
            );
            
            $this->assertTrue(
                is_a($generatorClass, GeneratorInterface::class, true),
                "Класс '$generatorClass' не реализует GeneratorInterface для типа '$type'"
            );
            
            // Проверяем что генератор имеет статический метод generate
            $this->assertTrue(
                method_exists($generatorClass, 'generate'),
                "Класс '$generatorClass' не имеет метода 'generate' для типа '$type'"
            );
            
        } catch (\Exception $e) {
            $this->fail(
                sprintf(
                    "Для типа '%s' (используется %d раз, например: %s) не найден генератор: %s",
                    $type,
                    $count,
                    $sample,
                    $e->getMessage()
                )
            );
        }
    }
}
