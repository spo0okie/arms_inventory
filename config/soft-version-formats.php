<?php

// Форматы описывают строку версии. Имена групп совпадают с ключами полей.
return [
	'debian-stable'=>[
		'label'=>'Debian stable update (+debNuM)',
		'pattern'=>'/\A(?<base>(?<upstream>(?:[0-9]+:)?[0-9][a-zA-Z0-9.+:~\-]*)-(?<revision>[0-9a-zA-Z.+~]+))\+deb(?<release>[0-9]+)u(?<update>[0-9]+)\z/D',
		'fields'=>[
			'base'=>['Базовая версия пакета','string'],
			'release'=>['Релиз Debian','integer'],
			'update'=>['Обновление Debian','integer'],
		],
	],
	'ubuntu'=>[
		'label'=>'Ubuntu (NubuntuM)',
		'pattern'=>'/\A(?<base>(?:[0-9]+:)?[0-9][a-zA-Z0-9.+:~\-]*-[0-9]+)ubuntu(?<revision>[0-9]+(?:\.[0-9]+)*)\z/D',
		'fields'=>[
			'base'=>['Базовая версия пакета','string'],
			'revision'=>['Ревизия Ubuntu','string'],
		],
	],
];
