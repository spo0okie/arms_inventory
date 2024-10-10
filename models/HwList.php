<?php
/**
 * Created by PhpStorm.
 * User: Spookie
 * Date: 07.03.2018
 * Time: 10:01
 */

namespace app\models;

/*
 * Список оборудования
 */

use Exception;
use Throwable;

class HwList {

    public $items=[];       //элементы списка оборудования
    public $rawData=null;   //сырые текстовые данные
    public $data=null;      //расшифрованные json данные

    /**
     * Загружет сырые данные из ОС (\app\models\Comps->raw_hw)
     * @param string $data
     * @throws Exception
     * @throws Throwable
     */
    public function loadRaw($data)
    {
    	//убираем NULL символы из содержимого. такое бывало.
        $this->rawData = '[' . str_replace("\0",'',$data) . ']'; //дополняем до валидного JSON формата
        $this->data = json_decode($this->rawData, true); //расшифровываем в array
        //$this->items = $this->data;
        //return;
        if (!is_array($this->data)) {
            //error_log("error decoding ".$this->rawData);
            return;
        } //если расшифровать не вышло - то дальше делать нечего

        foreach ($this->data as $items) {//перебираем элементы списка
            foreach ($items as $type => $item) {//каждый элемент у нас обозначен как ассоциативный массив, потому его тоже перебирвем
                $newItem = new HwListItem(); //готовим пустой объект
	            //error_log(print_r($item,true));
                if ($newItem->loadRaw($type, $item)) { //если чтото в него загрузилось, то
                    if ($newItem->type == HwListItem::$TYPE_MONITOR) {
                        //мониторы могут плодиться по несколько просто от того, что их цепляли разными портами
                        //при том назваться при этом могут по разному
                        //потому оставляем только уникальные по серийникам
						if ($newItem->sn=='Not Present in EDID') $newItem->sn='';
                        $unique=true;
						if ($newItem->sn) {
							foreach ($this->items as $testitem)
								if ($testitem->sn==$newItem->sn) $unique=false;
						} else {
							foreach ($this->items as $testitem)
								if (
									$testitem->type == HwListItem::$TYPE_MONITOR
								&&
                                	$testitem->product == $newItem->product
								) $unique=false;
						}
                        //если других мониторов с этим серийником нет, то и збс.
                        if ($unique) $this->add($newItem);
                    } else $this->add($newItem); //немониторы просто добавляем
                }
            }
        }
    }

    /**
     * Генерит JSON строку с сохраненным списком объектов
     * @return string список объектов в формате JSON
     */
    public function saveJSON(){
        $data=[];
        foreach ($this->items as $item)
            $data[]=$item->saveJSON();
        return '['.implode(',',$data) .']';
    }

    /**
     * Загружает объекты из JSON строки
     * @param string $data
     */
    public function loadJSON($data) {
        if (!strlen($data)) return; //если данных нет - выходим
		//$data = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $data);
		//$json = json_decode($data,true,512,JSON_INVALID_UTF8_IGNORE|JSON_PARTIAL_OUTPUT_ON_ERROR);
		$json = json_decode($data,true);
        if (!is_array($json)) return; //если расшифровать не вышло - выходим.
        foreach ($json as $item) {
            $newItem = new HwListItem();
            $newItem->loadArr($item);
            $this->add($newItem);
        }
    }

    /**
     * Добавляет элемент к списку
     * @param HwListItem $item
     */
    public function add($item){
        if (strlen($item->uid)) {
            //если у нас явно указан uid
            if (isset($this->items[$item->uid])) {
                //если этот элемент у нас уже есть в списке, то вместо добавления
                //мы обновляем уже имеющийся элемент новыми данными
                $this->items[$item->uid]->loadArr($item->toSave());
                return;
            } else {
                //иначе просто добавляем элемент с индексом
                $this->items[$item->uid]=$item;
            }
        } else {
            //иначе добавляем элемент без индекса
            $this->items[] = $item;
        }
    }

    public function del($idx) {
        if(isset($this->items[$idx])) unset($this->items[$idx]);
    }

    /**
     * Добавляет в список элемент, найденный в отпечатке ОС
     * @param HwListItem $item
     */
    public function addFound($item){
        if ($item->globIgnored()) return; //если элемнт в списке глобального игнорирования - то игнорируем
        $item->found=true;
        //перебираем все элементы
        foreach ($this->items as $idx=>$test) {
            //среди еще не найденных в отпечатках ОС элементов ищем подходящий
            if ((!$test->found)&&($item->isEqualTo($test))) {
                //если он свободен и подходит по отпечатку - отмечаем его, как обнаруженный
                $this->items[$idx]->found=true;
                return;
            }
        }
        $this->add($item);
    }

    /**
     * Подгружает список обнаруженных элементов из другого hwList
     * @param HwList $list
     */
    public function loadFound($list) {
        foreach ($list->items as $item) $this->addFound($item);
    }

