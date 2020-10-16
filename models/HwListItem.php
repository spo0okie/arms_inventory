<?php
/**
 * Created by PhpStorm.
 * User: Spookie
 * Date: 07.03.2018
 * Time: 10:02
 */

namespace app\models;

/**
 * Class HwListItem Элемент списка аппаратного обеспечения
 */
class HwListItem
{
    /*
     * Константы
     */
    public static $TYPE_MONITOR='monitor';
    public static $TYPE_VIDEOCARD='videocard';
    public static $TYPE_HDD='harddisk';
    public static $TYPE_RAM='memorybank';
    public static $TYPE_CPU='processor';
    public static $TYPE_MB='motherboard';
    protected static $loadParams=[
        'type',
        'manufacturer',
        'product',
        'sn',
        'title',
        'manufacturer_id',
        'fingerprint',
        'hidden',
        'inv_num',
        'uid',
        'manual_sn',
        'manual_name'
    ];

    /*
     * Исходные данные
     */
    public $type;           //тип устройства
    public $manufacturer;   //производитель устройства
    public $product;        //наименование устройства
	public $sn;             //серийный номер
	public $cores;          //кол-во ядер для CPU
	public $capacity;       //емкость для памяти/дисков

    /*
     * Вычисляемые непосредственно из исходных данных
     */
    public $title;          //Выводимый заголовок устройства (тоже тип устройства но человекопонятный)
    public $manufacturer_id;//ID производителя устройства
    public $fingerprint;    //отпечаток устройства - все первичные параметры в кучу, для сравнения устройств как строк
    public $found=false;    //флаг того, что это устройство обнаружено в АРМ (иначе оно может быть просто сохранено, но фактически отсутствовать)

    /*
     * Дополнительные параметры получаемые из АРМ
     */
    public $hidden=false;   //флаг того, что устройство помечено как скрытое в паспорте
    public $uid=null;       //идентификатор в рамках одного паспорта. иногда нужно различать идентичные элементы
    public $inv_num;        //бухг инв номер
    public $manual_name;    //наименование введеное вручную (оверрайд)
    public $manual_sn;      //серийный номер введенный вручную (оверрайд)


    /**
     * Возвращает true если элемент идентичен (описывает ту же железку) переданному
     * @param HwListItem $item элемент для сравнения
     * @return bool
     */
    public function isEqualTo($item){
        return 0==strcmp($item->fingerprint,$this->fingerprint);
    }

    /**
     * Возвращает JSON представление элемента
     *
     */
    public function saveJSON() {
        return json_encode($this->toSave(),JSON_UNESCAPED_UNICODE);
    }

    /**
     * Грузит один параметр $param из массива $storage в наш объект
     * @param $param параметр для загрузки
     * @param $storage массив со всеми входными параметрами
     */
    public function loadParam($param,$storage){
        if (isset($storage[$param])) $this->$param=$storage[$param];
    }

    /**
     * Загружает данные из массива
     * @param $data
     */
    public function loadArr($data) {
        foreach (static::$loadParams as $param) $this->loadParam($param,$data);
    }

    /**
     * Элемент подписывается (вносится в паспорт)
     */
    public function sign() {
        if(!strlen($this->uid)) $this->uid=uniqid();
    }

    /**
     * Возвращает массив полей для сохранения
     * @return array
     */
    public function toSave() {
        $item=(array)$this;
        unset($item['found']); //это поле нельзя сохранять
        if (!strlen($item['uid'])) $item['uid']=uniqid(); //а это должно быть обязательно
        return $item;
    }

    public function getName() {
        if (strlen($this->manual_name)) return $this->manual_name;
        return $this->product;
    }

    public function getSN() {
        if (strlen($this->manual_sn)) return $this->manual_sn;
        return $this->sn;
    }

