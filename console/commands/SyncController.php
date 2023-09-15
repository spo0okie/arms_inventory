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
use app\models\Soft;
use yii\console\Controller;
use app\helpers\RestHelper;
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
	
	//массив тех что подобрали локально
	private $local=[];
	
	//массив уже синхронизированных
	private $synced=[];
	
	public static $debug=true;
	
	/**
	 * Инициализация удаленной системы
	 * @param $url
	 * @param $user
	 * @param $pass
	 */
	public function initRemote($url,$user,$pass) {
		$this->remote = new RestHelper();
		$this->remote->unsecureSSL=true;
		$this->remote->init($url,$user,$pass);
	}
	
	static function getClassPath($path) {
		return (strpos($path,'\\')===false)?'app\\models\\'.$path:$path;
	}
	
	static function getClassName($path) {
		if (strpos($path,'\\')===false) return $path;
		$classPath=explode('\\',$path);
		return end($classPath);
	}
	
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
	
	/**
	 * сохранить обнаруженный локальный объект в хранилище
	 * @param $class string класс объекта
	 * @param $key string значение ключа
	 * @param $object array|object сам объект
	 */
	public function storeFound(string $class, string $key, $object) {
		if (!isset($this->local[$class])) $this->local[$class]=[];
		$this->local[$class][$key]=$object;
	}
	
	/**
	 * сохранить синхронизированный объект в хранилище
	 * @param $class string класс объекта
	 * @param $key string значение ключа
	 * @param $object array|object сам объект
	 */
	public function storeSynced(string $class, string $key, $object) {
		if (!isset($this->synced[$class])) $this->synced[$class]=[];
		$this->synced[$class][$key]=$object;
	}
	
	
	/**
	 * сохранить отключенный объект в хранилище
	 * это те объекты ссылки на которые мы перенаправили в другое место
	 * их надо после синхронизации проверять на осиротевшесть
	 * @param string $class класс объекта
	 * @param string $linkClass
	 * @param array  $remote		//объект, который сейчас будет перенацелен
	 * @throws ConsoleException
	 */
	public function storeDetachedReverse(string $class, string $linkClass, array $remote) {
		if (!isset($this->detached[$class])) $this->detached[$class]=[];
		
		$classPath=self::getClassPath($class);
		
		//находим локальный объект до перенацеливания
		$currentLinking=$this->getLocalObject($linkClass,$remote);
		if (is_null($currentLinking)) return; //нечего сохранять
		
		//какое поле ссылается на отключаемый объект
		$linkField=$classPath::$syncableReverseLinks[$linkClass];
		
		//находим тот объект, на который сейчас ссылается перенацеливаемый
		$detached=$classPath::findOne($currentLinking->$linkField);
		if (is_null($detached)) return; //нечего сохранять
		
		$this->loaded[$class][$detached->id]=$detached;
	}
	
	
	/**
	 * Загрузить удаленный класс
	 * @param string $class  класс загружаемого объекта (в виде url-id)
	 * @param array $params
	 * @param string $storeClass в какой класс сохраняем (в виде Camel)
	 */
	public function loadRemote(string $class, $params=[], $storeClass='') {
		$params['per-page']=0;
		if (!$storeClass) $storeClass=Inflector::camelize($class);
		echo "Loading $storeClass ... ";
		$objects=$this->remote->getObjects($class,'index',$params);
		if ($objects!=false) foreach ($objects as $object) {
			$this->storeLoaded($storeClass,$object);
		}
		$count=count($this->getLoadedClass($storeClass));
		echo "OK ($count objects)\n";
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
	 * Найти локальный объект нужного класса у соответствующий $remote
	 * @param string $class
	 * @param array $remote
	 */
	public function getLocalObject(string $class, array $remote) {
		$classPath=static::getClassPath($class);
		$className=static::getClassName($class);
		
		
		//поле по которому ищем локальный объект
		$name=$classPath::$syncKey;
		
		//$localSearch=$classPath::find()->where([$name=>$remote[$name]])->all();
		if (!isset($this->local[$className][$remote[$name]])) {
			$localSearch=$classPath::syncFindLocal($remote[$name]);
			
			//если в качестве ключа выбран $name, то по нему не должно искаться несколько объектов
			if (count($localSearch)>1) throw new ConsoleException('Got multiple objects with same name',[
				'Name'=>$remote[$name],
				'Objects Found'=>$localSearch
			]);
			
			$obj=count($localSearch)?reset($localSearch):null;
			$this->storeFound($className,$remote[$name],$obj);
		}
		
		return $this->local[$className][$remote[$name]];
	}
	
	/**
	 * Синхронизировать загруженный удаленный объект $object класса $class в локальную базу
	 * @param string $class
	 * @param array  $remote
	 * @param array  $overrides
	 * @throws ConsoleException
	 */
	public function syncSingle(string $class, array $remote, $overrides=[]) {
		//полный путь до класса
		$class=self::getClassPath($class);
		$className=self::getClassName($class);
		
		//поле по которому ищем локальный объект
		$name=$class::$syncKey;
		
		
		//каждому удаленному ищем локальный
		/** @var $local ArmsModel */
		
		if (isset($this->synced[$className][$remote[$name]])) {
			$local=$this->synced[$className][$remote[$name]];
			if (!count($overrides)) return $local;

		} else {
			$local=$this->getLocalObject($class,$remote);
		}
		
		
		$log='';
		if (is_null($local)) {
			//если нет - создаем
			echo "Local {$remote[$name]} missing! ";
			$local=$class::syncCreate($remote,$overrides,$log,$this->remote);
			$sync=$local->silentSave(false);
		} else {
			//иначе обновляем
			$sync=$local->syncFields($remote,$overrides,$log,$this->remote);
		}

		if (!is_null($sync)) {
			echo "{$remote[$name]}: $log ";
			echo $sync?" - OK":" - ERR";
			echo "\n";
		}
		
		if ($sync===false) throw new ConsoleException('Error saving local object!',[
			'Local'=>$local,
			'Remote'=>$remote,
			'Overrides'=>$overrides
		]);

		//синхронизируем прямые ссылки
		
		//вытаскиваем для удобства список ссылок из этого класса на других
		$linksClasses=$class::$syncableDirectLinks;
		
		//грузим объекты на которые ссылаемся
		$objectLinks=$this->getRemoteDirectLinks($remote,$class);
		
		//перебираем их по полям-ссылкам
		foreach ($objectLinks as $linkField=>$object) {
			//выясняем поле которое в ссылающемся объекте указывает на этот
			$linkClass=$linksClasses[$linkField];
			//TODO запомнить тот на который ссылались до сих пор
			//$this->storeDetachedReverse($class,$linkClass,$object);
			//находим/создаем в локальной БД такой объект
			$localLink=$this->syncSingle($linkClass,$object);
			//прописываем прямую ссылку на него
			if ($local->$linkField!=$localLink->id) {
				$local->$linkField=$localLink->id;
				$local->silentSave(false);
			}
		}




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
				$this->storeDetachedReverse($class,$linkClass,$object);
				$this->syncSingle($linkClass,$object,[$link=>$local->id]);
			}
		}
		
		//вытаскиваем для удобства список ссылок из других классов на этот
		/* public static $syncableMany2ManyLinks=[
			'soft_ids'=>'Soft,softList_ids'
		]; */
		$linksDefinitions=$class::$syncableMany2ManyLinks;
		//грузим фактически ссылающиеся на наш объекты
		$objectLinks=$this->getRemoteMany2ManyLinks($remote,$class);
		//перебираем их по классам
		foreach ($objectLinks as $linkClass=>$objects) {
			//выясняем поле которое в ссылающемся объекте указывает на этот
			[$linkClass,$reverseLink]=explode(',',$linksDefinitions[$linkClass]);
			
			//перебираем ссылающиеся объекты и "ссылаем их на новый"
			foreach ($objects as $object) {
				//TODO Запомнить объекты куда раньше ссылались эти
				//$this->storeDetachedReverse($class,$linkClass,$object);
				$this->syncSingle($linkClass,$object,[$reverseLink=>$local->id]);
			}
		}
		$this->storeSynced($className,$remote[$name],$local);
		return $local;
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
		   	'ManufacturersDict'=>'manufacturers_id'
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
	 * Загружает объекты ссылающиеся на удаленный
	 * @param array  $remote удаленный объект
	 * @param string $class класс удаленного объекта (грузим то мы его в виде массива, класс не виден)
	 * @return array массив ссылок field=>object
	 * @throws ConsoleException
	 */
	public function getRemoteDirectLinks(array $remote, string $class) {
		$classes=$class::$syncableDirectLinks;
		/* public static $syncableDirectLinks=[
			'manufacturers_id'=>'Manufacturers',
			'type_id'=>'TechTypes',
		]; */
		$links=[];
		foreach ($classes as $link=>$class) {
			$linkSearch=ArrayHelper::findByField(
				$this->getLoadedClass($class),	//ищем все объекты такого класса
				'id',						//$id которых
				$remote[$link]					//указывает ссылка $link у $remote
			);
			//фактически с конкретным $id у нас только один объект
			if (count($linkSearch)) $links[$link]=reset($linkSearch);
		}
		return $links;
	}
	
	/**
	 * Загружает объекты ссылающиеся на удаленный
	 * @param array  $remote удаленный объект
	 * @param string $class класс удаленного объекта (грузим то мы его в виде массива, класс не виден)
	 * @return array массив обратных ссылок
	 * @throws ConsoleException
	 */
	public function getRemoteMany2ManyLinks(array $remote, string $class) {
		$definitions=$class::$syncableMany2ManyLinks;
		/* public static $syncableMany2ManyLinks=[
			'soft_ids'=>'Soft,softList_ids'
		]; */
		$links=[];
		foreach ($definitions as $link=>$definition) {
			[$linkClass,$reverseLink]=explode(',',$definition);
			$links[$link]=[];
			foreach ($remote[$link] as $linkedId) {
				$linkSearch=ArrayHelper::findByField(
					$this->getLoadedClass($linkClass),	//ищем все объекты такого класса
					'id',						//$id которых
					$linkedId					//указывает ссылка $link у $remote
				);
				//фактически с конкретным $id у нас только один объект
				if (count($linkSearch))
					$links[$link][]=reset($linkSearch);
			}
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
		$this->loadRemote('tech-types');
		static::syncSimple('TechTypes');
	}
	
	/**
	 * Подтянуть типы оборудования
	 * @param $url
	 * @param $user
	 * @param $pass
	 */
	public function actionTechModels(string $url, string $user='', string $pass='')
	{
		$this->initRemote($url,$user,$pass);
		$this->loadRemote('manufacturers-dict');
		$this->loadRemote('manufacturers');
		$this->loadRemote('tech-types');
		$this->loadRemote('tech-models',['expand'=>'nameWithVendor']);
		$this->loadRemote('scans',['expand'=>'fileSize,fileDate,name']);
		//static::syncSimple('app\models\ManufacturersDict');
		//print_r($this->loaded);
		static::syncSimple('TechModels');
	}
	
	/**
	 * Подтянуть ПО
	 * @param $url
	 * @param $user
	 * @param $pass
	 */
	public function actionSoft(string $url, string $user='', string $pass='')
	{
		Soft::$disable_cache=true;
		Soft::$disable_rescan=true;
		$this->initRemote($url,$user,$pass);
		$this->loadRemote('manufacturers-dict');
		$this->loadRemote('manufacturers');
		$this->loadRemote('soft',['expand'=>'name']);
		//static::syncSimple('app\models\ManufacturersDict');
		//print_r($this->loaded);
		static::syncSimple('Soft');
	}
	
	/**
	 * Подтянуть ПО
	 * @param $url
	 * @param $user
	 * @param $pass
	 */
	public function actionSoftLists(string $url, string $user='', string $pass='')
	{
		Soft::$disable_cache=true;
		Soft::$disable_rescan=true;
		$this->initRemote($url,$user,$pass);
		$this->loadRemote('manufacturers-dict');
		$this->loadRemote('manufacturers');
		$this->loadRemote('soft',['expand'=>'name']);
		$this->loadRemote('soft-lists',['expand'=>'soft_ids']);
		static::syncSimple('SoftLists');
	}
	
	/**
	 * Подтянуть Лицензии
	 * @param $url
	 * @param $user
	 * @param $pass
	 */
	public function actionLicGroups(string $url, string $user='', string $pass='')
	{
		Soft::$disable_cache=true;
		Soft::$disable_rescan=true;
		$this->initRemote($url,$user,$pass);
		$this->loadRemote('manufacturers-dict');
		$this->loadRemote('manufacturers');
		$this->loadRemote('soft',['expand'=>'name']);
		$this->loadRemote('lic-types');
		$this->loadRemote('lic-groups',['expand'=>'soft_ids']);
		//static::syncSimple('app\models\ManufacturersDict');
		//print_r($this->loaded);
		static::syncSimple('LicGroups');
	}

	/**
	 * Подтянуть Контрагентов
	 * @param $url
	 * @param $user
	 * @param $pass
	 */
	public function actionPartners(string $url, string $user='', string $pass='')
	{
		$this->initRemote($url,$user,$pass);
		$this->loadRemote('partners');
		static::syncSimple('Partners');
	}
}
