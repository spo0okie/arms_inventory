# arms_demo.sql — канонический дамп тестовой БД

**Важно: CI не применяет миграции.** Workflow
(`.github/workflows/docker-build.yml`) загружает этот дамп как есть и сразу
гоняет тесты. Поэтому **любая миграция, меняющая схему, обязана
сопровождаться регенерацией дампа** — иначе CI не увидит новых
таблиц/колонок. Часть тестов сама перезаливает дамп поверх текущей БД
(`Helper\Database::loadSqlDump`, например `ApiSchemaResolvableTest`), так что
локально «домигрированная» БД живёт только до первого такого теста.

## Обновление БД новыми миграциями (локально)

- Поднять MySQL тестовой БД (127.0.0.1:3306, root без пароля; на Windows-dev
  это WAMP: `C:\wamp\bin\mysql\mysql9.1.0\bin\mysqld.exe --defaults-file=...my.ini`).
- Загрузить дамп: `mysql -h 127.0.0.1 -u root < tests/_data/arms_demo.sql`
- Применить миграции консолью с тестовым конфигом:
  `php tests/bin/yii migrate --interactive=0`
  (использует `config/test-console.php` → БД `arms_test`)

## Демо-данные (tests/_data/demo-seed)

Дамп — ещё и БД демо-сайта. Данные под новые возможности приложения
досеиваются скриптами `demo-seed/NN-*.sql` (план и состав — `plans/demo-data.md`),
которые накатываются **поверх свежезалитого дампа** по порядку номеров:

```bash
tests/_data/demo-seed/apply.sh            # залить дамп и накатить все сиды
tests/_data/demo-seed/apply.sh --seeds    # только сиды, дамп не трогать
```

Итог пересохраняется обратно в дамп (см. ниже) — сиды остаются в репозитории
как ревьюабельная и повторяемая версия того, что в дампе лежит одной кашей.

Правила сидов:

- новые строки получают явные id от 9000 (демо-диапазон), поэтому каждый файл
  начинается с `DELETE ... WHERE id>=9000` по своим таблицам и применяется
  повторно без последствий;
- правки существующих строк — только адресные `UPDATE ... WHERE id=...`;
- у таблиц с `ON UPDATE CURRENT_TIMESTAMP` (comps, soft, tech_models,
  tech_types, techs, lic_groups, lic_types, partners, manufacturers*) дата
  задаётся в `SET` явно — иначе каждый прогон менял бы её на «сейчас» и давал
  шумный diff дампа.

## Сохранение нового дампа

Схема тестовой БД изменилась (применены новые миграции) — фиксируем:

```bash
mysqldump -h 127.0.0.1 -u root --databases arms_test \
  --add-drop-database --routines --skip-dump-date \
  > tests/_data/arms_demo.sql
```

- `--skip-dump-date` обязателен — иначе каждый дамп даёт мусорный diff.
- mysqldump на Windows пишет CRLF, в репозитории дамп хранится с LF —
  привести: `sed -i 's/\r$//' tests/_data/arms_demo.sql`
- Проверить diff глазами: кроме ваших таблиц/колонок и таблицы `migration`
  ничего меняться не должно.

## Чистка БД

Перед сохранением дампа можно выкинуть неиспользуемый мусор:

```sql
USE arms_test;
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
