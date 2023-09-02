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
class Partners extends ArmsModel
{
	
	public static $title="Контрагент";
	public static $titles="Контрагенты";


	public static $all_items=null; //кэш всей таблицы
	public static $names_cache=null; //кэш сортированных имен
	
	public static $syncKey='uname';
	public static $syncableFields=['inn','kpp','uname','bname','cabinet_url','support_tel'];


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
			[['prefix'], 'string', 'max' => 5],
			[['uname', 'bname'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeData()
    {
        return [
            'id' => 'id',
			'inn' => 'ИНН',
			'kpp' => 'КПП',
			'inn_kpp' => 'ИНН/КПП',
            'uname' => [
            	'Юр. название',
				'hint' => 'Форму собственности не расписываем, пишем сокращенно'
			],
			'bname' => [
				'Бренд',
				'hint'=>'Каким красивым словом они сами себя называют вместо "ИП Пупкин"'
			],
			'prefix' => ['Префикс','hint'=>'Префикс для инв. номера оборудования, закрепленного за этой организацией<br>'
				. 'Нужно заполнять, только если<ul>'
				. '<li>Наш ИТ отдел обслуживает эту организацию (и ее оборудование)</li>'
				. '<li>Инв. номер оборудования включает в себя префикс организации</li>'
				. '</ul>'
			],
			'sname' => 'Короткое название',
			'fname' => 'Полное название',
            'comment' => [
            	'Комментарий',
				'hint' => 'Желательно указать любую полезную информацию с кем и какие вопросы решать с этим контрагентом.<br>'
				 . 'Кто персональный менеджер, кто технарь, кто бухгалтер, адреса, явки, пароли'
			],
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
		return $this->hasMany(Contracts::className(), ['id' => 'contracts_id'])
			->viaTable('{{%partners_in_contracts}}', ['partners_id' => 'id']);
	}
	
	/**
	 * Возвращает набор контрагентов в договоре
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getContracts()
	{
		return $this->hasMany(Contracts::className(), ['id' => 'contracts_id'])
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
	 * @return \yii\db\ActiveQuery
	 */
	public function getUsers()
	{
		return $this->hasMany(Users::className(), ['org_id' => 'id']);
	}
	
	
	/**
	 * Возвращает имя для поиска
	 * @return string
	 */
	public function getSname() {
		if (strpos(mb_strtolower($this->uname),mb_strtolower($this->bname))!==false) return $this->uname;
		return $this->uname.' ('.$this->bname.')';
	}
	
	/**
	 * Возвращает имя для поиска
	 * @return string
	 */
	public function getLongName() {
		return $this->uname.' ('.$this->bname.')';
	}
	
	public function getName() {
		return $this->bname?$this->bname:$this->uname;
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
	
	public function reverseLinks()
	{
		return [
			$this->services,
			$this->contracts,
			$this->users
		];
	}
}
