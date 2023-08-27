<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\console\commands;

use app\helpers\RestHelperException;
use yii\console\Controller;
use app\helpers\RestHelper;
use yii\db\Exception;

/**
 * This command echoes the first argument that you have entered.
 *
 * This command is provided as an example for you to learn how to create console commands.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class SyncController extends Controller
{
	/** @var RestHelper  */
	private $remote=null;
	
	//загруженные объекты удаленной системы
	private $loaded=[];
	
	//массив объектов от которых была произведена миграция и которые надо после проверить на осиротевшесть
	private $detached=[];
	
	/**
	 * сохранить загруженный объект в хранилище
	 * @param $class string класс объекта
	 * @param $object array|object сам объект
	 * @param $key string поле-ключ
	 */
	public function storeLoaded(string $class, $object, string $key = 'id') {
		if (!isset($this->loaded[$class])) $this->loaded[$class]=[];
		if (!is_array($object)) $object=(array)$object;
		$id=$object[$key];
		$this->loaded[$class][$id]=$object;
	}
	
	public function initRemote($url,$user,$pass) {
		$this->remote = new RestHelper();
		$this->remote->unsecureSSL=true;
		$this->remote->init($url,$user,$pass);
	}
	
	/**
	 * Загрузить удаленный класс
	 * @param string $class  класс загружаемого объекта
	 * @param array $params
	 */
	public function loadRemote(string $class, $params=[]) {
		$objects=$this->remote->getObjects($class,'',$params);
		if ($objects!=false)
			$this->loaded[$class]=$objects;
		else
			throw new RestHelperException("Error getting remote $class objects", $this->remote);
	}
	
	/**
	 * Подтянуть вендоров
	 * @param $url
	 * @param $user
	 * @param $pass
	 */
    public function actionManufacturers(string $url, string $user='', string $pass='')
    {
    	$this->initRemote($url,$user,$pass);
		$this->loadRemote('manufacturers');
		$this->loadRemote('manufacturers-dict');
    	print_r($this->loaded);
    }
}
