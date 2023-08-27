<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\console\commands;

use app\console\ConsoleException;
use app\helpers\ArrayHelper;
use app\helpers\RestHelperException;
use app\models\ArmsModel;
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
		if ($objects!=false) foreach ($objects as $object) {
			$this->storeLoaded($class,$object);
		}
	}
	
	/**
	 * Синхронизация простых объектов (без ссылок)
	 * @param string $class класс локальных объектов
	 * @param array  $remotes массив удаленных объектов
	 * @param string $name имя поля "ключа" по которому ищем локальные (ID у них не совпадают)
	 */
	public static function syncSimple(string $class, array $remotes, $name='name'){
		//грузим локальные объекты
		$locals=$class::find()->all();
		
		//перебираем удаленные
		foreach ($remotes as $id=>$remote) {
			//каждому удаленному ищем локальный
			$localSearch=ArrayHelper::findByField($locals,$name,$remote[$name]);
			
			//если в качестве ключа выбран $name, то по нему не должно искаться несколько объектов
			if (count($localSearch)>1) throw new ConsoleException('Got multiple objects with same name',[
				'Name'=>$remote[$name],
				'Objects Found'=>$localSearch
			]);
			
			if (!count($localSearch)) {
				//TODO тут надо принимать решение создаем ли новые объекты и если нужно - создавать
				echo "Local $name missing!\n";
				continue;
			}
			
			/** @var $local ArmsModel */
			$local=reset($localSearch);
			
			$local->syncFields($remote);
		}
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
		$this->loadRemote('manufacturers-dict');
		static::syncSimple('/app/models/ManufacturersDict',$this->remote['manufacturers-dict']);
    	//print_r($this->loaded);
    }
}