    /**
     * Возвращает объект списка с только сохраненными компонентами
     * @return HwList
     */
    public function onlySaved(){
        $saved=new HwList();
        foreach ($this->items as $item) if (strlen($item->uid)) $saved->add($item);
        return $saved;
    }

    public function hasUnsaved(){
        $unsaved=false;
        foreach ($this->items as $item) {
            if (!strlen($item->uid)) $unsaved=true;
        }
        return $unsaved;
    }

    public function signAll(){
        foreach ($this->items as $idx=>$item) $this->items[$idx]->sign();
    }

    public static function shortenCPUDescr($descr) {
		$descr=str_replace('Core(TM)2 Duo','',$descr);
		$descr=str_replace('Pentium III Xeon','',$descr);
		
    	$words=explode(' ',trim($descr));
    	unset($words[0]);
    	foreach ($words as $i=>$word) {
		    if ($word=='CPU') unset($words[$i]);
			elseif ($word=='Processor') unset($words[$i]);
			elseif ($word=='Gen') unset($words[$i]);
			elseif ($word=='Intel') unset($words[$i]);
			elseif ($word=='Intel(R)') unset($words[$i]);
			elseif ($word=='Core(TM)') unset($words[$i]);
			elseif ($word=='Atom(TM)') unset($words[$i]);
		    elseif ($word=='Xeon(R)') unset($words[$i]);
			elseif ($word=='Dual-Core') unset($words[$i]);
		    elseif ($word=='Core') unset($words[$i]);
		    elseif ($word=='Dual') unset($words[$i]);
		    elseif ($word=='COMPUTE') unset($words[$i]);
		    elseif ($word=='RADEON') unset($words[$i]);
		    elseif ($word=='2C+2G') unset($words[$i]);
			elseif ($word=='Pentium') unset($words[$i]);
			elseif ($word=='Pentium(R)') unset($words[$i]);
			elseif ($word=='Celeron(R)') unset($words[$i]);
		    elseif ($word=='Phenom(tm)') unset($words[$i]);
		    elseif ($word=='Athlon(tm)') unset($words[$i]);
		    elseif ($word=='CORES') $words[$i]="cores";
		    elseif ($word=='@') {
			    if (isset($words[$i+1])) unset($words[$i+1]);
			    unset($words[$i]);
		    }
	    }
    	return implode(' ',$words);
    }
	//возвращает короткое описание CPU
	public function getCPUShort(){
		$cpus=[];
		foreach ($this->items as $item) if (!$item->hidden) {
			if ($item->type == HwListItem::$TYPE_CPU) $cpus[]=static::shortenCPUDescr($item->product);
		}
		if (count($cpus)) return $cpus[0];
		return '';
	}
	
	public function getCpuCoresCount() {
		$cpuCount=0;
		foreach ($this->items as $item) if (!$item->hidden) {
			if ($item->type == HwListItem::$TYPE_CPU) {
				$cpuCount+= $item->cores;
			}
		}
		return ($cpuCount);
  
	}
	
	//возвращает короткое описание CPU
	public function getCPUCount(){
		$cpuCount=0;
		$cpuDescr='';
		foreach ($this->items as $item) if (!$item->hidden) {
			if ($item->type == HwListItem::$TYPE_CPU) {
				$cpuDescr=static::shortenCPUDescr($item->product);
				$cpuCount+= $item->cores;
			}
		}
		if ($cpuCount == 1 && strpos($cpuDescr,'(x')===0 && strpos($cpuDescr,')')===strlen($cpuDescr)-1) {
			$cpuDescr=substr($cpuDescr,2);
			$cpuDescr=substr($cpuDescr,0,strlen($cpuDescr)-1);
			return $cpuDescr;
		}
		if ($cpuCount) return "$cpuCount cores";
		return '';
	}
	
	
	public function getRamMb(){
		$ram=0;
		foreach ($this->items as $item) if (!$item->hidden) {
			if ($item->type == HwListItem::$TYPE_RAM) $ram+=(int)substr($item->product,0,-3);
		}
    	return (int)$ram;
	}
	
	public function getRAMShort(){
    	$ram=$this->getRamMb();
	    if (!$ram) return '';
	    return ((int)$ram < 1024)?
			(int)($ram).'MiB'
			:round($ram/1024,0).'GiB';
    }
	
    public function getHddGb() {
		$size=0;
		foreach ($this->items as $item) if (!$item->hidden) {
			if ($item->type == HwListItem::$TYPE_HDD) {
				$words=explode(' ',$item->product);
				$prod=$words[count($words)-1];
				$size+=(int)substr($prod,0,-2);
			}
		}
    	return (int)$size;
	}
	public function getHDDShort(){
    	$size=$this->getHddGb();
		if (!$size) return '';
		if ($size>1000) return (int)($size/1000).'TB';
		return (int)$size.'GB';
	}

}