### Обновление БД
Предполагаем, что у нас возникла проблема с тестовой БД, т.к. она отстала от актуальной версии и тесты не прошли.
Тогда у нас тестовый скрипт не удалил (после неудачи) тестовую БД и вопрос как ее развернуть из дампа мы опускаем.

 - Применяем миграции используя консольный конфиг с тестовой БД: ```yii.bat migrate --appconfig=config/test-console-acceptance.php```
 - Сохраняем предыдущий дамп: ```mv tests/_data/arms_demo.sql tests/_data/arms_demo.bak```
 - Делаем новый дамп: ```mysqldump -u root -p --routines --events --triggers --add-drop-database --add-drop-table --set-gtid-purged=OFF --databases arms_test_crud > tests/_data/arms_demo.sql```