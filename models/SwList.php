<?php
/**
 * Created by PhpStorm.
 * User: Spookie
 * Date: 07.03.2018
 * Time: 10:01
 *
 * Список ПО внутри компа/ОС аналогичный по функционалу HwList
 */

namespace app\models;

use Yii;

/*
 * Список оборудования
 */
class SwList {

    private static ?array $catalogIndex = null;

    public $items=[];       //элементы списка оборудования
    public $rawData=null;   //сырые текстовые данные
    public $data=null;      //расшифрованные json данные
    /*
     * $this->data - массив - отпечаток софта в ОС следующего вида:
     * [
     *      [
     *          'publisher' => '...',       //грузится прямо из raw_sw
     *          'name'      => '...',       //грузится прямо из raw_sw
     *          'manufacturer_id => '...',  //добавляется позже при findDevs(), или не добавляется
     *      ],
     *      ...
     * ]
     */
    public $manufacturers_ids=[]; //список производителей ПО в этом списке
    public $hits=[];        //массив совпадений при поиске продуктов вида [soft_id=>hits_count,...]

    /* список наименований продуктов с распознанными производителями
     * вида
     * $products_by_dev= [
     *   manufacturer_id=> [ //распознанный производитель
     *      product_name1, //наименование продукта 1
     *      product_name2, //наименование продукта 2
     *      ...
     *   ],
     *   ...
     * ];
     */
    public $products_by_dev=[];

    /**
     * Добавить элемент и проинициализировать
     * @param integer $id Идентификатор ПО
     */
    public function addItem($id)
    {
        if (isset($this->items[$id])) return;
        $this->items[$id]=[
            'id'=>$id,
            'saved'=>false,
            'found'=>false,
	        'ignored'=>false,
	        'free'=>false,
            'agreed'=>false,
            'dev'=>null,
            'name'=>null,
        ];
        $product=Soft::fetchItem($id);
        if (!is_null($product)) {
            $this->items[$id]['ignored']=$product->isIgnored;
	        $this->items[$id]['agreed']=$product->isAgreed;
	        $this->items[$id]['free']=$product->isFree;
            $this->items[$id]['dev']=$product->manufacturers_id;
            $this->items[$id]['descr']=$product->descr;
        }
    }

    /**
     * Загрузить элемент (из сохраненного списка)
     * @param integer $soft_id
     */
    public function loadItem($soft_id)
    {
        $this->addItem($soft_id);
        $this->items[$soft_id]['saved']=true;
    }

    /**
     * Добавляет элемент из обнаруженного ПО
     * @param integer $soft_id
     */
    public function findItem($hit)
    {
        $this->addItem($hit->soft_id);
	    $this->items[$hit->soft_id]['found']=true;
	    $this->items[$hit->soft_id]['hit']=$hit;
    }


    /**
     * Ищет разработчиков среди сырых данных
     * @throws \Exception
     * @throws \Throwable
     */
    public function findDevs()
    {
        //прогрев словаря производителей: каждая строка отпечатка ищет производителя по написанию,
        //без кэша это запрос на строку
        ManufacturersDict::initCache();

        //сразу объявляем набор приложений без разработчика (ОС не всегда его отдает)
        if (!isset($this->products_by_dev['no_dev'])) $this->products_by_dev['no_dev'] = [];


        //сначала выгребаем из общей кучи софт, с распознаваемым разработчиком
        foreach ($this->data as $i => $item) { //перебираем кучу
            $publisher = $item['publisher'] ?? '';
            $dev = ManufacturersDict::fetchManufacturer($publisher);
            // Издатель пакета может отличаться от разработчика. Для структурированных
            // записей разрешаем точное основное имя, если оно однозначно в справочнике.
            if (is_string($item['version'] ?? null) && $item['version'] !== '') {
                if (!isset($exactVendors)) {
                    $exactVendors = self::catalogIndex()['vendors'];
                }
                $rule = self::exactNameRule($item['name']);
                $vendors = $exactVendors[$rule] ?? [];
                if (count($vendors) === 1) $dev = array_key_first($vendors);
            }
            if (!is_null($dev)) { //если производитель обнаружился
                $this->data[$i]['manufacturers_id'] = $dev; //добавляем его в исходные данные

                //заполняем список обнаруженных производителей ПО для дальнейшего поиска программных продуктов

                //если для этого производителя еще не объявлен массив продуктов - объявляем
                if (!isset($this->products_by_dev[$dev])) $this->products_by_dev[$dev] = [];

                //если производителя нет в списке производителей - добавляем
                if (array_search($dev, $this->manufacturers_ids) === false) $this->manufacturers_ids[] = $dev;

                //если этого продукта еще нет в массиве продуктов этого производителя - добавляем
                if (array_search($item['name'], $this->products_by_dev[$dev]) === false) {
                    $this->products_by_dev[$dev][] = $item['name'];
                }
				
            } elseif (!strlen($item['publisher']??'')) {
                //если производителя нет в принципе, то добавляем продукт в отдельную категорию
                $this->products_by_dev['no_dev'][] = $item['name'];
            }
        }
    }

