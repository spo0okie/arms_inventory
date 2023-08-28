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
use yii\helpers\Inflector;

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
	 * @param string $class  класс загружаемого объекта (в виде url-id)
	 * @param array $params
	 * @param string $storeClass в какой класс сохраняем (в виде Camel)
	 */
	public function loadRemote(string $class, $params=[],$storeClass='') {
		$params['per-page']=0;
		if (!$storeClass) $storeClass=Inflector::camelize($class);
		$objects=$this->remote->getObjects($class,'',$params);
		if ($objects!=false) foreach ($objects as $object) {
			$this->storeLoaded($storeClass,$object);
		}
	}
	
	/**
	 * Вернуть все загруженные удаленные объекты указанного класса
	 * @param string $class
	 * @return mixed
	 * @throws ConsoleException
	 */
	public function getLoadedClass(string $class) {
		if (!isset($this->loaded[$class]))
			throw new ConsoleException("Class $class not loaded!");
		return $this->loaded[$class];
	}
	
	/**
	 * Синхронизировать загруженный удаленный объект $object класса $class в локальную базу
	 * @param string $class
	 * @param array  $remote
	 * @param array  $overrides
	 * @throws ConsoleException
	 */
	public function syncSingle(string $class, array $remote, $overrides=[]) {
		//поле по которому ищем локальный объект
		$name=$class::$syncKey;
		
		echo "{$remote[$name]}: ";
		
		//каждому удаленному ищем локальный
		//$localSearch=ArrayHelper::findByField($locals,$name,$remote[$name]);
		$localSearch=$class::find()->where([$name=>$remote[$name]])->all();
		
		//если в качестве ключа выбран $name, то по нему не должно искаться несколько объектов
		if (count($localSearch)>1) throw new ConsoleException('Got multiple objects with same name',[
			'Name'=>$remote[$name],
			'Objects Found'=>$localSearch
		]);
		
		/** @var $local ArmsModel */
		if (!count($localSearch)) {
			//если нет - создаем

			echo "Local $name missing!";
			$local=$class::syncCreate($remote,$overrides);
			$sync=$local->silentSave(false);
		} else {
			$local=reset($localSearch);
			
			//иначе обновляем
			$sync=$local->syncFields($remote,$overrides);
		}

		if (is_null($sync)) echo " - No changes";
		else echo $sync?" - OK":" - ERR";
		echo "\n";
		
		if ($sync===false) throw new ConsoleException('Error saving local object!',[
			'Local'=>$local,
			'Remote'=>$remote,
			'Overrides'=>$overrides
		]);
		
		//на этом этапе сам объект загружен, но есть ли у нас ссылки на него??
		//вытаскиваем для удобства список ссылок из других классов на этот
		$classLinks=$class::$syncableReverseLinks;
		//грузим фактически ссылающиеся на наш объекты
		$objectLinks=$this->getRemoteReverseLinks($remote,$class);
		//перебираем их по классам
		foreach ($objectLinks as $linkClass=>$objects) {
			//выясняем поле которое в ссылающемся объекте указывает на этот
			$link=$classLinks[$linkClass];
			
			//перебираем ссылающиеся объекты и "ссылаем их на новый"
			foreach ($objects as $object) {
				//TODO Запомнить объекты куда раньше ссылались эти
				$this->syncSingle('app\\models\\'.$linkClass,$object,[$link=>$local->id]);
			}
		}
		
	}
	
	
	/**
	 * Синхронизация простых объектов (без ссылок)
	 * @param string $class класс локальных объектов
	 * @throws ConsoleException
	 */
	public function syncSimple(string $class){
		//грузим локальные объекты
		//$locals=$class::find()->all();
		//от предварительной загрузки всех локальных пришлось отказаться,
		//т.к. сравнение строк в PHP и MySQL идет по разному
		//получался сценарий когда в PHP «интеграл» == "интеграл", а в Mysql нет
		//А при поиске каждого в базе - такой херни нет
		
		$classPath=explode('\\',$class);
		$className=end($classPath);
		$name=$class::$syncKey;
		
		//перебираем удаленные
		foreach ($this->getLoadedClass($className) as $id=>$remote) {
			$this->syncSingle($class,$remote);
		}
	}
	
	/**
	 * Загружает объекты ссылающиеся на удаленный
	 * @param array  $remote удаленный объект
	 * @param string $class класс удаленного объекта (грузим то мы его в виде массива, класс не виден)
	 * @return array массив обратных ссылок
	 * @throws ConsoleException
	 */
	public function getRemoteReverseLinks(array $remote, string $class) {
		$classes=$class::$syncableReverseLinks;
		/* public static $syncableReverseLinks=[
		   	'manufacturersDict'=>'manufacturers_id'
		]; */
		$links=[];
		foreach ($classes as $class => $link) {
			$links[$class]=ArrayHelper::findByField(
				$this->getLoadedClass($class),	//ищем все объекты такого класса
				$link,							//у которых ссылка $link
				$remote['id']					//указывает на $remote
			);
		}
		return $links;
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
		$this->loadRemote('manufacturers');
		//static::syncSimple('app\models\ManufacturersDict');
		//print_r($this->loaded);
		static::syncSimple('app\models\Manufacturers');
    }


	/**
	 * Подтянуть типы оборудования
	 * @param $url
	 * @param $user
	 * @param $pass
	 */
	public function actionTechTypes(string $url, string $user='', string $pass='')
	{
		$this->initRemote($url,$user,$pass);
		$this->loadRemote('manufacturers-dict');
		$this->loadRemote('manufacturers');
		//static::syncSimple('app\models\ManufacturersDict');
		//print_r($this->loaded);
		static::syncSimple('app\models\Manufacturers');
	}
}
