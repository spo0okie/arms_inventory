# Zabbix: мониторинг инсталляции ARMS

Здесь лежат артефакты для **наблюдения за самим приложением ARMS** средствами
Zabbix. Не путать с направлением «ARMS собирает данные ИЗ Zabbix» — это
отдельный проект синхронизации (arms.zabbix) и интеграционные панели
([docs/dev/integrations.md](../docs/dev/integrations.md)).

- [arms-perf-template.yaml](arms-perf-template.yaml) — шаблон «ARMS perf
  monitoring»: триггеры на медленные запросы и на ошибки 5xx по логам
  инсталляции.

Как импортировать, что требуется от zabbix-agent на хосте и как разбирать
сработавшие алерты — [docs/help/admin/monitoring.md](../docs/help/admin/monitoring.md).
