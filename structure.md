# Структура проекта ARMS (Automated Resource Management System)

## Общее описание

**ARMS** - это система автоматизированной инвентаризации ИТ-инфраструктуры предприятия, построенная на фреймворке Yii2. Система предназначена для комплексного учета оборудования, программного обеспечения, лицензий, сетевой инфраструктуры и предоставляемых ИТ-сервисов.

### Основные возможности

- **Учет оборудования**: компьютеры, серверы, сетевое оборудование, периферия
- **Учет операционных систем и ПО**: установленное программное обеспечение на рабочих станциях
- **Управление лицензиями**: лицензионные группы, ключи, типы лицензий, контроль использования
- **Сетевая инфраструктура**: сети, VLAN, IP-адреса, домены
- **ИТ-сервисы**: каталог предоставляемых услуг, распределение по ответственным
- **Компоновка рабочих мест**: визуализация расположения оборудования
- **Учет контрактов и партнеров**: договоры, поставщики услуг
- **Планирование**: расписания, временные доступы
- **История изменений**: журналирование всех изменений объектов
- **REST API**: программный доступ к данным системы
- **Интеграция с AI**: автоматическая генерация описаний ПО через OpenAI

---

## Технологический стек

### Backend

- **PHP**: 8.1+
- **Framework**: Yii2
- **База данных**: MariaDB 10

### Frontend

- **Bootstrap 5**: основной UI-фреймворк
- **Kartik-v виджеты**: расширенные компоненты для Yii2
  - DynaGrid: динамические таблицы с настройкой колонок
  - Select2: расширенные выпадающие списки
  - DateTimePicker: выбор даты и времени
  - FileInput: загрузка файлов
  - Markdown: работа с markdown-разметкой

---

## Структура директорий

```text
arms/
├── assets/              # Frontend-ресурсы (JS, CSS, изображения)
├── components/          # Переиспользуемые компоненты приложения
├── config/              # Конфигурационные файлы
├── console/             # Консольные команды (CLI)
├── controllers/         # Web-контроллеры (MVC)
├── docker/              # Docker-конфигурация
├── helpers/             # Вспомогательные классы
├── mail/                # Email-шаблоны
├── migrations/          # Миграции базы данных
├── models/              # Модели данных (ActiveRecord)
│   ├── base/            # Базовый класс ArmsModel и его трейты
│   ├── links/           # Модели связующих таблиц (M2M)
│   ├── traits/          # Трейты для моделей
│   └── ui/              # UI-модели (формы, представления)
├── modules/             # Модули приложения
│   ├── api/             # REST API модуль
│   │   └── controllers/ # REST контроллеры
│   └── schedules/       # Модуль расписаний
│       ├── controllers/ # Контроллеры расписаний
│       ├── helpers/     # Вспомогательные классы
│       ├── migrations/  # Миграции модуля
│       ├── models/      # Модели расписаний
│       │   └── traits/  # Трейты моделей расписаний
│       ├── tests/       # Тесты модуля
│       └── views/       # Представления расписаний
├── runtime/             # Временные файлы, логи, кэш
├── scss/                # SCSS исходники стилей
├── swagger/             # Swagger-документация API
├── templates/           # Шаблоны для генераторов кода
├── tests/               # Тесты (Codeception)
├── views/               # Представления (View)
├── web/                 # Публичная директория (document root)
├── composer.json        # Зависимости PHP
└── yii                  # Консольная точка входа
```

---

## Ключевые директории

### `/controllers` - Web-контроллеры

Контроллеры обрабатывают HTTP-запросы и управляют бизнес-логикой. Все контроллеры наследуются от [`ArmsBaseController`](controllers/ArmsBaseController.php), который предоставляет:

- CRUD-операции (Create, Read, Update, Delete)
- Контроль доступа через RBAC
- Ajax-поддержка
- Валидация данных
- Архивирование записей
- История изменений

---

### `/models` - Модели данных

Модели представляют данные и бизнес-логику. Все модели наследуются от [`ArmsModel`](models/ArmsModel.php), который расширяет `ActiveRecord` и добавляет:

- Автоматическое журналирование изменений
- Работу с many-to-many связями через LinkerBehavior
- Рекурсивные атрибуты (для иерархических моделей)
- Синхронизацию с удаленными системами
- Внешние ссылки (external_links)
- Валидацию и кастомные правила

