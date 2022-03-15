<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "partners".
 *
 * @property int $id id
 * @property string $inn ИНН
 * @property string $kpp КПП
 * @property string $uname Юр. название
 * @property string $bname Бренд
 * @property string $sname Имя для поиска (юр название и бренд)
 * @property string $comment Комментарий
 * @property string $cabinet_url Урл личного кабинет
 * @property string $support_tel Тел. тех. поддержки
 *
 * @property Contracts[] $docs
 * @property Contracts[] $contracts
 * @property Contracts[] $invoices
 * @property Services[] $services
 */
class Partners extends \yii\db\ActiveRecord
{
	
	public static $title="Контрагент";
	public static $titles="Контрагенты";


	public static $all_items=null; //кэш всей таблицы
	public static $names_cache=null; //кэш сортированных имен


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'partners';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['inn', 'uname', 'bname'], 'required'],
            [['comment','support_tel','cabinet_url'], 'string'],
            [['inn'], 'string', 'max' => 12],
            [['kpp'], 'string', 'max' => 9],
            [['uname', 'bname'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'inn' => 'ИНН',
            'kpp' => 'КПП',
            'uname' => 'Юр. название',
            'bname' => 'Бренд',
            'comment' => 'Комментарий',
			'cabinet_url' => 'Ссылка на личный кабинет',
			'support_tel' => 'Телефон тех.поддержки',
        ];
    }
	
	
	/**
	 * Возвращает набор контрагентов в договоре
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getDocs()
	{
		return static::hasMany(Contracts::className(), ['id' => 'contracts_id'])
			->viaTable('{{%partners_in_contracts}}', ['partners_id' => 'id']);
	}
	
	/**
	 * Возвращает набор контрагентов в договоре
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getContracts()
	{
		return static::hasMany(Contracts::className(), ['id' => 'contracts_id'])
			->viaTable('{{%partners_in_contracts}}', ['partners_id' => 'id'])
			->where(['like','name',Contracts::$dictionary['contract']]);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getServices()
	{
		return $this->hasMany(Services::className(), ['partners_id' => 'id']);
	}
	
	/**
	 * Возвращает имя для поиска
	 * @return string
	 */
    public function getSname() {
    	if (strpos(mb_strtolower($this->uname),mb_strtolower($this->bname))!==false) return $this->uname;
    	return $this->uname.' ('.$this->bname.')';
    }

	public static function fetchAll(){
		if (is_null(static::$all_items)) {
			$tmp=static::find()->all();
			static::$all_items=[];
			foreach ($tmp as $item) static::$all_items[$item->id]=$item;
		}
		return static::$all_items;
	}

	public static function fetchItem($id){
		return isset(static::fetchAll()[$id])?
			static::fetchAll()[$id]
			:
			null;
	}

	public static function fetchItems($ids){
		$tmp=[];
		foreach ($ids as $id) $tmp[$id]=static::fetchItem($id);
		return $tmp;
	}

	/**
	 * возвращает элементы, поле которые имеет значение value
	 * @param $field
	 * @param $value
	 * @return array
	 */
	public static function fetchByField($field,$value){
		$tmp=[];
		foreach (static::fetchAll() as $item)
			if ($item->$field == $value) $tmp[$item->id]=$item;
		return $tmp;
	}

/*    public static function fetchNames(){
		$list= static::find()
			->select(['id','uname','bname'])
			->all();
		return yii\helpers\ArrayHelper::map($list, 'id', 'sname');
	}*/

	public static function fetchNames()
	{
		if (!is_null(static::$names_cache)) return static::$names_cache;
		$names=[];
		foreach (static::fetchAll() as $item) $names[$item->id]=$item->sname;
		//$names= ArrayHelper::map(static::fetchAll(), 'id', 'name');
		asort($names);
		return static::$names_cache=$names;
	}


}
