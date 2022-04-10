<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tech_states".
 *
 * @property int $id id
 * @property string $code Служебное имя
 * @property string $name Состояние
 * @property string $descr Описание
 */
class ContractsStates extends \yii\db\ActiveRecord
{

	public static $title='Состояния док-ов';
	public static $description='Состояния жизненного цикла оборудования и иных сущностей в предприятии';
	//состояния неоплаты документа
	public static $unpaidStates=['state_paywait_full','state_payed_partial'];
	//состояния полной оплаты документа
	public static $paidStates=['state_payed_full'];
	
	private static $cache=null;
	private static $unpaidIds=null;
	private static $paidIds=null;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'contracts_states';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'name', 'descr'], 'required'],
            [['descr'], 'string'],
            [['code'], 'string', 'max' => 64],
            [['name'], 'string', 'max' => 128],
            [['code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'code' => 'Служебное имя',
            'name' => 'Состояние',
            'descr' => 'Описание',
        ];
    }

    public static function fetchStatuses() {
    	if (!is_null(static::$cache)) return static::$cache;
    	return static::$cache=static::find()
			->select(['id','name','code'])
			->orderBy('name')
			->all();
	}
	
	/**
	 * Выбрать ID статусов по фильтру кодов
	 * @param $filter
	 * @return array
	 */
	private static function filterStatesIds($filter) {
    	$result=[];
		foreach (static::fetchStatuses() as $status) {
			/**
			 * @var $status ContractsStates
			 */
			//var_dump($status);
			//echo($status->code.' vs ['.implode(',',$filter).']<br>');
			if (array_search($status->code,$filter)!==false) $result[]=$status->id;
		}
		//error_log(implode(',',$filter).' => '.implode(',',$result));
		return $result;
	}
	
	/**
	 * Список ИД статусов неоплаты
	 * @return array
	 */
	public static function fetchUnpaidIds() {
		if (!is_null(static::$unpaidIds)) return static::$unpaidIds; //cache
		return static::$unpaidIds=static::filterStatesIds(Self::$unpaidStates);
	}
	
	/**
	 * Список ИД статусов неоплаты
	 * @return array
	 */
	public static function fetchPaidIds() {
		if (!is_null(static::$paidIds)) return static::$paidIds;	//cache
		return static::$paidIds=static::filterStatesIds(self::$paidIds);
	}
	
	
	/**
	 * Является ли переданный ИД статуса признаком неоплаты?
	 * @param $id
	 * @return bool
	 */
	public static function isUnpaid($id) {
		return array_search($id,static::fetchUnpaidIds())!==false;
	}
	
	/**
	 * Является ли переданный ИД статуса признаком оплаты?
	 * @param $id
	 * @return bool
	 */
	public static function isPaid($id) {
		return array_search($id,static::fetchPaidIds())!==false;
	}
	
	public static function fetchNames(){
		return \yii\helpers\ArrayHelper::map(static::fetchStatuses(), 'id', 'name');
	}

}
