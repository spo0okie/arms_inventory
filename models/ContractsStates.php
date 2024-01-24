<?php

namespace app\models;


/**
 * This is the model class for table "tech_states".
 *
 * @property int $id id
 * @property string $code Служебное имя
 * @property string $name Состояние
 * @property string $descr Описание
 * @property boolean $paid Оплачено
 * @property boolean $unpaid Ждет оплаты
 */
class ContractsStates extends ArmsModel
{
	
	public static $title='Состояние док-ов';
	public static $titles='Состояния док-ов';
	public static $description='Состояния жизненного цикла оборудования и иных сущностей в предприятии';
	//состояния неоплаты документа
	//public static $unpaidStates=['state_paywait_full','state_payed_partial'];
	//состояния полной оплаты документа
	//public static $paidStates=['state_payed_full'];
	
	private static $cache=null;
	//private static $unpaidIds=null;
	//private static $paidIds=null;

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
    public function attributeData()
    {
        return [
            'id' => [
            	'id',
			],
            'code' => [
            	'код',
				'hint'=>'используется для CSS раскраски'
			],
            'name' => [
            	'Состояние',
				'hint'=>'Короткое имя состояния документа'
			],
            'descr' => [
            	'Описание',
				'hint'=>'Пояснение к состоянию'
			],
			'paid'=>[
				'Оплачен',
				'hint'=>'Полностью или частично.'
					.'<br>Оборудование, лицензии и материалы по счетам будут считаться "в доставке"'
					.'<br>и отмечаться недоставленными, пока не будут привязаны к документу'
			],
			'unpaid'=>[
				'Ожидает оплаты',
				'hint'=>'Полностью или частично.'
					.'<br>Счета с таким статусом привязанные к договору, по которому предоставляется услуга'
					.'<br>будут формировать сумму долга по оплате услуги'
			],
        ];
    }

    public static function fetchStatuses() {
    	if (!is_null(static::$cache)) return static::$cache;
    	return static::$cache=static::find()
			->select(['id','name','code'])
			->orderBy('name')
			->all();
	}
	

}
