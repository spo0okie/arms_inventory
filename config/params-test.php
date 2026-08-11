<?php return array (
	//механизм оповещений в тестах включён: unit/acceptance проверяют сам механизм
	//(в боевом дефолте notify.enable=false - opt-in инсталляции)
	'notify.enable'=>true,

	//механизм интеграций: sms-провайдер включён для проверки маршрутов
	//(acceptance) и самого провайдера (unit). data:// вместо http://, чтобы
	//даже случайный вызов отправки не ходил в сеть.
	'integrations'=>[
		'sms'=>[
			'class'=>\app\components\integrations\providers\SmsProvider::class,
			'url'=>'data://text/plain,TEST-OK',
		],
	],
);
