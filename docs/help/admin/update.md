# Обновление

Порядок обновления установленной вручную системы: git pull, обновление зависимостей, миграции БД, а также откат обновления, если что-то пошло не так.

Для чистой [установки](install.md) есть отдельная инструкция.

### Перед обновлением

Если мы хотим, чтобы обновления были обратимы, то

Смотрим на каком коммите мы находимся:

```bash
git log -1
```

и запоминаем (лучше в файл) хэш текущего коммита (например c729a2b43520275f1abc8c25bd9770f5d6c43519)

Если страшно, то можно сделать [резервную копию](backup.md)

### Обновление файловой структуры

просто скачиваем свежую версию при помощи git и обновляем структуру БД применяя новые миграции:

```bash
git pull
```

После этого обновляем зависимости

```bash
composer update
```

С этого места система в общем случае неконсистентна, поэтому быстро обновляем структуру БД:

```bash
./yii migrate
```

Собственно все. Система снова консистентна и находится в последней версии.

### Если что-то пошло не так

Ну или просто раньше было лучше, то в обратном порядке делаем

#### Откат БД

Нужно делать, только если при обновлении БД были применены какие-то миграции.
Итак, нужно отменить последние миграции. Может быть вы помните сколько было применено, но на всякий случай смотрим историю миграций:

```bash
./yii migrate/history
```

считаем сколько миграций было применено при последнем обновлении (по дате применения). Пример:

```bash
./yii migrate
Yii Migration Tool (based on Yii v2.x)

Total 1 new migration to be applied:
        m191219_100002_fix_contracts_in_materials_id

Apply the above migration? (yes|no) [no]:y
*** applying m191219_100002_fix_contracts_in_materials_id
    > alter column id in table contracts_in_materials to integer NOT NULL AUTO_INCREMENT ... done (time: 0.010s)
*** applied m191219_100002_fix_contracts_in_materials_id (time: 0.025s)


1 migration was applied.

Migrated up successfully.
./yii migrate/history
Yii Migration Tool (based on Yii v2.x)

Showing the last 10 applied migrations:
        (2020-01-12 18:54:05) m191219_100002_fix_contracts_in_materials_id
        (2020-01-12 14:41:12) m191219_100001_fix_materials_id
        (2020-01-12 14:41:12) m191219_100000_add_users_employ_date
        (2020-01-12 14:41:11) m191208_173041_fix_users_id
        (2020-01-12 14:41:11) m191208_173041_fix_many_2_many
        (2020-01-12 14:41:11) m191208_164401_add_default_ip_values_in_comps
        (2020-01-12 14:41:11) m191204_062411_decimal_prices
        (2020-01-12 14:41:10) m191120_095815_add_cost_column_to_org_phones_table
        (2020-01-12 14:41:10) m191120_062411_float_prices
        (2020-01-12 14:41:10) m191119_172409_add_charge_column_to_contracts_table
```

В примере мы применяем 1 миграцию и в истории видим что дата применения миграции m191219_100002_fix_contracts_in_materials_id вот только-что, а остальные применены давно.
собственно нам надо отменить одну:

```bash
./yii migrate/down 1
```

с этого момента мы снова не консистентны.

#### Откат файлов

делаем git checkout на старый коммит:

```bash
git checkout c729a2b43520275f1abc8c25bd9770f5d6c43519
```

после чего git правда скажет, что мы находимся в состоянии detached HEAD (отцепились от ветки).

Не забываем откатить и зависимости под старую версию кода:

```bash
composer update
```

#### Вернуться на последнюю версию

надо вернуться на ветку и обновиться:

```bash
git checkout master
git pull
composer update
./yii migrate
```
