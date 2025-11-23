<?php

return [
	'adminEmail' => 'reviakin@gmail.com',
	'wikiUrl'=>'https://wiki.reviakin.net/',
	'hintUrl'=>'https://wiki.reviakin.net/инвентаризация:',
	'wikiUser'=>'',
    'wikiPass'=>'',
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
	
	'departments.enable'=>false,
	
	'arms.docs'=>[
		'passport'=>['Паспорт рабочего места','icon'=>'<i class="fas fa-passport"></i>'],
		'act'=>['Акт приема-передачи','icon'=>'<i class="fas fa-file-contract"></i>'],
	],
	'techs.docs'=>[
		'act-single'=>['Акт приема-передачи','icon'=>'<i class="fas fa-file-contract"></i>'],
	],
	'techs.hostname.enable'=>false,
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
	
	'sms.enable'=>false,
	
	'ipamRanges'=>[
		['baseIp'=>'10.0.0.0','maxPrefix'=>8,'minPrefix'=>16],
		['baseIp'=>'192.168.0.0','maxPrefix'=>16,'minPrefix'=>24],
		['baseIp'=>'172.16.0.0','maxPrefix'=>16,'minPrefix'=>24],
	],
	
	'llm.openai.key'=>'',
	'llm.openai.proxy'=>'',
];