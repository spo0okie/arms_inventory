# Установка (Docker)

Быстрое развертывание системы в контейнерах: подготовленная структура папок и конфигов скачивается из отдельного репозитория, миграции БД применяются автоматически при старте.

- Для [обновления](update-docker.md) есть отдельная инструкция.
- Для установки [без докера](install.md) есть отдельная инструкция.

### Быстрый старт

> **Важно:** инструкция подразумевает наличие в системе **docker** и **git**. Их установка за рамками этой инструкции.

#### Linux

```bash
#создаем папку
mkdir /opt/arms
#переходим в нее
cd /opt/arms
#скачиваем подготовленную структуру папок и конфигов
git clone https://github.com/spo0okie/arms-docker.git .
#выдаем права на монтируемые папки
chmod 777 db files log runtime
#запускаем
docker compose up -d
```

#### Windows

```batch
rem создаем папку
mkdir arms
rem переходим в нее
cd arms
rem скачиваем подготовленную структуру папок и конфигов
git clone https://github.com/spo0okie/arms-docker.git .
rem ставим атрибут только чтение на конфиг mysql, иначе он игнорируется при старте контейнера MySQL
attrib +R config/mysql.cnf
rem запускаем
docker compose up
```

Дожидаемся всех миграций БД (применяются автоматически при старте контейнера приложения) и старта интерфейса на порту 8088.
После этого первичная инициализация закончена, можно переходить к импорту.

> Настройки приложения при docker-инсталляции лежат в папке **config** рядом с docker-compose.yml: файл **config/params.php** монтируется внутрь контейнера как params-local.php (см. [настройку](setup.md)), **config/ldap.php** - как конфиг LDAP, **config/web.php** - как web-local.php (низкоуровневые переопределения Yii).

### Импорт данных

Из демо БД, чтобы вручную не заводить кучу оборудования, ПО, производителей и т.п.

#### Модели оборудования

(а также категории оборудования и производители)

> В примерах ниже используется наименование контейнера **arms-arms-app-1**, которое формирует современный docker compose для примера выше (папка **arms** + сервис **arms-app**).
> Устаревший docker-compose v1 формирует имя **arms_arms-app_1**.
> Наименование контейнеров можно посмотреть командой
> ```bash
> docker container ls
> ```

```bash
docker exec -it "arms-arms-app-1" php yii sync/tech-models https://inventory.reviakin.net/web/api guest guest1
```

#### Списки ПО

(а также само ПО и производители)

```bash
docker exec -it "arms-arms-app-1" php yii sync/soft-lists https://inventory.reviakin.net/web/api guest guest1
```

#### Типы лицензий

```bash
docker exec -it "arms-arms-app-1" php yii sync/lic-groups https://inventory.reviakin.net/web/api guest guest1
```

### Дальше

После установки переходим к [настройке](setup.md) авторизации и параметров приложения.
