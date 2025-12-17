## Обновление БД
 - Загружаем дамп ```mysql -u root -p < tests\_data\arms_demo.sql```
 - Применяем миграции используя консольный конфиг с тестовой БД: ```yii.bat migrate --appconfig=config/test-console-acceptance.php```

## Сохранение дампа БД
Мы что-то изменили в структуре тестовой БД и хотим сохранить новый дамп.
- Сохраняем предыдущий дамп: ```rename tests\_data\arms_demo.sql arms_demo.bak```
- Делаем новый дамп: ```mysqldump -u root -p --routines --events --triggers --add-drop-database --add-drop-table --set-gtid-purged=OFF --databases arms_test_crud > tests\_data\arms_demo.sql```

## Чистка БД

```sql
USE arms_test_crud;
DELETE FROM soft s
WHERE
    NOT EXISTS (SELECT 1 FROM soft_in_lists sil WHERE sil.soft_id = s.id)
  AND NOT EXISTS (SELECT 1 FROM soft_in_comps sic WHERE sic.soft_id = s.id)
  AND NOT EXISTS (SELECT 1 FROM soft_hits sh WHERE sh.soft_id = s.id)
  AND NOT EXISTS (SELECT 1 FROM soft_in_lics sl WHERE sl.soft_id = s.id);

DELETE FROM manufacturers_dict md
WHERE md.manufacturers_id IN (
    SELECT m.id
    FROM manufacturers m
    WHERE
        NOT EXISTS (
            SELECT 1 FROM soft s
            WHERE s.manufacturers_id = m.id
        )
      AND NOT EXISTS (
        SELECT 1 FROM tech_models tm
        WHERE tm.manufacturers_id = m.id
    )
);
DELETE FROM manufacturers m
WHERE
    NOT EXISTS (
        SELECT 1 FROM soft s
        WHERE s.manufacturers_id = m.id
    )
  AND NOT EXISTS (
    SELECT 1 FROM tech_models tm
    WHERE tm.manufacturers_id = m.id
);
```