**Модели соответствуют контроллерам:**

- Каждый контроллер работает со своей моделью
- Для моделей с поиском есть Search-классы (например, [`CompsSearch`](models/CompsSearch.php))
- Для моделей с историей есть History-классы (например, [`CompsHistory`](models/CompsHistory.php))

**Вспомогательные директории:**

- [`models/links/`](models/links) - модели связующих таблиц many-to-many
- [`models/traits/`](models/traits) - переиспользуемые трейты
- [`models/ui/`](models/ui) - модели UI-настроек

---

### `/components` - Компоненты приложения

#### Виджеты

Переиспользуемые UI-компоненты для отображения данных:

- [`DynaGridWidget`](components/DynaGridWidget.php) - динамическая таблица с настройкой колонок
- [`RackWidget`](components/RackWidget.php) - визуализация стойки оборудования
- [`HistoryWidget`](components/HistoryWidget.php) - виджет истории изменений
- [`WikiPageWidget`](components/WikiPageWidget.php) - отображение Wiki-страниц
- [`WikiTextWidget`](components/WikiTextWidget.php) - рендеринг Wiki-разметки

**Специализированные виджеты:**

- [`LinkObjectWidget`](components/LinkObjectWidget.php) - ссылка на объект
- [`ListObjectsWidget`](components/ListObjectsWidget.php) - список связанных объектов
- [`DeleteObjectWidget`](components/DeleteObjectWidget.php) - кнопка удаления
- [`UpdateObjectWidget`](components/UpdateObjectWidget.php) - кнопка редактирования

#### LLM-интеграция

[`components/llm/LlmClient`](components/llm/LlmClient.php) - клиент для работы с OpenAI API:

**Возможности:**

- Автоматическая генерация описаний программного обеспечения
- Генерация технических характеристик оборудования
- Поддержка proxy для доступа к OpenAI API
- Использование модели GPT-4o-mini для экономии

#### Формы

[`components/Forms/`](components/Forms) - компоненты для работы с формами:

- [`ArmsForm`](components/Forms/ArmsForm.php) - базовый класс форм
- Валидация через Ajax
- Динамические поля (зависимые выпадающие списки)

#### Колонки Grid

[`components/gridColumns/`](components/gridColumns) - кастомные колонки для таблиц:

- Колонки с кастомной фильтрацией
- Редактируемые колонки inline
- Связанные колонки (отображение данных из связанных таблиц)

---

### `/helpers` - Вспомогательные классы

Утилиты для работы с данными:

- [`ArrayHelper`](helpers/ArrayHelper.php) - расширенная работа с массивами
- [`StringHelper`](helpers/StringHelper.php) - работа со строками
- [`DateTimeHelper`](helpers/DateTimeHelper.php) - работа с датами
- [`RestHelper`](helpers/RestHelper.php) - помощник для REST API запросов
- [`WikiHelper`](helpers/WikiHelper.php) - парсинг Wiki-разметки
- [`QueryHelper`](helpers/QueryHelper.php) - построение SQL-запросов
- [`FieldsHelper`](helpers/FieldsHelper.php) - работа с полями моделей
- [`MacsHelper`](helpers/MacsHelper.php) - работа с MAC-адресами
- [`HtmlHelper`](helpers/HtmlHelper.php) - генерация HTML

---

### `/console/commands` - Консольные команды

CLI-команды для административных задач:

- [`CompsController`](console/commands/CompsController.php) - работа с компьютерами
- [`SoftController`](console/commands/SoftController.php) - работа с ПО
- [`TechModelsController`](console/commands/TechModelsController.php) - работа с моделями оборудования
- [`SyncController`](console/commands/SyncController.php) - синхронизация с удаленными системами
- [`IpController`](console/commands/IpController.php) - работа с IP-адресами
- [`LoginJournalController`](console/commands/LoginJournalController.php) - анализ журнала входов
- [`UsersController`](console/commands/UsersController.php) - управление пользователями
- [`RbacController`](console/commands/RbacController.php) - управление правами доступа
- [`OrgStructController`](console/commands/OrgStructController.php) - работа с оргструктурой

**Использование:**

