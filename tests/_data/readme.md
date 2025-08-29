### Обновление БД
 - Загружаем дамп ```mysql -u root -p < tests\_data\arms_demo.sql```
 - Применяем миграции используя консольный конфиг с тестовой БД: ```yii.bat migrate --appconfig=config/test-console-acceptance.php```

### Сохранение дампа БД
Мы что-то изменили в структуре тестовой БД и хотим сохранить новый дамп.
- Сохраняем предыдущий дамп: ```rename tests\_data\arms_demo.sql arms_demo.bak```
- Делаем новый дамп: ```mysqldump -u root -p --routines --events --triggers --add-drop-database --add-drop-table --set-gtid-purged=OFF --databases arms_test_crud > tests\_data\arms_demo.sql```
