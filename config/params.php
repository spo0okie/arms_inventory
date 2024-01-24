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
	'userSyncFields'=>[
		'Orgeh' => 'SAP',
		'Doljnost' => 'SAP',
		'Ename' => 'SAP',
		'Persg' => 'SAP',
		'Uvolen' => 'SAP',
		'Login' => 'AD',
		'Email' => 'AD',
		'Phone' => 'AD',
		'Mobile' => 'AD',
		'work_phone' => 'AD',
		'Bday' => 'SAP',
		'manager_id' => 'SAP',
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
	'docs.pay_id.enable'=>false,
	'docs.pay_id.name'=>'ЗНП',
	'docs.name.instruction'=>false,
];