    /**
     * Ищет совпадения продуктов
     */
    public function findHits()
    {
        //поиск совпадений продуктов уже обнаруженных разрабочиков

        //подгружаем все известные продукты обнаруженных разработчиков
        $dev_products=Soft::findAll(['manufacturers_id'=>$this->manufacturers_ids]);

        //перебираем все продукты с разработчиками
        if (is_array($dev_products)) foreach ($dev_products as $item) {
        	//формируем карточку "попадания"
	        $hit=new \app\models\SoftHits([
		        'soft_id'=>$item->id,
		        'strings'=>$this->products_by_dev[$item->manufacturers_id], //ищем сначала среди продуктов этого же разработчика
		        'additional_strings'=>$this->products_by_dev['no_dev'], //дополнительно среди продуктов без явного разработчика
	        ]);

	        if ($hit->count>0) {
		        $this->hits[]=$hit; //если совпадения есть - запоминаем это
	        } else
		        unset($hit);
        }

        // Без основных строк второго прохода ни одна карточка не даст совпадения.
        if ($this->products_by_dev['no_dev'] && is_array($all_products=Soft::fetchAll())) foreach ($all_products as $item) {
	        $hit=new \app\models\SoftHits([
		        'soft_id'=>$item->id,
		        'strings'=>$this->products_by_dev['no_dev'], //ищем среди всех продуктов нераспознанных разработчиков
		        'additional_strings'=>isset($this->products_by_dev[$item->manufacturers_id])?
			        //но как дополнительные продукты подсовываем именно проверяемого разработчика
			        $this->products_by_dev[$item->manufacturers_id]
			        :
			        []
	        ]);
	        if ($hit->count>0) {
		        $this->hits[]=$hit; //если совпадения есть - запоминаем это
	        } else
		        unset($hit);
        }

        //сортируем обнаруженные совпадения продуктов по количеству совпавших строк по убыванию
        usort($this->hits,
            function($a,$b){
                return SoftHits::compareByRelevance($a,$b);
            }
        );
    }



    /**
     * Вписывает найденные элементы из ранее обнаруженных попаданий $this->hits
     */
    public function findItems()
    {
    	//var_dump($this->products_by_dev);
    	//берем за базу исходные данные поиска
        //составляем список 100% обнаруженных программных продуктов
        foreach ($this->hits as $idx=>$hit) {
        	//если вдруг у нас нашелся продукт из (no_dev) и у него есть разработчик, а мы для этого разработчика не создали набор ПО
	        if (!isset($this->products_by_dev[$hit->manufacturers_id])) $this->products_by_dev[$hit->manufacturers_id]=[];

        	//проверяем все еще ли наш продут ищется после того, как из исходного списка убрали строки уже найденных продуктов
			if ($hit->recheck_after_shrink(
				array_merge(
					$this->products_by_dev[$hit->manufacturers_id],
					$this->products_by_dev['no_dev']
				)
			) && $hit->count==0) {
				//не ищется более
				unset($this->hits[$idx]);
				continue;
			}
            $this->findItem($hit); //добавляем продукт c флажком "обнаружен"

	        //убираем из исходного списка строки, которые присутствуют в этом продукте
			//чтобы исключить двойное обнаружение продуктоы

	        //убираем карточку из сырых данных
			foreach ($this->data as $id=>$card)
				if ($hit->rawCardCheck($card)) {
					// Извлекаем версию только из карточки, доставшейся продукту после разрешения конфликтов.
					$package = $hit->packageVersion($card);
					if ($package !== null) {
						$this->items[$hit->soft_id]['packages'][serialize($package)] = $package;
					}
	        	    unset ($this->data[$id]);
				}


	        //убираем из продуктов этого производителя
	        foreach ($this->products_by_dev[$hit->manufacturers_id] as $id=>$item)
		        if ($hit->isFound($item))
			        unset ($this->products_by_dev[$hit->manufacturers_id][$id]);

	        //убираем из продуктов без производителя
	        foreach ($this->products_by_dev['no_dev'] as $id=>$item)
		        if ($hit->isFound($item))
			        unset ($this->products_by_dev['no_dev'][$id]);

        }
    }


