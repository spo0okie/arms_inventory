<?php
/**
 * Хелпер в задачи которого входит работа с удаленными данными через REST API
 */

namespace app\helpers;

use app\console\ConsoleException;
use yii\web\UrlManager;

class RestHelper
{
	public $url=null;			//URL удаленной системы
	public $user=null;			//пользователь для авторизации в удаленной системе
	public $pass=null;			//пароль для авторизации в удаленной системе
	public $context=null;		//контекст с авторизацией для запросов
	public $unsecureSSL=false;	//игнорировать корректность SSL
	
	public $request;			//сюда кладем полный урл запроса
	public $response;			//сюда кладем сырые данные ответа
	public $responseHeaders;	//сюда кладем заголовок ответа
	
	/**
	 * Инициализация параметров удаленной системы
	 * @param $url
	 * @param $user
	 * @param $pass
	 */
	public function init($url,$user,$pass)
	{
		if ($url) $this->url=$url;
		if ($user) $this->user=$user;
		if ($pass) $this->pass=$pass;

		$context=[];

		if ($this->user) {
			$auth=base64_encode($this->user.':'.$this->pass);
			$context['http'] = [
				"header" => "Authorization: Basic $auth"
			];
		}
		
		if ($this->unsecureSSL)
			$context['ssl']=[
				"verify_peer"=>false,
				"verify_peer_name"=>false,
			];
		
		$this->context=stream_context_create($context);
	}
	
	/**
	 * Разбирается с каким кодом вернулась страничка с запроса
	 * @param array $headers
	 * @return array
	 */
	private static function parseHeaders(array $headers)
	{
		//упер отсель https://www.php.net/manual/ru/reserved.variables.httpresponseheader.php
		$head = array();
		foreach( $headers as $k=>$v )
		{
			$t = explode( ':', $v, 2 );
			if( isset( $t[1] ) )
				$head[ trim($t[0]) ] = trim( $t[1] );
			else
			{
				$head[] = $v;
				if( preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$v, $out ) )
					$head['response_code'] = intval($out[1]);
			}
		}
		return $head;
	}
	
	/**
	 * Урл до какого-то пути на удаленной системе
	 * @param string $path
	 * @param array  $params
	 * @return string
	 */
	public function getPathUrl(string $path,$params=[]) {
		$urlManager=new UrlManager([
			'enablePrettyUrl'=>true,
			'showScriptName' => false,
		]);
		$urlManager->setBaseUrl($this->url);
		$params[0]=$path;
		return $urlManager->createAbsoluteUrl($params);
	}
	
	/**
	 * Урл до конкретного action на удаленной системе
	 * @param        $class
	 * @param string $action
	 * @param array  $params
	 * @return string
	 */
	public function getActionUrl(string $class,$action='index',$params=[]) {
		return $this->getPathUrl($class.'/'.$action,$params);
	}
	
	/**
	 * Получает данные с удаленной системы
	 * @param $url
	 * @return array
	 */
	public function getData($url) {
		$this->request=$url;
		$this->response=@file_get_contents($url,false,$this->context);
		$this->responseHeaders=static::parseHeaders($http_response_header);
		echo "$url\n";
		if (isset($this->responseHeaders['response_code'])&&($this->responseHeaders['response_code']=='200')) {
			return json_decode($this->response, true);
		} throw new ConsoleException("Error getting remote data",[
			'Requested URL' =>$this->request,
			'Response headers'=>$this->responseHeaders,
			'Response'=>$this->response
		]);
	}
	
	public function getFileData($url) {
		$this->request=$url;
		$this->response=@file_get_contents($url,false,$this->context);
		$this->responseHeaders=static::parseHeaders($http_response_header);
		//echo "$url\n";
		if (isset($this->responseHeaders['response_code'])&&($this->responseHeaders['response_code']=='200')) {
			return $this->response;
		} throw new ConsoleException("Error getting remote data",[
			'Requested URL' =>$this->request,
			'Response headers'=>$this->responseHeaders,
			'Response'=>$this->response
		]);
	}
	
	/**
	 * Получить с удаленной системы все объекты определенного класса
	 * @param        $class
	 * @param string $action
	 * @param array  $params
	 * @return array|false
	 */
	public function getObjects($class,$action='index',$params=[]) {
		return $this->getData(
			$this->getActionUrl($class,$action,$params)
		);
	}
	
	public function getFile($class,$action='download',$params=[]) {
		return $this->getFileData(
			$this->getActionUrl($class,$action,$params)
		);
	}
}