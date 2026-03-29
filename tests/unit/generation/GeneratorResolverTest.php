<?php

namespace tests\unit\generation;

use app\generation\context\AttributeContext;
use app\generation\context\GenerationContext;
use app\helpers\ModelHelper;
use app\models\base\ArmsModel;
use app\types\AttributeTypeInterface;
use Codeception\Test\Unit;

/**
 * Тест для проверки что все типы атрибутов могут генерировать значения.
 * 
 * Проверяет что для всех типов атрибутов, используемых в моделях ARMS,
 * существует соответствующий тип реализующий генерацию.
 */
class TypeGenerationTest extends Unit
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
        if (file_exists($armsModelPath) && !class_exists(ArmsModel::class, false)) {
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
     * Тест проверяет что для конкретного типа атрибута существует Type класс с генерацией
     * @param string $type Тип атрибута
     * @param int $count Количество использований типа
     * @param string $sample Пример использования
     * @dataProvider dataProviderAllModelTypes
     */
    public function testTypeCanGenerate(string $type, int $count, string $sample): void
    {
        // Находим модель с этим типом атрибута
        [$modelClass, $attribute] = explode('->', $sample);
        
        /** @var ArmsModel $model */
        $model = new $modelClass();
        
        try {
            // Получаем тип для генерации
            $typeInstance = $model->getAttributeTypeForGeneration($attribute);
            
            // Проверяем что тип реализует интерфейс
            $this->assertInstanceOf(
                AttributeTypeInterface::class,
                $typeInstance,
                "Атрибут '$sample' не имеет typeClass"
            );
            
            // Создаём контекст для генерации
            $generationContext = new GenerationContext(
                empty: false,
                seed: 12345,
                depth: 0,
                maxDepth: 2
            );
            
            $attributeData = $model->getAttributeData($attribute);
            $attrContext = new AttributeContext(
                attribute: $attribute,
                attributeData: is_array($attributeData) ? $attributeData : [],
                model: $model,
                generationContext: $generationContext
            );
            
            // Генерируем значение
            $value = $typeInstance->generate($attrContext);
            
            // Проверяем что значение сгенерировано
            $this->assertNotNull(
                $value,
                "Тип '{$typeInstance::name()}' вернул null для атрибута '$sample'"
            );
            
        } catch (\Exception $e) {
            $this->fail(
                sprintf(
                    "Не удалось сгенерировать значение для типа '%s' (используется %d раз, например: %s): %s",
                    $type,
                    $count,
                    $sample,
                    $e->getMessage()
                )
            );
        }
    }
}
