# Установка

Инструкция по ручной установке системы на веб-сервер: PHP-модули, база данных, файловая структура, зависимости, миграции и первичный импорт данных.

Для [обновления](update.md) есть отдельная инструкция.
Для установки с [docker](install-docker.md) есть отдельная инструкция.

### PHP + модули

Требуется PHP **8.1 или новее** (см. `composer.json`). Пример установки для Debian/Ubuntu:

```bash
apt install php php-mbstring php-ldap php-xml php-mysql php-gd php-intl php-gmp php-imagick php-zip php-curl php-bcmath
```

### Доп. ПО

Для работы с изображениями установка пакетов должна подтянуть ImageMagick.
В файле **/etc/ImageMagick-6/policy.xml** (путь зависит от версии, может быть **/etc/ImageMagick-7/policy.xml**) перед строкой

```xml
</policymap>
```

вставьте строку:

```xml
<policy domain="coder" rights="read | write" pattern="PDF" />
```

это даст возможность обрабатывать PDF файлы.

### Создание БД и настройка прав доступа к ней

**NOTES:**

1. Создание БД находится за рамками этого руководства. БД должна быть создана и доступ к ней должен быть предоставлен по логину-паролю. Приведенные здесь инструкции просто пример.
2. Кодировку настоятельно рекомендуется использовать именно `utf8mb4` с collation `utf8mb4_unicode_ci`, т.к. на ней выбор остановился после устранения проблем с другими!
3. На старых версиях MySQL (<5.7.7) / MariaDB (<10.2) могут понадобиться настройки (на современных версиях не требуются):
   ```ini
   [innodb]
   innodb_strict_mode = OFF;
   innodb_large_prefix = true
   ```

*Пример создания БД достаточной для работы*

```sql
CREATE DATABASE arms character set utf8mb4 collate utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON arms.* TO 'arms-user'@'localhost' IDENTIFIED BY 'secret-password';
```

### Файловая структура

Клонируем содержимое git-репозитория в папку проекта (например **/var/www/arms**).

```bash
git clone https://github.com/spo0okie/arms_inventory.git .
chmod 755 ./yii
```

