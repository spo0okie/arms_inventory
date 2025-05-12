<?php
//параметры для тестирования GET запросов
//{anyId} - ID первого найденного элемента в таблице
//{anyName} - name первого найденного элемента в таблице
//{skipTest} - пропустить тестирование этого запроса
use app\controllers\ArmsBaseController;
use app\controllers\HistoryController;
use app\controllers\SmsController;
use app\controllers\UiTablesColsController;
use app\controllers\UserGroupsController;
use app\helpers\StringHelper;

return [
	//Этот контроллер не имеет своей модели и таблицы БД
	'/^arms-base\/.*$/' => '{skipTest}',
	//Этот надо как-то хитро тестировать со всеми моделями
	'/^history\/.*$/' => '{skipTest}',
	//хз как его тестировать пока
	'/^lic-links\/.*$/' => '{skipTest}',
	'/^sms\/.*$/' => '{skipTest}',
	'/^site\/.*$/' => '{skipTest}',
	'/^ui-tables-cols\/.*$/' => '{skipTest}',
	'/^wiki\/.*$/' => '{skipTest}',
	//его наверно вообще надо удалить
	'/^arms\/.*$/' => '{skipTest}',
	'/^user-groups\/.*$/' => '{skipTest}',
	//тут все сложно
	'/^[a-z0-9\-]+\/editable$/' => '{skipTest}',
	'/^[a-z0-9\-]+\/access-types-form$/' => '{skipTest}',
	//TODO: для этого желательно научиться восстанавливать БД перед тестами
	'/^comps\/(absorb|addsw|rmsw|ignoreip|unignoreip)$/' => '{skipTest}',
	//кастомные методы
	'/^acls\/ace-cards$/' => '?id={anyId}',
	'/^comps\/ttip-hw$/' => '?id={anyId}',
	'/^contracts\/hint-arms$/' => '?ids=7&form=test',//в тестовой БД там есть оборудование
	'/^contracts\/hint-parent$/' => '?ids=7&form=test',//в тестовой БД там есть договор
	'/^contracts\/(scans|update\-form)$/' => '?id={anyId}',//TODO: зачем этот метод?
	'/^contracts\/link\-tech$/' => '{skipTest}',//TODO: зачем этот метод?
	'/^contracts\/(un)?link$/' => '{skipTest}',//TODO: нужно восстановление БД
	'/^lic-(groups|items|keys)\/(un)?link$/' => '{skipTest}',//TODO: нужно восстановление БД
	'/^lic-items\/hint-arms$/' => '?id=1&form=test',//в тестовой БД там есть оборудование привязанное через документ
	'/^lic-items\/contracts$/' => '?id={anyId}',//TODO: зачем этот метод?
	'/^lic-keys\/create$/' => '?LicKeys[lic_items_id]=1', //для создания лиц ключа обязательно нужна закупка
	'/^materials\/ttips$/' => '?ids={anyId}',
	'/^net-ips\/item-by-name$/' => '?name=10.20.1.10',
	'/^networks\/item-by-name$/' => '?name=10.20.1.0/24',
	'/^networks\/incoming-connections-list$/' => '?id={anyId}',
	'/^places\/map-(set|delete)$/' => '{skipTest}',
	'/^scans\/thumb$/' => '{skipTest}', //TODO: нужно восстановление БД
	'/^scheduled-access\/status$/' => '?id={anyId}',
	'/^schedules-entries\/create$/' => '?id={anyId}&SchedulesEntries[schedule_id]=1',//нужно расписание
	'/^services\/(aces\-list|acls\-list|card|card\-support|card\-maintenance\-reqs|children\-tree|json\-preview|os\-list)$/' => '?id={anyId}',
	'/^soft\/select\-update$/' => '{skipTest}', //TODO: сделать тест - это GET метод
	'/^tech\-(models|types)\/hint\-.*$/' => '?id={anyId}',
	'/^tech\-models\/item-by-name$/' => '?name=SPA 504G&manufacturer=Cisco',
	'/^techs\/(docs|edithw|invnum|port\-list|updhw|rmhw)$/' => '{skipTest}', //TODO: сделать тест - это GET метод
	'/^techs\/(rack-unit|rack-unit-validate)$/' => '{skipTest}', //TODO: сделать тест - это GET метод
	'/^techs\/ttip\-hw$/' => '?id={anyId}', //TODO: сделать тест - это GET метод
	'/^users\/item\-by\-login$/' => '?login=admin',
	//стандартные методы
	'/^[a-z0-9\-]+\/(view|item|ttip|update|card|uploads)$/' => '?id={anyId}',
	'/^[a-z0-9\-]+\/item-by-name$/' => '?name={anyName}',
];