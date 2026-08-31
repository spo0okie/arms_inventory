<?php

return [
	'adminEmail' => 'reviakin@gmail.com',
	//wikiUrl используется для рендера пользовательского контента с wiki-разметкой
	//(WikiController/WikiPageWidget); документация приложения - встроенная (docs/help)
	'wikiUrl'=>'https://wiki.reviakin.net/',
	'wikiUser'=>'',
    'wikiPass'=>'',
	//'docsOverridePath'=>'/path/to/local/docs',	//каталог переопределения встроенной документации
	'bsVersion' => '5.x',

	'useRBAC'=>false,   		//включить RBAC и выдавать админа только тем кому назначено
	'localAuth'=>false,   		//включить локальную БД паролей
	'authorizedView'=>false,	//запретить доступ без авторизации
	'textFields'=>[
		'default'=>'text',
		'Aces.notepad'=>'markdown',
		'Acls.notepad'=>'markdown',
		'MaintenanceJobs.description'=>'markdown',
		'MaintenanceReqs.description'=>'markdown',
		'Networks.notepad'=>'markdown',
		'Schedules.history'=>'markdown',
		'Segments.history'=>'markdown',
		'Soft.notepad'=>'markdown',
		'Users.notepad'=>'markdown',
	],
	'schedulesTZShift'=>3600*3,
	'contractsPayDocFormat'=>'Формат имени счетов следующий:<br>'.
		'<i>Счет №&lt;номер счета&gt; - ЗНП&lt;номер ЗНП&gt; - СЗ№&lt;номер Служебки по которой все это началось&gt; '.
		'а также короткое описание что, куда, кому, зачем</i><br>'.
		'Например: Счёт № 3455549 - ЗНП 5100048657 - СЗ№ 4715 от 03.11.2021 Монитор Philips 24&quot; (15 шт) в Калугу для МИГ<br>'.
		'это позволит впоследствии быстро найти счет по этим полям<br>',

	'networkDescribeSegment'=>'auto',
	'networkInlineDescriptionLimit'=>20,
	'ports.mapMinPorts'=>4,			//карта портов на корпусе рисуется от стольких портов: у устройства с одним-двумя она бессмысленна

	'departments.enable'=>false,

	'arms.docs'=>[
		'passport'=>['Паспорт рабочего места','icon'=>'<i class="fas fa-passport"></i>'],
		'act'=>['Акт приема-передачи','icon'=>'<i class="fas fa-file-contract"></i>'],
	],
	'techs.docs'=>[
		'act-single'=>['Акт приема-передачи','icon'=>'<i class="fas fa-file-contract"></i>'],
	],
	'techs.hostname.enable'=>false,
	'techs.hostname.asName'=>true,
	'techs.managementService.enable'=>false,

	'docs.pay_id.enable'=>false,
	'docs.pay_id.name'=>'ЗНП',
	'docs.name.instruction'=>false,
	'docs.max_preview_size'=>2*1024*1024,	//предельный размер документа который автоматически подгружается в форму

	'domains.default'=>'workgroup',		//какой домен подставлять в ОС и оборудование если домен явно указан не был
	'domains.fqdn_hostname'=>false,		//отображать hostname как FQDN (иначе как DOMAIN\host)

	'user.name_as_uid.enable'=>false,	//запрещаем использование полного ФИО как ключ для переназначения логина

	'services.no_backup.warn'=>true,	//ругаться если у сервиса не объявлены требования на бэкап

	'soft.deferred_rescan'=>false,

	//файл масок фильтра выгрузки нераспознанного софта (yii soft-raw/export):
	//по regexp-маске на строку (семантика как у выражений продуктов, без учёта регистра),
	//применяется к издателю и имени карточки отпечатка; # - комментарий.
	//пример: config/soft-export-filter.example
	'soft.export_filter'=>'',

	//интеграции с внешними ИС (docs/dev/integrations.md).
	//Инстанс включает свои в params-local.php; ключ = id провайдера,
	//'class' = класс провайдера, остальное - его конфиг. Пример:
	//'integrations'=>[
	//	'sms'=>[
	//		'class'=>\app\components\integrations\providers\SmsProvider::class,
	//		'url'=>'https://sms-gw.local/send?phone={phone}&text={text}',
	//	],
	//],
	//(бывший sms.enable/sms.url: отправка SMS теперь провайдер 'sms')
	'integrations'=>[],

	'ipamRanges'=>[
		['baseIp'=>'10.0.0.0','maxPrefix'=>8,'minPrefix'=>16],
		['baseIp'=>'192.168.0.0','maxPrefix'=>16,'minPrefix'=>24],
		['baseIp'=>'172.16.0.0','maxPrefix'=>16,'minPrefix'=>24],
	],

	'llm.openai.key'=>'',
	'llm.openai.proxy'=>'',

	//механизм e-mail-оповещений (plans/notifications.md): мастер-рубильник.
	//Выключен (по умолчанию) - события (смена статуса документа) и правила notifyRules
	//НЕ ставят письма в очередь notifications: без настроенных SMTP и cron очередь
	//росла бы вечно. Включать вместе с настройкой mailer.* и cron notify/send.
	'notify.enable'=>false,
	//точечный выключатель событийных оповещений о смене статуса документа:
	//false = статусные письма не ставятся, но правила notifyRules (notify/watch)
	//продолжают работать. Действует только при включённом notify.enable.
	'notify.docs.state.enable'=>true,

	//отправка почты (механизм оповещений, plans/notifications.md)
	'mailer.useFileTransport'=>true,	//true = складывать письма в runtime/mail вместо реальной отправки
	'mailer.transport'=>['scheme'=>'smtp','host'=>'localhost','port'=>25],
	'mailer.from'=>'',					//адрес отправителя; пусто = adminEmail
	'web.hostInfo'=>'',					//базовый URL приложения для абсолютных ссылок в письмах (http://arms.example.com)

	//правила оповещений о "залежавшихся" документах для notify/watch (примеры в plans/notifications.md)
	'notifyRules'=>[],

	//мониторинг производительности (docs/help/admin/monitoring.md)
	//порог журнала медленных запросов (runtime/logs/perf.log), сек; 0 = выключить
	'perf.slow_request_seconds'=>3,
	//access-лог(и) Apache для отчёта yii perf/report: список масок через запятую,
	//.gz читается прозрачно (маска захватывает ротированные куски - лишние дни отсекает фильтр по дате)
	'perf.access_log'=>'/var/log/apache2/inventory.http_access.log*',
];
