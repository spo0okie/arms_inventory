<?php

namespace tests\unit\models;

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
     * Инициализирует Yii приложение для тестов
     * @return bool True если инициализация прошла успешно
     */
    protected static function initYii(): bool
    {
        // Если Yii уже инициализирован, ничего не делаем
        if (Yii::$app !== null) {
            return true;
        }

        // Подключаем autoloader Yii
        require_once codecept_root_dir() . '/vendor/autoload.php';
        require_once codecept_root_dir() . '/vendor/yiisoft/yii2/Yii.php';

        // Загружаем конфигурацию
        $configPath = codecept_root_dir() . '/config/test-console.php';
        if (!file_exists($configPath)) {
            echo "Config not found: $configPath\n";
            return false;
        }

        $config = require $configPath;
        
        // Устанавливаем базовый путь для alias @app
        $config['basePath'] = codecept_root_dir();
        
        try {
            new \yii\console\Application($config);
            
            // Явно устанавливаем alias для @app
            Yii::setAlias('@app', codecept_root_dir() . '/');
            
            return true;
        } catch (\Exception $e) {
            echo "Yii init error: " . $e->getMessage() . "\n";
            return false;
        }
    }

    /**
     * Получает все файлы моделей из директорий models и modules
     * @return array Список путей к файлам моделей
     */
    protected function getModelFiles(): array
    {
        $modelFiles = [];
        
        // Используем прямые пути вместо alias'ов
        $rootDir = codecept_root_dir();
        $directories = [
            $rootDir . '/models',
            $rootDir . '/modules',
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $files = FileHelper::findFiles($directory, [
                'only' => ['*.php'],
                'recursive' => true,
                'exclude' => [
                    'base/',
                    'traits/',
                    'ui/',
                    'links/',
                ],
            ]);

            foreach ($files as $file) {
				if (str_ends_with($file, 'ArmsModel.php')) continue;
				if (str_ends_with($file, 'HistoryModel.php')) continue;
				if (str_ends_with($file, 'LicLinks.php')) continue;
				if (str_ends_with($file, 'History.php')) continue;
				if (str_ends_with($file, 'Search.php')) continue;
                $modelFiles[] = $file;
            }
        }

        return $modelFiles;
    }

    /**
     * Извлекает имена классов из файлов моделей
     * @param array $modelFiles Список путей к файлам моделей
     * @return array Список имен классов моделей
     */
    protected function getModelClasses(array $modelFiles): array
    {
        $modelClasses = [];

        foreach ($modelFiles as $file) {
            $content = file_get_contents($file);
            
            // Пропускаем файлы без namespace
            if (!preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
                continue;
            }
            $namespace = trim($matches[1]);

            // Ищем имя основного класса - упрощенная регулярка
            if (preg_match('/^class\s+(\w+)/m', $content, $classMatches)) {
                $className = $namespace . '\\' . $classMatches[1];
                
                // Подключаем файл для загрузки класса
                require_once $file;
                
                $modelClasses[] = $className;
            }
        }

        return $modelClasses;
    }

    /**
     * Проверяет, является ли атрибут частью связи (relation), а не полем модели
     * @param ArmsModel $model Модель
     * @param string $attribute Имя атрибута
     * @return bool True если это связь
     */
    protected function isRelation(ArmsModel $model, string $attribute): bool
    {
        // Проверяем, есть ли такой метод связи в модели
        return method_exists($model, 'get' . ucfirst($attribute)) || 
               isset($model->getLinksSchema()[$attribute]);
    }

    /**
     * Возвращает данные для теста - массив [класс, атрибут]
     * @return array Массив пар [класс, атрибут]
     */
    public function dataProviderSafeAttributesWithExplicitType(): array
    {
        // Инициализируем Yii перед запуском DataProvider
        /*if (!self::initYii()) {
            echo "Yii initialization failed\n";
            return [];
        }*/

		\Helper\Yii2::initFromFileName('test-console.php');
		\Helper\Database::loadSqlDump();
        
        // Подключаем ArmsModel напрямую, чтобы был доступен для проверки наследования
        $armsModelPath = codecept_root_dir() . '/models/base/ArmsModel.php';
        if (file_exists($armsModelPath)) {
            require_once $armsModelPath;
        }
        
        $testData = [];
        self::$checkedItems = []; // Сбрасываем список проверенных элементов
        
        $modelFiles = $this->getModelFiles();
        $modelClasses = $this->getModelClasses($modelFiles);

        codecept_debug("Found " . count($modelClasses) . " potential model classes");

        $checkedClasses = []; // Отслеживаем уже проверенные классы

        foreach ($modelClasses as $modelClass) {
            // Пропускаем если класс уже проверен
            if (isset($checkedClasses[$modelClass])) {
                continue;
            }

            // Проверяем наследование от ArmsModel
            if (!is_a($modelClass, ArmsModel::class, true)) {
                continue;
            }

            try {
                /** @var ArmsModel $model */
                $model = new $modelClass();
                
                // Получаем безопасные атрибуты - это МЕТОД safeAttributes(), а не getSafeAttributeNames()!
                // (метод определён в yii\base\Model)
                $safeAttributes = $model->safeAttributes();
                
                if (empty($safeAttributes)) {
                    continue;
                }

                $checkedClasses[$modelClass] = true;

                foreach ($safeAttributes as $attribute) {
                    // Пропускаем атрибуты-связи
                    if ($model->attributeIsLink($attribute)) {
                        continue;
                    }

                    $testData[$modelClass.'/'.$attribute] = [$modelClass, $attribute];
                    
                    // Записываем в список для вывода
                    self::$checkedItems[] = [
                        'class' => $modelClass,
                        'attribute' => $attribute,
                    ];
                }
            } catch (\Exception $e) {
                // Пропускаем модели, которые не удалось создать
                codecept_debug("Пропущен класс $modelClass: " . $e->getMessage());
                continue;
            }
        }

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
            // Инициализируем Yii если ещё не инициализирован
            //self::initYii();
            
            // Подключаем ArmsModel если ещё не подключен
            $armsModelPath = codecept_root_dir() . '/models/base/ArmsModel.php';
            if (file_exists($armsModelPath) && !class_exists(ArmsModel::class, false)) {
                require_once $armsModelPath;
            }
            
            /** @var ArmsModel $model */
            $model = new $modelClass();
            
            // Получаем метаданные атрибута
			if (is_null($model->getAttributeType($attributeName,null))) {
				$this->fail(sprintf(
                    'У атрибута "%s" модели %s отсутствует информация о типе (key "type") в метаданных',
                    $attributeName,
                    $modelClass
                ));
            }
			
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
