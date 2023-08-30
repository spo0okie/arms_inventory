<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "found_soft".
 *
 * @property int $id
 * @property int $soft_id
 * @property int $comp_id
 * @property int $manufacturers_id
 * @property string $hits
 * @property array $strings
 * @property array $additional_strings
 * @property array $hit_items
 * @property array $foundStrings
 *
 * @property Comps $comp
 * @property Soft $soft
 * @property bool $searched
 * @property integer $count
 */
class SoftHits extends \yii\db\ActiveRecord
{
	//флажок того что в этом объекте реально производился поиск
	private $searched_flag=false;

	//строки среди которых ищем продукт
	public $strings=[];

	//дополнительные строки среди которых ищем продукт, если нашлись совпадения в основных
	public $additional_strings=[];

	//совпадения масок и строк
	public $hit_items=[];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'soft_hits';
    }

	/**
	 * Сравнение хитов на основании релевантности
	 * @return integer
	 */
	public static function compareByRelevance ($a,$b){
		if ($a->count==$b->count) {
			//если количество строк идентично, то сравним суммарную длинну отработавших выражений
			//из предположения, что чем длиннее выражение, тем точнее поиск
			$a_r=$a->relevance;
			$b_r=$b->relevance;
			if ($a_r==$b_r) return 0;
			return ($a_r < $b_r)? 1 : -1;
		};
		return ($a->count < $b->count)? 1 : -1;
	}


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'soft_id', 'comp_id', 'hits'], 'required'],
            [['id', 'soft_id', 'comp_id'], 'integer'],
            [['hits'], 'string'],
            [['id'], 'unique'],
            [['comp_id'], 'exist', 'skipOnError' => true, 'targetClass' => Comps::className(), 'targetAttribute' => ['comp_id' => 'id']],
            [['soft_id'], 'exist', 'skipOnError' => true, 'targetClass' => Soft::className(), 'targetAttribute' => ['soft_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'soft_id' => 'Продукт',
            'comp_id' => 'Комп',
            'hits' => 'Совпадения',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getComp()
    {
        return $this->hasOne(Comps::className(), ['id' => 'comp_id']);
    }

	/**
	 * @return bool
	 */
    public function getSearched()
    {
    	return $this->searched_flag;
    }

	/**
	 * @return integer
	 */
	public function getManufacturers_id()
	{
		return Soft::fetchItem($this->soft_id)->manufacturers_id;
	}

	/**
     * @return \yii\db\ActiveQuery
     */
    public function getSoft()
    {
        return $this->hasOne(Soft::className(), ['id' => 'soft_id']);
    }


    public static function compare($mask,$string) {
	    if (strlen($mask) && strlen($string)) {
		    try {
			    if (preg_match('/'.$mask.'/ui',$string)) return true;
		    } catch (\Exception $exception) {
			    return false;
		    }
	    }
	    return false;
    }

	/**
	 * Возвращает массив совпадений переданных масок с переданным массивом входных строк возвращает массив совпадений полностью (маска, строка)
	 * @param array $masks маски по которым можно идентифицировать продукт
	 * @param array $strings входные строки среди которых ищем продукт
	 * @return array совпавшие маски и строки
	 */
	public static function SearchHits($masks,$strings) {
		$hits=[];
		//каждый хит - карточка попадания (маска и строка)
		foreach ($strings as $string) { //перебираем строки среди которых ищем этот софт
			foreach ($masks as $mask) { //перебираем маски продукта
				if (static::compare($mask,$string)) {
					$hits[]=[
						'string'    =>$string,
						'mask'      =>$mask,
					];
				}
			}
		}
		return $hits;
	}


	/**
	 * Непосредственно поиск совпадений
	 * @return bool успех
	 */
	public function search(){
		$this->searched_flag=true;
		//Проверяем что у нас есть объект ПО с которым работать
		if (!($this->soft_id) && !(is_object($this->soft))) return false;
		//ищем основные совпадения среди основных масок и строк
		$this->hit_items=static::SearchHits(
			Soft::fetchItemsArray($this->soft_id),
			$this->strings
		);

		//если у нас есть основные совпадения, то добавляем дополнительные
		if (count($this->hit_items)) {
			$this->hit_items = array_merge(
				$this->hit_items,
				static::SearchHits(     //ищем доп совпадения
					Soft::fetchAdditionalArray($this->soft_id),
					array_merge($this->strings, $this->additional_strings)
				)
			);
			return true; //возвращаем успех операции
		} else return false; //ничего не нашлось
	}

	/**
	 * Непосредственно поиск совпадений
	 * @return bool успех
	 */
	public function recheck_after_shrink($strings){
		$changed=false;
		//перебираем все уже обнаруженные элементы
		foreach ($this->hit_items as $idx=>$item) {
			//и проверяем что те строки которыми они обнаружились - еще присутствуют в поиске
			if (array_search($item['string'],$strings)===false) {
				$changed=true;
				unset ($this->hit_items[$idx]);
				//echo "now missing ${item['string']}\n";
			}
		}
		//возвращаем флажок, изменилось ли чтото
		return $changed;
	}

	public function getCount()
	{
		if (!$this->searched) $this->search();
		return count($this->hit_items);
	}

	/**
	 * Проверяем попадает ли карточка ПО из объекта SwList в наш хит
	 * @param array $card
	 * @return bool
	 */
	public function rawCardCheck($card)
	{
		if (
			//еесли в карточке продукта проставлен производитель и он совпадает с нашим
			(isset($card['manufacturers_id'])&&($this->manufacturers_id==$card['manufacturers_id']))
			||
			//или если производителя нет вообще
			!strlen($card['publisher'])
		) {
			//по каждому из конкретных совпадений проверяем попадает ли карточка в него
			return $this->isFound($card['name']);
		}
		return false;
	}

	/**
	 * ищем найдена ли такая строка?
	 * @param $test
	 * @return bool
	 */
	public function isFound ($test) {
		foreach ($this->hit_items as $hit)
			if (strcmp($hit['string'], $test) == 0) return true;
		return false;
	}

	public function getRelevance()
	{
		$relevance=0;
		foreach ($this->hit_items as $hit) $relevance+=strlen($hit['mask']);
		return $relevance;
	}
}
