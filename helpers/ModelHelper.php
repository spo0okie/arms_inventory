<?php

namespace app\helpers;

use app\models\base\ArmsModel;
use yii\helpers\FileHelper;

/**
 * Вспомогательный класс для работы с моделями ARMS
 * 
 * Предоставляет методы для получения списков моделей из директорий models и modules
 */
class ModelHelper
{
    /**
     * Исключаемые директории при поиске моделей
     */
    const EXCLUDED_DIRS = [
        'base/',
        'traits/',
        'ui/',
        'links/',
    ];

    /**
     * Исключаемые файлы при поиске моделей
     */
    const EXCLUDED_FILES = [
        'ArmsModel.php',
        'HistoryModel.php',
        'LicLinks.php',
        'History.php',
        'Search.php',
    ];

    /**
     * Получает все файлы моделей из директорий models и modules
     * 
     * @param string $rootDir Корневая директория проекта
     * @return array Список путей к файлам моделей
     */
    public static function getModelFiles(): array
    {
        $modelFiles = [];
        
        $directories = [
            \Yii::getAlias('@app') . '/models',
            \Yii::getAlias('@app') . '/modules',
        ];

        foreach ($directories as $directory) {
            if (!is_dir($directory)) {
                continue;
            }

            $files = FileHelper::findFiles($directory, [
                'only' => ['*.php'],
                'recursive' => true,
                'except' => self::EXCLUDED_DIRS,
            ]);

            foreach ($files as $file) {
                if (self::isExcludedFile($file)) {
                    continue;
                }
                $modelFiles[] = $file;
            }
        }

        return $modelFiles;
    }

    /**
     * Проверяет, исключён ли файл из поиска
     * 
     * @param string $file Путь к файлу
     * @return bool True если файл нужно исключить
     */
    private static function isExcludedFile(string $file): bool
    {
        foreach (self::EXCLUDED_FILES as $excludedFile) {
            if (str_ends_with($file, $excludedFile)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Извлекает имена классов из файлов моделей
     * 
     * @param array $modelFiles Список путей к файлам моделей
     * @return array Список имён классов моделей
     */
    public static function getModelClasses(?array $modelFiles=null): array
    {	
		if ($modelFiles === null)
			$modelFiles = self::getModelFiles();

        $modelClasses = [];

        foreach ($modelFiles as $file) {
            $content = file_get_contents($file);
            
            // Пропускаем файлы без namespace
            if (!preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
                continue;
            }
            $namespace = trim($matches[1]);

            // Ищем имя основного класса
            if (preg_match('/^class\s+(\w+)/m', $content, $classMatches)) {
                $className = $namespace . '\\' . $classMatches[1];
                
                // Подключаем файл для загрузки класса
                require_once $file;

				// Проверяем наследование от базового класса
				if (!is_a($className, ArmsModel::class, true)) {
					continue;
				}
                
                $modelClasses[] = $className;
            }
        }

        return $modelClasses;
    }

	/**
	 * Возвращает список типов атрибутов моделей
	 * @param array|null $modelClasses Список классов моделей (по умолчанию все модели из models,modules)
	 * @return array Список типов атрибутов моделей в формате 
	 * 				['type'=>['count'=>количество использований,'sample'=>пример где используется]]
	 */
	public static function getModelAtributesTypes(?array $modelClasses=null): array {
		$types=[];
		if ($modelClasses === null)
			$modelClasses = self::getModelClasses();
		foreach ($modelClasses as $modelClass) {
			$model=new $modelClass();
			$attribureData=$model->attributeData();
			foreach ($attribureData as $attr=>$data) {
				if (isset($data['type'])) {
					$type=$data['type'];
					if (!isset($types[$type])) {
						$types[$type]=['count'=>1,'sample'=>$modelClass.'->'.$attr];
					} else
						$types[$type]['count']++;
				}
			}
		}
		
		return $types;
	}

}
