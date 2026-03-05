<?php
/**
 * Bootstrap для unit-тестов модуля schedules.
 *
 * Unit-тесты этого модуля не требуют БД и полного поднятия приложения.
 * Достаточно подключить автозагрузчик Composer и определить константы Yii2.
 */

defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'test');

// Корень проекта — три уровня вверх от modules/schedules/tests/
$projectRoot = dirname(__DIR__, 3);

require_once $projectRoot . '/vendor/autoload.php';
require_once $projectRoot . '/vendor/yiisoft/yii2/Yii.php';

// Регистрируем псевдоним @app чтобы автозагрузчик Yii2 находил классы проекта
Yii::setAlias('@app', $projectRoot);
Yii::setAlias('@modules', $projectRoot . '/modules');

// Регистрируем хелперы вручную (они не в PSR-4 пространстве имён)
require_once $projectRoot . '/helpers/ArrayHelper.php';
require_once $projectRoot . '/helpers/StringHelper.php';
require_once $projectRoot . '/helpers/DateTimeHelper.php';