	/**
     * Загружет сырые данные из ОС (\app\models\Comps->raw_sw)
     * @param string $data исходные данные raw_sw
     * @return bool успех
     * @throws \Exception
     * @throws \Throwable
     */
    public function loadRaw($data)
    {
        if (!strlen($data??'')) return false;
        $this->rawData='['.$data.']';

        $json = json_decode($this->rawData,true);
        if (!is_array($json)) return false;
        $this->data=$json;

        $legacyRows = $this->normalizeLegacyDebPackages();
        $this->findDevs();
        $this->findHits();
        $this->findItems();
        // Нераспознанные строки возвращаем в исходном виде для выгрузки остатков.
        foreach (array_intersect_key($legacyRows, $this->data) as $index => $item) {
            $this->data[$index] = $item;
        }
        return true;
    }

    /** Возвращает основную маску, которая соответствует полному имени. */
    private static function exactNameRule(string $name): string
    {
        return '^'.str_replace('\-', '-', preg_quote($name, '/')).'$';
    }

    /** Индексирует маски и карточки по разработчику на время действия кэша Soft. */
    private static function catalogIndex(): array
    {
        if (!Soft::$disable_cache && self::$catalogIndex !== null) return self::$catalogIndex;

        $index = ['vendors'=>[], 'cards'=>[], 'by_vendor'=>[]];
        foreach (Soft::fetchAll() as $product) {
            $vendor = $product->manufacturers_id;
            $index['by_vendor'][$vendor][] = $product;
            if (!$product->items) continue;
            foreach ($product->getItemsArray() as $mask) {
                $mask = str_replace('\-', '-', $mask);
                $index['vendors'][$mask][$vendor] = true;
                $index['cards'][$mask][$product->id] = $product;
            }
        }
        // В режиме обновления справочника штатный кэш Soft отключён.
        if (!Soft::$disable_cache) self::$catalogIndex = $index;
        return $index;
    }

    /** Отделяет версию у подтверждённых старых записей Debian и Ubuntu. */
    private function normalizeLegacyDebPackages(): array
    {
        $rules = Yii::$app->params['soft.legacy_deb_packages'] ?? [];
        if (!$rules) return [];

        $originals = [];
        $canNormalize = [];
        foreach ($this->data as $index => $item) {
            $publisher = $item['publisher'] ?? '';
            $rawName = $item['name'] ?? '';
            if ($publisher === '' || array_key_exists('version', $item)) continue;

            $rule = $rules[$publisher][$rawName] ?? null;
            if (!is_array($rule)) continue;
            $name = $rule[0] ?? null;
            $version = $rule[1] ?? null;
            if (!is_string($name) || $name === '' || !is_string($version) || $version === ''
                || $rawName !== $name.'-'.$version) continue;

            // Настройка действует, пока карточка с точной маской существует и однозначна.
            $catalog ??= self::catalogIndex();
            $canNormalize[$name] ??= self::hasUniqueLegacyDebMatch($name, $catalog);
            if (!$canNormalize[$name]) continue;

            $originals[$index] = $item;
            $this->data[$index]['name'] = $name;
            $this->data[$index]['version'] = $version;
        }
        return $originals;
    }

