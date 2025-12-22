# Список недостатков OpenAPI документации


## Проблемы
 - LicGroups/Search, LicItems/Search, LicKeys/Search - не стандартный поиск, не описан фильтр. Используются в lib_inventory - надо там переделать на filter для единого подхода (предварительно реализовав filter в API)
 - LoginJournal/Search - не стандартный поиск, не описан фильтр (возможно что вообще не нужен. в usr_logon_inventory не используется)
 - LoginJournal/Push - надо описать
 - NetIps/FirstUnused - надо описать
 - Scans/Upload,Download - надо описать
 - Schedules - все методы надо описать
 - Techs/SearchByMac,SearchByUser - надо описать
 - Users/Whoami - надо описать