<?php

namespace tests\unit\types;

use app\helpers\ModelHelper;
use Codeception\Test\Unit;

/**
 * Энфорсер строгой типизации API-схемы.
 *
 * Для каждого атрибута реальной API-поверхности каждой модели (attributes() + extraFields())
 * проверяет, что generateRWAttributeAnnotation() НЕ бросает исключение.
 *
 * После флипа аннотации на apiSchema() резолвер типов строгий: если тип атрибута не выводится
 * из объявления (typeClass/ref/linksSchema) или правил валидации алгоритмически — генерация
 * схемы бросает исключение. Этот тест собирает все такие атрибуты и падает со списком,
 * заставляя объявить тип явно (или убрать ошибочный атрибут/колонку).
 */
class ApiSchemaResolvableTest extends Unit
{
    /** @var \UnitTester */
    protected $tester;

    public function testEveryApiAttributeResolvesType(): void
    {
        \Helper\Yii2::initFromFileName('test-console.php');
        \Helper\Database::loadSqlDump();

        $unresolved = [];
        foreach (ModelHelper::getModelClasses() as $modelClass) {
            try {
                /** @var \app\models\base\ArmsModel $model */
                $model = new $modelClass();
            } catch (\Throwable $e) {
                continue;
            }

            $attrs = array_unique(array_merge($model->attributes(), $model->extraFields()));
            foreach ($attrs as $attribute) {
                try {
                    $model->generateRWAttributeAnnotation($attribute);
                } catch (\Throwable $e) {
                    $unresolved[] = $modelClass . '->' . $attribute . '  read: (' . $e->getMessage() . ')';
                }
            }
        }

        // search-набор: поля из $searchFields контроллеров (как в swagger\processors\ExpandMacrosProcessor)
        $ctx = new \OpenApi\Context();
        foreach (glob(codecept_root_dir() . '/modules/api/controllers/*Controller.php') as $file) {
            require_once $file;
            $short = basename($file, '.php');
            $cls = 'app\\modules\\api\\controllers\\' . $short;
            if (!class_exists($cls) || !property_exists($cls, 'searchFields')) continue;
            $modelClass = 'app\\models\\' . substr($short, 0, -strlen('Controller'));
            if (!class_exists($modelClass)) continue;
            try {
                $model = new $modelClass();
            } catch (\Throwable $e) {
                continue;
            }
            foreach ($cls::$searchFields as $name => $field) {
                $fieldName = is_numeric($name) ? $field : $name;
                try {
                    $model->generateSearchParameterAnnotation($fieldName, $ctx);
                } catch (\Throwable $e) {
                    $unresolved[] = $cls . ' search[' . $fieldName . '] -> ' . $modelClass . '  (' . $e->getMessage() . ')';
                }
            }
        }

        $this->assertSame(
            [],
            $unresolved,
            "Не выводится тип для API-атрибутов (объявите typeClass/ref/linksSchema или уберите атрибут):\n"
                . implode("\n", $unresolved)
        );
    }
}
