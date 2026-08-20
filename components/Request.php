<?php

namespace app\components;

/**
 * Запрос с поддержкой легаси-префикса `/web/` в пути.
 *
 * Историческая справка: первый релиз публиковался так, что DocumentRoot указывал
 * на корень проекта, а лежащий там .htaccess переписывал запросы в подпапку `web/`.
 * Из-за этого Yii видел `SCRIPT_NAME=/web/index.php`, вычислял `baseUrl=/web` и
 * заводил лишний токен `web` во ВСЕ адреса приложения (`/web/techs/index`,
 * `/web/api/users`). Канонической схемой публикации теперь является
 * `DocumentRoot=<проект>/web` (docs/help/admin/install.md), при которой `baseUrl`
 * пуст и адреса чистые.
 *
 * Переключить разом все внешние интеграции (скрипты синхронизации, телефония,
 * плагин wiki, закладки, ссылки в самой wiki) невозможно, поэтому старые адреса
 * с `/web/` должны продолжать работать. Совместимость двухслойная:
 *  - web/.htaccess срезает префикс на уровне веб-сервера, чтобы статика
 *    (`/web/css/custom.css`) отдавалась с диска, а остальное попадало в index.php;
 *  - этот класс срезает тот же префикс из pathInfo, иначе роутер искал бы
 *    несуществующий контроллер `web`.
 *
 * Прозрачно (без редиректа) — намеренно: REST-клиенты интеграций в массе своей
 * не ходят по 301/302, а часть из них на редиректе меняет метод POST/PUT на GET.
 * Ссылки, которые приложение рисует само, при этом всегда канонические, поэтому
 * пришедший по старому адресу браузер «вылечивается» после первого перехода.
 *
 * Когда все интеграции будут перенастроены, слой отключается настройкой
 * `components.request.legacyPathPrefix=null` в config/web-local.php.
 */
class Request extends \yii\web\Request
{
	/**
	 * @var string|null префикс пути, оставшийся от старой схемы публикации.
	 * null или пустая строка полностью выключают совместимость.
	 */
	public $legacyPathPrefix='web';

	/** @var bool сработала ли совместимость на текущем запросе */
	private $_legacyPrefixStripped=false;

	/** @var bool нормализация URL уже выполнена */
	private $_legacyChecked=false;

	/**
	 * {@inheritdoc}
	 *
	 * Нормализуем URL целиком (а не только pathInfo): из него Yii выводит и
	 * маршрут, и `Url::to('')` - адрес текущей страницы, который по умолчанию
	 * подставляется в action форм. Иначе форма, открытая по легаси-адресу,
	 * постилась бы обратно на легаси-адрес, и старый префикс жил бы в сессии
	 * пользователя сколько угодно долго.
	 */
	public function getUrl()
	{
		$url=parent::getUrl();

		//резолв делаем один раз: результат кладём обратно в кэш базового класса
		if ($this->_legacyChecked) return $url;
		$this->_legacyChecked=true;

		$prefix=is_string($this->legacyPathPrefix)?trim($this->legacyPathPrefix,'/'):'';
		if ($prefix==='') return $url;

		//префикс, с которого начинается адрес приложения: обычно это baseUrl, но если
		//точка входа видна в адресе (showScriptName, так работают acceptance-тесты) -
		//то scriptUrl. Ровно так же выбирает базу и сам Yii в resolvePathInfo()
		$scriptUrl=$this->getScriptUrl();
		$base=(strncmp($url,$scriptUrl,strlen($scriptUrl))===0)?$scriptUrl:$this->getBaseUrl();

		//в старой схеме публикации префикс - это как раз baseUrl, и лишним он не является:
		//там легаси-адресом был бы /web/web/..., которого не бывает
		$legacyBase=$base.'/'.$prefix;
		$len=strlen($legacyBase);
		if (strncmp($url,$legacyBase,$len)!==0) return $url;

		//префикс должен быть отдельным токеном пути: /webinars трогать нельзя
		$next=$url[$len]??'';
		if ($next!=='' && $next!=='/' && $next!=='?') return $url;

		$this->_legacyPrefixStripped=true;
		$url=$base.substr($url,$len);
		if ($url==='' || $url[0]!=='/') $url='/'.$url;
		$this->setUrl($url);

		return $url;
	}

	/**
	 * Пришёл ли запрос по легаси-адресу с префиксом `/web/`.
	 * Полезно для диагностики: показывает, что какая-то интеграция ещё не перенастроена.
	 * @return bool
	 */
	public function getIsLegacyPath()
	{
		$this->getUrl(); //резолв мог ещё не выполняться
		return $this->_legacyPrefixStripped;
	}
}