```bash
php yii comps/rescan              # Пересканировать компьютеры
php yii sync/pull-soft            # Синхронизировать ПО
php yii rbac/init                 # Инициализация RBAC
```

---

### `/modules` - Модули приложения

#### REST API модуль

[`modules/api/Rest`](modules/api/Rest.php) - RESTful API для программного доступа:

**Особенности:**

- Автоматическое создание контроллеров для моделей
- Поддержка стандартных REST-операций (GET, POST, PUT, DELETE)
- Фильтрация и поиск
- Пагинация
- Swagger-документация

**Endpoints:**

```text
GET    /api/comps              # Список компьютеров
GET    /api/comps/123          # Компьютер по ID
POST   /api/comps              # Создать компьютер
PUT    /api/comps/123          # Обновить компьютер
DELETE /api/comps/123          # Удалить компьютер
GET    /api/comps/search       # Поиск
```

**Доступные ресурсы API:**

- comps, contracts, domains
- lic-groups, lic-types, login-journal
- manufacturers, net-ips, org-struct
- partners, phones, scans, services
- soft, soft-lists, techs, tech-models, tech-types
- users

---

### `/views` - Представления (Views)

Каждая модель имеет набор стандартных представлений:

```text
views/{model-name}/
├── index.php        # Список (таблица)
├── view.php         # Просмотр одного объекта
├── create.php       # Создание нового
├── update.php       # Редактирование
├── _form.php        # Форма (переиспользуется в create/update)
├── item.php         # Краткое представление (для списков)
├── ttip.php         # Всплывающая подсказка
├── card.php         # Карточка объекта
└── columns.php      # Определение колонок для таблицы
```

**Общие layouts:**

- [`views/layouts/main.php`](views/layouts/main.php) - основной layout
- [`views/layouts/menu.php`](views/layouts/menu.php) - главное меню
- [`views/layouts/index.php`](views/layouts/index.php) - общий layout для списков моделей, для которых нет индивидуального
- [`views/layouts/view.php`](views/layouts/view.php) - общий layout для просмотра моделей, для которых нет индивидуального

---

### `/tests` - Тесты

Подробное описание в [`tests/readme.md`](tests/readme.md)

---

## Ключевые архитектурные решения

### Базовый контроллер

[`ArmsBaseController`](controllers/ArmsBaseController.php)

Все контроллеры наследуются от базового, который предоставляет:

- CRUD операции (index,view,delete,update,create)
- async-grid - аналогично index, но без layout для отдачи в другие формы (через ajax)
- RBAC контроль доступа, выбрасывает исключения 403 и 401 в случае отсутствия доступа (см. buildAccessRules)

**Карта доступа:**

```php
public function accessMap() {
    return [
        self::PERM_VIEW => ['index', 'view', 'search'],
        self::PERM_EDIT => ['create', 'update', 'delete'],
    ];
}
```

### Базовая модель

Все модели наследуются от базовой: [`ArmsModel`](models/ArmsModel.php)
Оч подробно документирована; также документированы все использованные в ней трейты.

### История изменений (History Journal)

Для каждой важной модели создается History-модель:

```php
class CompsHistory extends HistoryModel
{
    public $masterClass = Comps::class;
}
```

**Просмотр истории:**

```php
$history = CompsHistory::find()
    ->where(['comps_id' => $id])
    ->orderBy(['timestamp' => SORT_DESC])
    ->all();
```

### 4. Many-to-Many связи

Через voskobovich/yii2-linker-behavior:

```php
class Service extends ArmsModel
{
    public function behaviors() {
        return [
            'linksBehavior' => [
                'class' => LinkerBehavior::class,
                'relations' => [
                    'user_ids' => 'users',  // М2М с пользователями
                ],
            ],
        ];
    }
    
    public function getUsers() {
        return $this->hasMany(Users::class, ['id' => 'user_id'])
            ->viaTable('services_users', ['service_id' => 'id']);
    }
}
```

**Использование:**

```php
$service->user_ids = [1, 2, 3];  // Привязка
$service->save();
```

### 5. RBAC (Role-Based Access Control)

