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

//для второго update надо добавить regexp выражение с суффиксом [2]
//и при обходе маршрутов искать маршруты [2] [3] ... [n] пока они будут находиться

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
	'/^attaches\/(create|update)$/' => '{skipTest}',//надо файл загружать; потом разберемся как
	'/^comps\/ttip-hw$/' => ['GET'=>['id'=>'{anyId}']],
	'/^contracts\/hint-arms$/' => ['GET'=>['ids'=>'7','form'=>'test']],//в тестовой БД там есть оборудование
	'/^contracts\/hint-parent$/' => ['GET'=>['ids'=>'7','form'=>'test']],//в тестовой БД там есть договор
	'/^contracts\/(scans|update\-form)$/' => ['GET'=>['id'=>'{anyId}']],//TODO: зачем этот метод?
	'/^contracts\/link\-tech$/' => '{skipTest}',//TODO: зачем этот метод?
	'/^contracts\/(un)?link$/' => '{skipTest}',//TODO: нужно восстановление БД
	'/^lic-(groups|items|keys)\/(un)?link$/' => '{skipTest}',//TODO: нужно восстановление БД
	'/^lic-groups\/delete$/' => ['GET'=>['id'=>'9'],		'POST'=>[],					//делаем это POST запросом
		'saveModel'=>['storeAs'=>'deleted','model'=>['id' => '9']],	//сохраняем ее параметры перед этим, для дальнейшего использования
		'dropReverseLinks'=>['id'=>'9'],		//удаляем обратные связи (иначе не удалить)
		'response'=>'302'			//ответ всегда 302 (переадресация на index)
	],
	'/^lic-items\/hint-arms$/' => ['GET'=>['id'=>'{anyId}','form'=>'test']],//в тестовой БД там есть оборудование привязанное через документ
	'/^lic-items\/contracts$/' => ['GET'=>['id'=>'{anyId}']],//TODO: зачем этот метод?
	'/^lic-items\/delete$/' => ['GET'=>['id'=>'4'],		'POST'=>[],					//делаем это POST запросом
		'saveModel'=>['storeAs'=>'deleted','model'=>['id' => '4']],	//сохраняем ее параметры перед этим, для дальнейшего использования
		'dropReverseLinks'=>['id'=>'4'],		//удаляем обратные связи (иначе не удалить)
		'response'=>'302'			//ответ всегда 302 (переадресация на index)
	],
	'/^lic-keys\/create$/' => ['GET'=>['LicKeys'=>['lic_items_id'=>2]]], //для создания лиц ключа обязательно нужна закупка
	'/^lic-keys\/create\[1\]$/' => ['GET'=>['LicKeys'=>['lic_items_id'=>2]],'POST'=>['{replacedModelParams}'],'response'=>['302','200']], //для создания лиц ключа обязательно нужна закупка
	'/^login-journal\/update$/' => '{skipTest}', //нельзя править записи журнала входов
	'/^maintenance-jobs\/children-tree$/' => ['GET'=>['id'=>'{anyId}']],
	'/^materials\/ttips$/' => ['GET'=>['ids'=>'{anyId}']],
	'/^net-ips\/item-by-name$/' => ['GET'=>['name'=>'10.20.1.11']],
	'/^networks\/item-by-name$/' => ['GET'=>['name'=>'10.20.1.0/24']],
	'/^networks\/incoming-connections-list$/' => ['GET'=>['id'=>'{anyId}']],
	'/^places\/map-(set|delete)$/' => '{skipTest}',
	'/^scans\/thumb$/' => '{skipTest}', //TODO: нужно восстановление БД
	'/^scheduled-access\/status$/' => ['GET'=>['id'=>'{anyId}']],
	'/^schedules-entries\/create$/' => ['GET'=>['id'=>'{anyId}','SchedulesEntries'=>['schedule_id'=>6]]],//нужно расписание
	'/^schedules-entries\/create\[1\]$/' => ['GET'=>['id'=>'{anyId}','SchedulesEntries'=>['schedule_id'=>6]],'POST'=>['{replacedModelParams}'],'response'=>['302','200']],//нужно расписание
	'/^services\/(aces\-list|acls\-list|card|card\-support|card\-maintenance\-reqs|children\-tree|json\-preview|os\-list)$/' => ['GET'=>['id'=>'{anyId}']],
	'/^soft\/select\-update$/' => '{skipTest}', //TODO: сделать тест - это GET метод
	'/^tech\-(models|types)\/hint\-(comment|description|template)$/' => ['GET'=>['id'=>'{anyId}']],
	'/^tech\-models\/item-by-name$/' => ['GET'=>['name'=>'G430','manufacturer'=>'Avaya']],
	'/^tech\-models\/render-rack$/' => '{skipTest}', //TODO: сделать тест - надо передавать конфигурацию корзины
	'/^techs\/(docs|edithw|invnum|port\-list|updhw|rmhw)$/' => '{skipTest}', //TODO: сделать тест - это GET метод
	'/^techs\/(rack-unit|rack-unit-validate)$/' => '{skipTest}', //TODO: сделать тест - это GET метод
	'/^techs\/ttip\-hw$/' => ['GET'=>['id'=>'{anyId}']], //TODO: сделать тест - это GET метод
	'/^users\/item\-by\-login$/' => ['GET'=>['login'=>'guest']],
	//стандартные методы
	'/^[a-z0-9\-]+\/(view|item|ttip|card|uploads)$/' => ['GET'=>['id'=>'{anyId}']],
	'/^[a-z0-9\-]+\/item-by-name$/' => ['GET'=>['name'=>'{anyName}']],
	'/^[a-z0-9\-]+\/delete$/' => [
		'GET'=>['id' => '{anyId}'],	//удаляем любую имеющуюся модель
		'POST'=>[],					//делаем это POST запросом
		'saveModel'=>['storeAs'=>'deleted','model'=>['id' => '{anyId}']],	//сохраняем ее параметры перед этим, для дальнейшего использования
		'dropReverseLinks'=>['id'=>'{anyId}'],		//удаляем обратные связи (иначе не удалить)
		'response'=>'302'			//ответ всегда 302 (переадресация на index)
	],
	'/^[a-z0-9\-]+\/validate$/' =>	['POST'=>['{deletedModelParams}']],
	'/^[a-z0-9\-]+\/update$/' =>	['GET'=>['id' => '{anyId}']],
	'/^[a-z0-9\-]+\/update\[1\]$/' => [
		'GET'=>['id' => '{anyId}'],	//заменяем любую имеющуюся модель
		'saveModel'=>['storeAs'=>'replaced','model'=>['id' => '{anyId}']],	//сохраняем ее параметры перед этим, для дальнейшего использования
		'POST'=>['{deletedModelParams}'], //заменяем ее на параметры сохраненной удаленной модели
		'response'=>['302','200'],		//200 может вернуться при ошибке валидации бывало такое в тестах, когда
		// валидные до удаления данные приводили к рекурсивной ссылке на самого себя при их записи в другую модель
	],
	'/^[a-z0-9\-]+\/create$/' => [],
	'/^[a-z0-9\-]+\/create\[1\]$/' => ['POST'=>['{replacedModelParams}'],'response'=>['302','200']], //200 может вернуться при ошибке валидации
];