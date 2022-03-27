<?php

return [
	'adminEmail' => 'reviakin@gmail.com',
	'wikiUrl'=>'https://wiki.azimuth.holding.local/',
	'hintUrl'=>'https://wiki.azimuth.holding.local/инвентаризация:',
	'wikiUser'=>'inventory_doku_ro',
    'wikiPass'=>'m8-W8CZSZ(wANqL+',
	'bsVersion' => '5.x',

	'useRBAC'=>false,   //включить RBAC и выдавать админа только тем кому назначено
	'authorizedView'=>true,
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
];