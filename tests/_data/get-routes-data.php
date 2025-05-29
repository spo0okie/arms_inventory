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
	'/^acls\/ace-cards$/' => ['GET'=>['id'=>'{anyId}']],
	'/^comps\/ttip-hw$/' => ['GET'=>['id'=>'{anyId}']],
	'/^contracts\/hint-arms$/' => ['GET'=>['ids'=>'7','form'=>'test']],//в тестовой БД там есть оборудование
	'/^contracts\/hint-parent$/' => ['GET'=>['ids'=>'7','form'=>'test']],//в тестовой БД там есть договор
	'/^contracts\/(scans|update\-form)$/' => ['GET'=>['id'=>'{anyId}']],//TODO: зачем этот метод?
	'/^contracts\/link\-tech$/' => '{skipTest}',//TODO: зачем этот метод?
	'/^contracts\/(un)?link$/' => '{skipTest}',//TODO: нужно восстановление БД
	'/^lic-(groups|items|keys)\/(un)?link$/' => '{skipTest}',//TODO: нужно восстановление БД
	'/^lic-items\/hint-arms$/' => ['GET'=>['id'=>'{anyId}','form'=>'test']],//в тестовой БД там есть оборудование привязанное через документ
	'/^lic-items\/contracts$/' => ['GET'=>['id'=>'{anyId}']],//TODO: зачем этот метод?
	'/^lic-keys\/create$/' => ['GET'=>['LicKeys'=>['lic_items_id'=>1]]], //для создания лиц ключа обязательно нужна закупка
	'/^maintenance-jobs\/children-tree$/' => ['GET'=>['id'=>'{anyId}']],
	'/^materials\/ttips$/' => ['GET'=>['ids'=>'{anyId}']],
	'/^net-ips\/item-by-name$/' => ['GET'=>['name'=>'10.20.1.10']],
	'/^networks\/item-by-name$/' => ['GET'=>['name'=>'10.20.1.0/24']],
	'/^networks\/incoming-connections-list$/' => ['GET'=>['id'=>'{anyId}']],
	'/^places\/map-(set|delete)$/' => '{skipTest}',
	'/^scans\/thumb$/' => '{skipTest}', //TODO: нужно восстановление БД
	'/^scheduled-access\/status$/' => ['GET'=>['id'=>'{anyId}']],
	'/^schedules-entries\/create$/' => ['GET'=>['id'=>'{anyId}','SchedulesEntries'=>['schedule_id'=>1]]],//нужно расписание
	'/^services\/(aces\-list|acls\-list|card|card\-support|card\-maintenance\-reqs|children\-tree|json\-preview|os\-list)$/' => ['GET'=>['id'=>'{anyId}']],
	'/^soft\/select\-update$/' => '{skipTest}', //TODO: сделать тест - это GET метод
	'/^tech\-(models|types)\/hint\-.*$/' => ['GET'=>['id'=>'{anyId}']],
	'/^tech\-models\/item-by-name$/' => ['GET'=>['name'=>'SPA 504G','manufacturer'=>'Cisco']],
	'/^techs\/(docs|edithw|invnum|port\-list|updhw|rmhw)$/' => '{skipTest}', //TODO: сделать тест - это GET метод
	'/^techs\/(rack-unit|rack-unit-validate)$/' => '{skipTest}', //TODO: сделать тест - это GET метод
	'/^techs\/ttip\-hw$/' => ['GET'=>['id'=>'{anyId}']], //TODO: сделать тест - это GET метод
	'/^users\/item\-by\-login$/' => ['GET'=>['login'=>'admin']],
	//стандартные методы
	'/^[a-z0-9\-]+\/(view|item|ttip|card|uploads|create)$/' => ['GET'=>['id'=>'{anyId}']],
	'/^[a-z0-9\-]+\/item-by-name$/' => ['GET'=>['name'=>'{anyName}']],
	'/^[a-z0-9\-]+\/delete$/' => ['POST'=>['id' => '{otherId}']],
	'/^[a-z0-9\-]+\/update$/' => ['GET'=>['id' => '{anyId}'],'POST'=>['{otherModelParams}']],
	//'/^[a-z0-9\-]+\/create$/' => ['POST'=>['{anyModelParams}']],
];