Управление доступом через [`spo0okie/yii2-rbac-plus`](https://github.com/spo0okie/yii2-rbac-plus):

**Проверка прав:**

```php
if (Yii::$app->user->can('edit-comps')) {
    // Разрешено редактирование компьютеров
}
```

**Управление через интерфейс:**

- `/rbac/` - управление ролями и разрешениями
- `/rbac/assignments` - назначение ролей пользователям

### 6. DynaGrid - динамические таблицы

Пользователи могут настраивать:

- Какие колонки показывать
- Порядок колонок
- Сортировку по умолчанию
- Фильтры

Настройки сохраняются в БД на пользователя.

---

## Конфигурация

### Основные файлы конфигурации

#### [`config/web.php`](config/web.php) - Web-приложение

```php
'components' => [
    'db' => [...],              // Подключение к БД
    'ldap' => [...],            // LDAP/AD аутентификация
    'user' => [
        'identityClass' => 'app\models\Users',
        'enableAutoLogin' => true,
    ],
    'authManager' => [
        'class' => 'yii\rbac\DbManager',  // RBAC в БД
    ],
],
'modules' => [
    'api' => [...],             // REST API
    'rbac' => [...],            // Управление доступом
    'gridview' => [...],        // Kartik Grid
    'dynagrid' => [...],        // Динамические таблицы
],
```

#### [`config/console.php`](config/console.php) - Консольное приложение

```php
'controllerNamespace' => 'app\console\commands',
'controllerMap' => [
    'migrate' => [
        'class' => 'yii\console\controllers\MigrateController',
        'migrationNamespaces' => ['app\migrations'],
    ],
],
```

#### Локальные конфигурации (не в git)

Используются для возможности совмещения персональных настроек инстанса с default настройками, хранящимися в git.

- `config/db-local.php` - подключение к БД
- `config/web-local.php` - локальные настройки web
- `config/params-local.php` - параметры приложения

---

## Интеграции

### 1. LDAP/Active Directory

Аутентификация через [`edvlerblog/yii2-adldap-module`](https://github.com/edvlerblog/yii2-adldap-module):

```php
'ldap' => [
    'providers' => [
        'default' => [
            'hosts' => ['dc.example.com'],
            'base_dn' => 'DC=example,DC=com',
            'username' => 'ldap_user',
            'password' => 'password',
        ],
    ],
],
```

### 2. OpenAI API

Генерация описаний через LLM:

```php
$llm = new LlmClient();

// Описание ПО
$description = $llm->generateSoftwareDescription('Adobe Photoshop');
// Возвращает: {short, license, cost, description, links}

// Описание модели оборудования
$description = $llm->generateTechModelDescription(
    'Laptop',
    'Dell Latitude 5520',
    $template
);
```

### 3. Swagger/OpenAPI

Документация API генерируется автоматически через аннотации:

```php
/**
 * @OA\Get(
 *     path="/api/comps",
 *     summary="Список компьютеров",
 *     @OA\Response(response=200, description="OK")
 * )
 */
public function actionIndex() { ... }
```

**Доступ к документации:** `/swagger/`

### 4. Wiki (dokuwiki)

- Рендер ссылок на Wiki-страницы в описаниях объектов
- Возможность использования dokuwiki как внешний renderer для описаний объектов. Такое описание будет аснинхронно отправлено в интегрированную вики, отрендерено там и подгружено обратно в ARMS для отображения.

---

## Docker

В директории [`docker/`](docker) находятся файлы для контейнеризации:

```bash
docker-compose up -d
```

**Сервисы:**

- Web-сервер (Apache/Nginx)
- PHP-FPM
- MariaDB
- PHPMyAdmin (опционально)

---

## Документация

- [Структура проекта](structure.md)
- [Стандарты кодирования](standards.md)
- [Тесты](tests/readme.md)
- [Swagger/OpenAPI](swagger/readme.md)

### Встроенная документация REST API доступна по адресу: `/site/`

### Внешние ссылки

- [Wiki проекта](https://wiki.reviakin.net/инвентаризация)
- [Demo](https://inventory.reviakin.net/)

---

## Поддержка и разработка

### Структура кода

**Стандарты:**

- PSR-4 autoloading
- PSR-2 code style (частично)
- Документация методов через PHPDoc

**Best practices:**

- DRY (Don't Repeat Yourself)
- SOLID принципы
- MVC паттерн
