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

Для работы с изображениями установка пакетов должна была подтянуть ImageMagick.
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
2. Кодировку настойчиво рекомендуется использовать именно `utf8mb4` с collation `utf8mb4_unicode_ci`, т.к. на ней выбор остановился после устранения проблем с другими!
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

Клонируем в веб корень содержимое гит репозитория.

```bash
git clone https://github.com/spo0okie/arms_inventory.git .
chmod 755 ./yii
```

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

Приложению необходимо обеспечить доступ к файловой системе, поэтому процесс вебсервера должен иметь доступ к папкам

- web/assets
- web/scans
- web/scans/thumbs

TL;DR:

```bash
mkdir -p web/scans/thumbs
chmod 777 web/scans/thumbs
chmod 777 web/scans
chmod 777 web/assets
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

Поскольку проект создан на базе фреймворка yii2, то для управления структурой БД используется встроенный в него инструмент миграций. В любой момент времени при чистой установке или после обновления необходимо выполнить

```bash
yii migrate --migrationPath=@yii/rbac/migrations/
yii migrate
```

> В докер контейнере это делается автоматически при старте контейнера.

### Роли

Подготавливаем стандартные роли для RBAC доступа

```bash
yii rbac/init
```

### Apache

Пример файла apache2

```apache
<VirtualHost *:443>
  ServerName inventory.domain.local

  DocumentRoot "/var/www/arms"

  <Directory "/var/www/arms">
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

### Импорт данных

Из демо БД, чтобы вручную не заводить кучу оборудования, ПО, производителей и т.п.

#### Модели оборудования

(а также категории оборудования и производители)

```bash
./yii sync/tech-models https://inventory.reviakin.net/web/api guest guest1
```

#### Списки ПО

(а также само ПО и производители)

```bash
./yii sync/soft-lists https://inventory.reviakin.net/web/api guest guest1
```

#### Типы лицензий

```bash
./yii sync/lic-groups https://inventory.reviakin.net/web/api guest guest1
```

### Дальше

После установки переходим к [настройке](setup.md) авторизации и параметров приложения.