    /** Проверяет единственную точную карточку и пересечения масок у её разработчика. */
    private static function hasUniqueLegacyDebMatch(string $name, array $catalog): bool
    {
        $candidates = $catalog['cards'][self::exactNameRule($name)] ?? [];
        if (count($candidates) !== 1) return false;
        $target = reset($candidates);

        // Другой продукт того же разработчика мог бы забрать строку по более широкой маске.
        foreach ($catalog['by_vendor'][$target->manufacturers_id] as $product) {
            if ($product->id === $target->id) continue;
            foreach (array_merge($product->getItemsArray(), $product->getAdditionalArray()) as $mask) {
                if (SoftHits::compare($mask, $name)) return false;
            }
        }
        return true;
    }

    public function loadItems($items)
    {
        foreach ($items as $item) $this->loadItem($item);
    }

	/** Суффикс названия при выводе строки отпечатка или распознанной установки. */
	public static function versionSuffix(array $item): string
	{
		// У продукта может быть несколько установленных пакетов одной версии.
		$versions = [];
		foreach ($item['packages'] ?? [$item] as $package) {
			$version = $package['version'] ?? null;
			if (is_string($version) && trim($version) !== '') $versions[] = trim($version);
		}
		return $versions ? ' '.implode(', ', array_unique($versions)) : '';
	}

    public function sortByName(){
        $manufacturers=Manufacturers::fetchNames();
        usort($this->items, function ($a, $b) use ($manufacturers) {
            if (($x = strcasecmp(
				isset($manufacturers[$a['dev']])?$manufacturers[$a['dev']]:'',
				isset($manufacturers[$b['dev']])?$manufacturers[$b['dev']]:''
			)) !== 0) return $x;
            return strcasecmp($a['descr'], $b['descr']);
        });

    }

	/** Самостоятельные установки и обнаруженные компоненты каждого видимого продукта. */
	public function getSoftEffective(): array
	{
		$items = array_column($this->items, null, 'id');
		$found = array_column(array_filter($items, static fn($item) => $item['found']), 'id');
		if (count($found) < 2) return $items;

		// Паспортная запись без совпадения в отпечатке не поглощает другие продукты.
		$edges = (new \yii\db\Query())->from('soft_components')
			->select(['component_id', 'parent_id'])
			->where(['component_id'=>$found, 'parent_id'=>$found])
			->orderBy('parent_id')->all();
		$parents = [];
		foreach ($edges as $edge) {
			// При нескольких родителях выбираем первый по ID независимо от сортировки списка.
			$parents[$edge['component_id']] ??= $edge['parent_id'];
		}
		foreach ($parents as $component => $parent) {
			$visited = [$component=>true];
			while (isset($parents[$parent]) && !isset($visited[$parent])) {
				$visited[$parent] = true;
				$parent = $parents[$parent];
			}
			// При повреждённом цикле оставляем записи доступными отдельно.
			if (isset($visited[$parent])) continue;
			$items[$parent]['components'][] = $items[$component];
			unset($items[$component]);
		}
		return $items;
	}

    public function getAgreed() {
        $tmp=[];
        foreach ($this->getSoftEffective() as $item)
            if ($item['agreed']) $tmp[$item['id']]=$item;

        return $tmp;
    }

	public function hasUnsavedAgreed() {
		$has=false;
		foreach ($this->getAgreed() as $item)
			if ($item['agreed'] && !$item['saved']) $has=true;

		return $has;
	}

	public function hasSavedFree() {
		foreach ($this->items as $item)
			if ($item['free'] && $item['saved']) return true;
		return false;
	}

	public function getSavedFree() {
    	$list=[];
		foreach ($this->items as $item)
			if ($item['free'] && $item['saved']) $list[]=$item['id'];
		return $list;
	}

	public function hasSavedIgnored() {
		foreach ($this->items as $item)
			if ($item['ignored'] && $item['saved']) return true;
		return false;
	}

	public function getSavedIgnored() {
		$list=[];
		foreach ($this->items as $item)
			if ($item['saved'] && $item['ignored']) $list[]=$item['id'];
		return $list;
	}

}
