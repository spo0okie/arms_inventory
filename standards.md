# Architectural and Code Conventions for ARMS

Этот документ содержит устойчивые правила и подходы, принятые в проекте ARMS (Automated Resource Management System). Все правила основаны на реальном коде проекта и документации разработчика.

**Источники:**
- Анализ кодовой базы проекта
- Документация разработчика: https://wiki.reviakin.net/инвентаризация:dev:start
- [`structure.md`](structure.md) - структура проекта

---

## Стандарты моделей (ActiveRecord)

### Базовая модель ArmsModel

Все модели проекта обязаны наследоваться от `ArmsModel` и следовать подходам,
реализованным в ней и связанных трейтах:

- `AttributeDataModelTrait`
- `AttributeLinksModelTrait`
- `ExternalDataModelTrait`

Именно эти файлы являются единственным источником правды о структуре,
метаданных и правилах работы моделей, включая:

- формат и назначение массива `attributeData()`;
- правила генерации label/hint/viewLabel/indexLabel/apiLabel;
- правила типов атрибутов (`type`);
- логику наследуемых полей (`is_inheritable`, плейсхолдеры);
- правила alias-атрибутов и loader-атрибутов;
- работу с рекурсивными атрибутами;
- формирование подсказок поиска;
- генерацию OpenAPI-аннотаций;
- логику множественных и одиночных ссылок;
- поведение обратных ссылок (`absorb`);
- правила join-ов для атрибутов;
- общие соглашения по названиям и структуре полей модели.

Для актуальной и полной спецификации необходимо обращаться к документации,
встроенной в `ArmsModel` и перечисленные trait-файлы.

При создании любой новой модели разработчик обязан изучить и использовать
описанный там подход, чтобы модель была полностью совместима с системой
метаданных ARMS.



## Стандарты миграций

### Наследование

Базовый класс миграций - [`ArmsMigration`](migrations/arms/ArmsMigration.php)
Необходимо учитывать документацию, размещенную в классе и использовать методы, которыми он расширяет базовый класс Migration

###  Именование таблиц

**Правило:** snake_case, множественное число

```
comps                    // Компьютеры
services                 // Сервисы
tech_models              // Модели оборудования
net_ips                  // IP адреса
login_journal            // Журнал входов
comps_history            // История компьютеров
services_in_users        // Связь сервисы-пользователи
```

### Типичные типы колонок

```php
// Строки
$this->string(32)       // Короткие строки (logins, версии)
$this->string(64)       // Названия
$this->string(128)      // Имена хостов, пути
$this->string(255)      // Email, URL, описания
$this->string()         // По умолчанию 255

// Текст
$this->text()           // Обычный текст
$this->mediumText()     // Средний текст (JSON, большие данные)
$this->longText()       // Очень большой текст

// Числа
$this->integer()        // INT(11)
$this->bigInteger()     // BIGINT
$this->float()          // FLOAT
$this->decimal(10, 2)   // DECIMAL для денег

// Даты
$this->timestamp()      // TIMESTAMP (для updated_at, created_at)
$this->datetime()       // DATETIME
$this->date()           // DATE

// Логические
$this->boolean()        // TINYINT(1)
```

## Стандарты контроллеров

### Базовый контроллер

Все контроллеры **ОБЯЗАТЕЛЬНО** наследуются от [`ArmsBaseController`](controllers/ArmsBaseController.php):
Необходимо учитывать документацию, размещенную в классе
Для легковесных моделей можно использовать default view файлы без создания custom

## Стандарты Views

### Структура папок

```
views/{model-name}/
├── index.php          # Список (DynaGrid)
├── view.php           # Детальный просмотр
├── create.php         # Создание
├── update.php         # Редактирование
├── _form.php          # Форма (общая для create/update)
├── item.php           # Краткое представление
├── ttip.php           # Tooltip
├── card.php           # Карточка (опционально общая для view / tooltip)
└── columns.php        # Определение колонок для Grid -> index
```


## Changelog поддержка

### История в таблицах

Все важные таблицы имеют `{table}_history`:

```
comps -> comps_history
services -> services_history  
contracts -> contracts_history
```

### Автоматическое журналирование

В [`ArmsModel`](models/ArmsModel.php):

```php
public function afterSave($insert, $changedAttributes)
{
    parent::afterSave($insert, $changedAttributes);
    $this->historyCommit(); // Автоматическое журналирование
}

public function afterDelete()
{
    parent::afterDelete();
    $this->historyEnd(); // Запись об удалении
}
```

### Просмотр истории

```php
$history = CompsHistory::find()
    ->where(['master_id' => $id])
    ->orderBy(['updated_at' => SORT_DESC])
    ->all();
```

## Swagger/OpenAPI документация

Документирована в файле [`swagger/readme.md`](swagger/readme.md)

## Конфигурация проекта

### Основные файлы

```
config/
├── web.php              # Web-приложение (в git, default значения)
├── web-local.php        # Web-приложение (НЕ в git, кастомизация default значений)
├── console.php          # Консоль (в git)
├── db.php               # БД (git, default значения)
├── db-local.php         # БД (НЕ в git, кастомизация default значений)
├── params.php           # Параметры (в git, default значения)
└── params-local.php     # Параметры (НЕ в git, кастомизация default значений)
```


---

## Best Practices проекта

### Стиль кода

- **PSR-4** autoloading
- **PSR-2** code style (частично)
- **PHPDoc** для всех public методов
- **Типизация** где возможно (PHP 7.4+)

### Принципы

- **DRY** (Don't Repeat Yourself)
- **SOLID** принципы
- **MVC** паттерн строго
- **Convention over Configuration**

### Комментарии

```php
// Однострочные комментарии для пояснений
/* Многострочные для блоков кода */

/**
 * PHPDoc для методов и классов
 * @param type $param описание
 * @return type описание
 */
```

### Именование переменных

```php
$model          // Одна модель
$models         // Массив моделей
$dataProvider   // Провайдер данных
$searchModel    // Search-модель
$query          // ActiveQuery
$renderer       // View для рендеринга
```

---

**Полезные ссылки:**
- [Wiki разработчика](https://wiki.reviakin.net/инвентаризация:dev:start)
- [Структура проекта](structure.md)
- [Yii2 Guide](https://www.yiiframework.com/doc/guide/2.0/en)