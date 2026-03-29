<?php

namespace tests\unit\models;

use app\helpers\ModelHelper;
use app\models\base\ArmsModel;
use Codeception\Test\Unit;
use Yii;
use yii\helpers\FileHelper;

/**
 * Тест для проверки что все safe атрибуты моделей имеют явно указанный тип в метаданных
 * 
 * Проверяет что у каждого safe атрибута каждой модели, наследующейся от ArmsModel,
 * в метаданных (attributeData) явно указан тип поля ('type').
 */
class ModelTypeSafetyTest extends Unit
{
    /** @var \UnitTester */
    protected $tester;

    /** @var array Список проверенных классов и атрибутов для вывода в консоль */
    protected static array $checkedItems = [];


	/**
     * Получает данные для проверки safe атрибутов моделей
     * 
     * Возвращает массив пар [класс, атрибут] для всех safe атрибутов моделей,
     * наследующихся от ArmsModel.
     * 
     * @return array Массив пар [класс, атрибут] в формате ['класс/атрибут' => [класс, атрибут]]
     */
    protected static function getSafeAttributesWithExplicitTypeData(): array {
        // Подключаем базовый класс если ещё не подключен
    
        $testData = [];
        $modelClasses = ModelHelper::getModelClasses();

        foreach ($modelClasses as $modelClass) {
            try {
                /** @var \app\models\base\ArmsModel $model */
                $model = new $modelClass();
                
                // Получаем безопасные атрибуты
                $safeAttributes = $model->safeAttributes();
                
                if (empty($safeAttributes)) {
                    continue;
                }

                foreach ($safeAttributes as $attribute) {
                    // Пропускаем атрибуты-связи
                    if ($model->attributeIsLink($attribute)) {
                        continue;
                    }

                    $testData[$modelClass.'/'.$attribute] = [$modelClass, $attribute];
                }
            } catch (\Exception $e) {
                // Пропускаем модели, которые не удалось создать
                continue;
            }
        }

        return $testData;
    }

    /**
     * Возвращает данные для теста - массив [класс, атрибут]
     * @return array Массив пар [класс, атрибут]
     */
    public function dataProviderSafeAttributesWithExplicitType(): array
    {

		\Helper\Yii2::initFromFileName('test-console.php');
		\Helper\Database::loadSqlDump();
        
        // Подключаем ArmsModel напрямую, чтобы был доступен для проверки наследования     
        //require_once codecept_root_dir() . '/models/base/ArmsModel.php';;
        
        $testData = self::getSafeAttributesWithExplicitTypeData();

        codecept_debug("DataProvider returning " . count($testData) . " items");
        return $testData;
    }

    /**
     * Тест проверяет что конкретный safe атрибут имеет явно указанный тип в метаданных
     * @param string $modelClass Имя класса модели
     * @param string $attributeName Имя атрибута для проверки
     * @dataProvider dataProviderSafeAttributesWithExplicitType
     */
    public function testSafeAttributeHasExplicitType(string $modelClass, string $attributeName): void
    {
        try {
            
            // Подключаем ArmsModel если ещё не подключен
            $armsModelPath = codecept_root_dir() . '/models/base/ArmsModel.php';
            if (file_exists($armsModelPath) && !class_exists(ArmsModel::class, false)) {
                require_once $armsModelPath;
            }
            
            /** @var ArmsModel $model */
            $model = new $modelClass();
            
            // Получаем метаданные атрибута
			$typeClass = $model->getAttributeTypeClass($attributeName);			
        } catch (\Exception $e) {
            $this->fail(sprintf(
                'Не удалось проверить атрибут "%s" модели %s: %s',
                $attributeName,
                $modelClass,
                $e->getMessage()
            ));
        }
    }

}
