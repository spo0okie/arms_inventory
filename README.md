<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">БД для инвентаризации ИТ инфрастуктуры</h1>
    <h4 align="center">на базе Yii 2 Basic Project Template</h4>
    <br>
</p>

FEATURES/ВОЗМОЖНОСТИ
- [Учет оборудования](https://inventory.reviakin.net/web/techs/index)
  - [Учет используемых на предприятии моделей оборудования](https://inventory.reviakin.net/web/tech-models/index)
  - [Компоновка стоек и шкафов](https://inventory.reviakin.net/web/techs/view?id=18)
  - [Учет портов](https://inventory.reviakin.net/web/techs/view?id=12)
- [Учет операционных систем](https://inventory.reviakin.net/web/comps/index)
- [Компоновка рабочих мест](https://inventory.reviakin.net/web/places/armmap)
- [Учет предоставляемых ИТ отделом услуг и сервисов](https://inventory.reviakin.net/web/services/index?showChildren=1)
  - [Распределение сервисов по ответственным](https://inventory.reviakin.net/web/services/index-by-users)
- [Учет лицензий](https://inventory.reviakin.net/web/lic-groups/index)
  - [Учет ключей](https://inventory.reviakin.net/web/lic-items/view?id=1)
- [Учет сегментов инфраструры](https://inventory.reviakin.net/web/segments/index)
- [Учет сетей](https://inventory.reviakin.net/web/networks/index), [Vlan](https://inventory.reviakin.net/web/net-vlans/index), [IP Адресов](https://inventory.reviakin.net/web/networks/view?id=12)
  - [Учет вводов интернет](https://inventory.reviakin.net/web/org-inet/index) и [подключений телефонии](https://inventory.reviakin.net/web/org-phones/index) c [привязкой к договорам](https://inventory.reviakin.net/web/services/view?id=2)
- [Ведение расписаний](https://inventory.reviakin.net/web/schedules/view?id=4)
- [Учет временных доступов](https://inventory.reviakin.net/web/scheduled-access/view?id=6)
  - [В т.ч. сотрудникам внешних организаций](https://inventory.reviakin.net/web/partners/view?id=2)


REQUIREMENTS/ТРЕБОВАНИЯ
------------

Минимальная версия PHP 7.1
MySql 5.5.3


INSTALLATION/УСТАНОВКА
------------

[Установка](https://wiki.reviakin.net/%D0%B8%D0%BD%D0%B2%D0%B5%D0%BD%D1%82%D0%B0%D1%80%D0%B8%D0%B7%D0%B0%D1%86%D0%B8%D1%8F:%D1%83%D1%81%D1%82%D0%B0%D0%BD%D0%BE%D0%B2%D0%BA%D0%B0)

CONFIGURATION/НАСТРОЙКА
-------------

[Настройка](https://wiki.reviakin.net/%D0%B8%D0%BD%D0%B2%D0%B5%D0%BD%D1%82%D0%B0%D1%80%D0%B8%D0%B7%D0%B0%D1%86%D0%B8%D1%8F:%D0%BD%D0%B0%D1%81%D1%82%D1%80%D0%BE%D0%B9%D0%BA%D0%B0)


TESTING
-------

Tests are located in `tests` directory. They are developed with [Codeception PHP Testing Framework](http://codeception.com/).
By default there are 3 test suites:

- `unit`
- `functional`
- `acceptance`

Tests can be executed by running

```
vendor/bin/codecept run
```

The command above will execute unit and functional tests. Unit tests are testing the system components, while functional
tests are for testing user interaction. Acceptance tests are disabled by default as they require additional setup since
they perform testing in real browser. 


### Running  acceptance tests

To execute acceptance tests do the following:  

1. Rename `tests/acceptance.suite.yml.example` to `tests/acceptance.suite.yml` to enable suite configuration

2. Replace `codeception/base` package in `composer.json` with `codeception/codeception` to install full featured
   version of Codeception

3. Update dependencies with Composer 

    ```
    composer update  
    ```

4. Download [Selenium Server](http://www.seleniumhq.org/download/) and launch it:

    ```
    java -jar ~/selenium-server-standalone-x.xx.x.jar
    ```

    In case of using Selenium Server 3.0 with Firefox browser since v48 or Google Chrome since v53 you must download [GeckoDriver](https://github.com/mozilla/geckodriver/releases) or [ChromeDriver](https://sites.google.com/a/chromium.org/chromedriver/downloads) and launch Selenium with it:

    ```
    # for Firefox
    java -jar -Dwebdriver.gecko.driver=~/geckodriver ~/selenium-server-standalone-3.xx.x.jar
    
    # for Google Chrome
    java -jar -Dwebdriver.chrome.driver=~/chromedriver ~/selenium-server-standalone-3.xx.x.jar
    ``` 
    
    As an alternative way you can use already configured Docker container with older versions of Selenium and Firefox:
    
    ```
    docker run --net=host selenium/standalone-firefox:2.53.0
    ```

5. (Optional) Create `yii2_basic_tests` database and update it by applying migrations if you have them.

   ```
   tests/bin/yii migrate
   ```

   The database configuration can be found at `config/test_db.php`.


6. Start web server:

    ```
    tests/bin/yii serve
    ```

7. Now you can run all available tests

   ```
   # run all available tests
   vendor/bin/codecept run

   # run acceptance tests
   vendor/bin/codecept run acceptance

   # run only unit and functional tests
   vendor/bin/codecept run unit,functional
   ```

### Code coverage support

By default, code coverage is disabled in `codeception.yml` configuration file, you should uncomment needed rows to be able
to collect code coverage. You can run your tests and collect coverage with the following command:

```
#collect coverage for all tests
vendor/bin/codecept run -- --coverage-html --coverage-xml

#collect coverage only for unit tests
vendor/bin/codecept run unit -- --coverage-html --coverage-xml

#collect coverage for unit and functional tests
vendor/bin/codecept run functional,unit -- --coverage-html --coverage-xml
```

You can see code coverage output under the `tests/_output` directory.