    /**
     * Загружает элемент из сырых данных (\app\models\Comps->raw_hw)
     * @param string $type тип элемента
     * @param array $item элемент сырых данных
     * @return boolean успех загрузки
     * @throws \Exception
     * @throws \Throwable
     */
    public function loadRaw($type, $item)
    {
        /*
         * Сюда на вход приходит чтото такое:
         * [
         *     ["motherboard"]=>  [
         *          ["manufacturer"]=>       string(21) "ASUSTeK COMPUTER INC."
         *          ["product"]=>            string(6)  "H61M-G"
         *          ["serial"]=>             string(15) "140222247102869"
         *     ]
         * ]
         * т.е. это
         * [ $type => [ 'manufacturer'=>$manufacturer, 'product'=>$product, 'serial'=>$sn ]]
         * наша зада это разобрать по полочкам
         */

	    $this->type=strtolower($type);
        if (!is_array($item)) {
	        switch ($this->type) {
		        case static::$TYPE_CPU :
			        /*
					 * это случай когда CPU записан одной строкой
					 */
			        $this->title = 'Процессор';
			        $this->manufacturer = explode(' ', $item)[0]; //первое слово - производитель
			        $this->product = $item; //вся строка - сам проц
			        $this->sn = ''; //отсутствует
					$this->cores=1;
			        break;
	        }
        } else {
	        foreach ($item as $idx=>$val) $item[$idx]=trim($val);
	        switch ($this->type) {
				case static::$TYPE_CPU :
					/*
					 * в случае процессора у нас есть поля
					 * model
					 * cores
					 */
					$this->title = 'Процессор';
					$this->manufacturer = 'tst'; //explode(' ', $item['model'])[0];
					$this->product = $item['model'];
					$this->cores=isset($item['cores'])?$item['cores']:1;
					$this->sn = '';
					break;
	            case static::$TYPE_MB :
	                /*
	                 * в случае материнской платы у нас есть поля
	                 * manufacturer
	                 * product
	                 * serial
	                 */
	                $this->title = 'Материнская плата';
	                $this->manufacturer = $item['manufacturer'];
	                $this->product = $item['product'];
	                $this->sn = $item['serial'];
	                break;
	            case static::$TYPE_CPU :
	                /*
	                 * в случае процессора у нас есть поля
	                 * manufacturer
	                 * product
	                 * serial
	                 */
	                $this->title = 'Процессор';
	                $this->manufacturer = explode(' ', $item)[0];
	                $this->product = $item;
	                $this->sn = '';
	                break;
	            case static::$TYPE_RAM :
	                /*
	                 * в случае памяти у нас есть поля
	                 * manufacturer
	                 * capacity
	                 */
	                $this->title = 'Модуль памяти';
	                $this->manufacturer = $item['manufacturer'];
		            $this->product = $item['capacity'] . 'MiB';
		            $this->capacity = (int)trim($item['capacity']);
	                $this->sn = '';
	                break;
	            case static::$TYPE_HDD :
	                /*
	                 * в случае жесткого диска у нас есть поля
	                 * model
	                 * size
	                 */
	                $this->title = 'Накопитель';
	                $this->manufacturer = explode(' ', $item['model'])[0];
	                $this->product = $item['model'] . ' ' . $item['size'] . 'GB';
		            $this->capacity = (int)trim($item['size']);
	                $this->sn = isset($item['serial'])?$item['serial']:'';
	                break;
	            case static::$TYPE_VIDEOCARD :
	                /*
	                 * в случае видеокарты у нас есть поля
	                 * model
	                 * ram
	                 * name
	                 */
	                $this->title = 'Видеокарта';
	                $this->manufacturer = explode(' ', $item['name'])[0];
	                $this->product = $item['name'] . ' ' . $item['ram'] . 'MiB';
		            $this->capacity = $item['ram'];
	                $this->sn = '';
	                break;
	            case static::$TYPE_MONITOR :
	                /*
	                 * монитор нам передает
	                 * DeviceID
	                 * ManufactureDate
	                 * SerialNumber
	                 * ModelName
	                 * Version
	                 * VESAID
	                 * PNPID
	                 */
	                $this->title = 'Монитор';
	                $this->manufacturer = explode(' ', $item['ModelName'])[0];
	                $this->product = $item['ModelName'];
	                $this->sn = $item['SerialNumber'];
	                break;
	            default:
	                $this->title= 'Неизвестный тип '.$type;
	                //return true;
	                return false;
	        }
        }
        $this->manufacturer_id = ManufacturersDict::fetchManufacturer($this->manufacturer);
        $this->fingerprint = mb_strtolower($type.'|'.$this->manufacturer.'|'.$this->product.'|'.$this->sn);
        return true;
    }// loadRaw

    public function globIgnored() {
        return HwIgnore::exists($this->fingerprint);
    }

} //class;