> **Корнем сайта (DocumentRoot) должна быть подпапка `web` проекта**, а не сам проект:
> `/var/www/arms/web`. Наружу тогда смотрит только то, что и должно быть доступно
> браузеру, а код, конфиги с паролями (`config/db-local.php`) и `runtime` остаются
> вне корня сайта. Адреса при такой публикации выглядят как
> `https://inventory.domain.local/techs/index`.
>
> Инсталляции, развернутые по прежней редакции этой инструкции, публиковались иначе - DocumentRoot указывал
> на корень проекта, а лежащий там `.htaccess` переписывал запросы в `web/`. Из-за этого
> во всех адресах присутствовал лишний токен: `https://inventory.domain.local/web/techs/index`.
> Такие адреса продолжают работать и на новой публикации (см. [Адреса приложения](#адреса-приложения)),
> так что перенастраивать интеграции разом не требуется. Порядок перехода описан
> в инструкции по [обновлению](update.md#переход-на-адреса-без-web).

Это установит все уникальные для этого проекта файлы, но не используемые им сторонние модули, которые поддерживаются другими разработчиками.

### Установка зависимостей

Проект в своей структуре содержит все ссылки на необходимые модули других производителей. Они устанавливаются через [composer](https://getcomposer.org/) (уже должен присутствовать в системе, инструкции по установке есть на [getcomposer.org](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos)):

```bash
composer update
```

> Для боевой инсталляции можно добавить ключ `--no-dev`, чтобы не ставить пакеты для разработки и тестирования (так собирается docker-образ).

### Доступ к папкам

Нужно создать папку для превью картинок

- web/scans/thumbs

Приложению необходимо обеспечить доступ к файловой системе, поэтому процесс вебсервера должен иметь доступ на запись к папкам

- web/assets
- web/scans
- web/scans/thumbs
- runtime

TL;DR:

```bash
mkdir -p web/scans/thumbs
chmod 777 web/scans/thumbs
chmod 777 web/scans
chmod 777 web/assets
chmod 777 runtime
```

### Локальные конфиг файлы

Создаем пустые конфиг файлы для персональных настроек инсталляции

**config/params-local.php**

```php
<?php
return[];
```

**config/web-local.php**

```php
<?php
return[];
```

**config/db-local.php** должен содержать реальные учетные данные БД для инвентаризации ([настроить БД](setup.md))

```php
<?php
return [
    'dsn' => 'mysql:host=localhost;dbname=arms',
    'username' => 'arms-user',
    'password' => 'secret-password',
];
```

**config/ldap.php** должен содержать корректный конфиг, даже если LDAP авторизация не будет использоваться

```php
<?php
return [
    'class'=>'Edvlerblog\Adldap2\Adldap2Wrapper',
    'providers'=> [
        'default'=>[
            'autoconnect'=>true,
            'config'=>[
                'port'      => 636,
                //'port'      => 389,
                'hosts'    => ['dc1.domain.local','dc2.domain.local'],
                'account_suffix' =>  '@domain.local',
                'base_dn' => "DC=domain,DC=local",
                //под кем подключиться к АД (подойдет любой пользователь. права админа не нужны)
                'username' => 'inventory@domain.local',
                'password' => 'SuperSecretPassword1!',
                'use_ssl'   => true,
                'use_tls'   => true,
                'custom_options'   => [
                    // See: http://php.net/ldap_set_option
                    //LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER
                ],
            ],
        ],
    ],
];
```

### Создание таблиц

Поскольку проект создан на базе фреймворка yii2, то для управления структурой БД используется встроенный в него инструмент миграций. При чистой установке (и после каждого обновления) необходимо выполнить

```bash
./yii migrate --migrationPath=@yii/rbac/migrations/
./yii migrate
```

> В докер контейнере это делается автоматически при старте контейнера.

### Роли

Создаем базовую роль **admin** для RBAC (нужно, даже если включать RBAC планируется позже, — см. [настройку авторизации](setup.md#авторизация)):

```bash
./yii rbac/init
```

### Apache

Должен быть включен модуль `rewrite`, а для каталога `web` разрешен `AllowOverride All` — иначе не сработает `web/.htaccess`, который отдает pretty-URL в `index.php`.

Пример файла apache2

```apache
<VirtualHost *:443>
  ServerName inventory.domain.local

  #корень сайта - подпапка web проекта, а не сам проект
  DocumentRoot "/var/www/arms/web"

  <Directory "/var/www/arms/web">
    Options -Indexes +Includes
    AllowOverride All
    Require all granted
  </Directory>

  ErrorLog "/var/log/apache2/inventory.https_error_ssl.log"
  ServerSignature Off
  CustomLog "/var/log/apache2/inventory.https_access_ssl.log" combined

  SSLEngine on
  SSLCertificateFile      "/etc/ssl/certs/inventory.cer"
  SSLCertificateKeyFile   "/etc/ssl/private/private.key"
  SSLProtocol              -all +TLSv1.2 +TLSv1.3
  SSLOptions +ExportCertData
</VirtualHost>
```

### Адреса приложения

При каноническом DocumentRoot (`<проект>/web`) приложение живет на чистых адресах:

| Что | Адрес |
|---|---|
| интерфейс | `https://inventory.domain.local/techs/index` |
| REST API | `https://inventory.domain.local/api/users` |
| статика | `https://inventory.domain.local/css/custom.css` |

**Старые адреса с `/web/` продолжают работать** - `https://inventory.domain.local/web/api/users`
обслуживается ровно тем же кодом, что и `/api/users`. Сделано это для инсталляций, выросших
из прежней схемы публикации: перенастроить разом все интеграции (скрипты синхронизации,
телефонию, плагин wiki, закладки сотрудников, ссылки в самой wiki) невозможно.

Как это устроено:

- `web/.htaccess` срезает префикс на уровне веб-сервера, поэтому по старому адресу отдается
  и статика (`/web/css/custom.css`);
- `app\components\Request` срезает тот же префикс из пути запроса, иначе роутер искал бы
  несуществующий контроллер `web`.

Совместимость работает **без редиректа**: метод запроса и тело сохраняются, поэтому
POST/PUT-интеграции не ломаются. При этом сами страницы всегда рисуют канонические ссылки,
так что открытая по старому адресу страница «вылечивается» после первого же перехода.

Когда все интеграции переведены на чистые адреса, слой совместимости можно выключить -
в **config/web-local.php**:

```php
<?php
return [
    'components' => [
        //старые адреса /web/... начнут отдавать 404
        'request' => ['legacyPathPrefix' => null],
    ],
];
```

> Если веб-сервер не apache, `.htaccess` не читается, и оба правила надо перенести в его конфиг.
> Для nginx это выглядит так:
> ```nginx
> root /var/www/arms/web;
> #совместимость со старой схемой публикации
> rewrite ^/web/(.*)$ /$1 last;
> location / {
>     try_files $uri $uri/ /index.php$is_args$args;
> }
> ```

### Импорт данных

Из демо БД, чтобы вручную не заводить кучу оборудования, ПО, производителей и т.п.


#### Модели оборудования

(а также категории оборудования и производители)

```bash
./yii sync/tech-models https://inventory.reviakin.net/api guest guest1
```

#### Списки ПО

(а также само ПО и производители)

```bash
./yii sync/soft-lists https://inventory.reviakin.net/api guest guest1
```

#### Типы лицензий

```bash
./yii sync/lic-groups https://inventory.reviakin.net/api guest guest1
```

### Дальше

После установки переходим к [настройке](setup.md) авторизации и параметров приложения.
