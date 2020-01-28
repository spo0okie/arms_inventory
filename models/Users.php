<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "users".
 *
 * @property string $id Табельный номер
 * @property string $Orgeh Подразделение (id)
 * @property string $Orgtx Подразделение
 * @property string $Doljnost Должность
 * @property string $Ename Полное имя
 * @property int $Persg Тип трудоустройства
 * @property int $Uvolen Уволен
 * @property string $Login Логин (AD)
 * @property string $Email E-Mail
 * @property string $Phone Внутренний тел
 * @property string $Mobile Мобильный тел
 * @property string $work_phone Городской рабочий тел
 * @property string $Bday День рождения
 * @property string $employ_date Дата приема
 * @property string $resign_date Дата увольнения
 * @property string $manager_id Руководитель
 * @property int $nosync Отключить синхронизацию
 *
 * @property Arms[] $arms
 * @property Techs[] $techs
 * @property Arms[] $armsHead
 * @property Arms[] $armsIt
 * @property Arms[] $armsResponsible
 * @property Materials[] $materials
 */
class Users extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{


	public static $users=[];
	public static $working_cache=null;
	public static $names_cache=null;

	public static $title="Сотрудники";

	/** Тип трудоустройства
	 * 1 - В штате,
	 * 2 - Совместители внутрен,
	 * 3 - Совместители внешние,
	 * 4 - ДГПХ,
	 * 5 - Инвалиды,
	 * 6 – Пенсионеры,
	 * 7 – Несовершеннолетние
	 */

	public static $WTypes = [
		1=>'Основное трудоустройство',
	    2=>'Совместители внутрен',
		3=>'Совместители внешние',
		4=>'ДГПХ',
		5=>'Инвалиды',
		6=>'Пенсионеры',
		7=>'Несовершеннолетние',
	];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'users';
    }


	public function extraFields()
	{
		$fields=parent::extraFields();
		$fields[]='fn'; //first name
		$fields[]='mn'; //middle name
		$fields[]='ln'; //last name
		$fields[]='orgStruct'; //department
		$fields[]='org'; //org
		return $fields;
	}

	/**
     * @inheritdoc
     */
    public function rules()
    {
        return [
	        [['employee_id', 'Ename', 'Persg', 'Uvolen', ], 'required'],
	        [['Persg', 'Uvolen', 'nosync','org_id'], 'integer'],
	        [['employee_id', 'Orgeh', 'Bday', 'manager_id'], 'string', 'max' => 16],
	        [['Doljnost', 'Ename', 'Login'], 'string', 'max' => 255],
	        [['id'], 'unique'],
	        [['Email'], 'string', 'max' => 64],
	        [['Phone', 'work_phone'], 'string', 'max' => 32],
	        [['Mobile'], 'string', 'max' => 128],
        ];
    }

    /**
     * @inheritdoc
     */
	public function attributeLabels()
	{
		return [
			'employee_id' => 'Табельный номер',
			'org_id' => 'Организация',
			'Orgeh' => 'Подразделение',
			'Doljnost' => 'Должность',
			'Ename' => 'Полное имя',
			'Persg' => 'Тип трудоустройства',
			'Uvolen' => 'Уволен',
			'Login' => 'Логин (AD)',
			'Email' => 'E-Mail',
			'Phone' => 'Внутренний тел',
			'Mobile' => 'Мобильный тел',
			'work_phone' => 'Городской рабочий тел',
			'Bday' => 'День рождения',
			'manager_id' => 'Руководитель',
			'nosync' => 'Отключить синхронизацию',
			'Arms' => 'АРМ',
			'LastThreeLogins' => 'Входы',
		];
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getArms()
	{
		return $this->hasMany(Arms::className(), ['user_id' => 'id']);
	}

	/**
     * @return \yii\db\ActiveQuery
     */
    public function getArmsResponsible()
    {
        return $this->hasMany(Arms::className(), ['responsible_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArmsHead()
    {
        return $this->hasMany(Arms::className(), ['head_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArmsIt()
    {
        return $this->hasMany(Arms::className(), ['it_staff_id' => 'id']);
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTechs()
	{
		return $this->hasMany(Techs::className(), ['user_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTechsIt()
	{
		return $this->hasMany(Techs::className(), ['it_staff_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getMaterials()
	{
		return $this->hasMany(Materials::className(), ['it_staff_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrgStruct()
	{
		return $this->hasOne(OrgStruct::className(), ['id'=>'Orgeh']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrg()
	{
		return $this->hasOne(\app\models\Orgs::className(), ['id'=>'org_id']);
	}

	/**
     * @inheritdoc
     */
    public static function findIdentity($id)
    {
	    return static::findOne(['id' => $id]);
	
	    //return isset(self::$users[$id]) ? new static(self::$users[$id]) : null;
    }

    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        foreach (self::$users as $user) {
            if ($user['accessToken'] === $token) {
                return new static($user);
            }
        }

        return null;
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
	    $login=mb_strtolower($username);
	    //при поиске по логину предпочитаем сначала искать среди трудоустроенных
	    $list = static::find()->select(['id','Login','Uvolen'])->orderBy(['Uvolen'=>'ASC','id'=>'DESC'])->all();
	    foreach ($list as $item) {
		    if (!strcmp(mb_strtolower($item['Login']),$login)) return $item;
	    }
	    return null;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

	public function getFull_name() {return $this->Ename;}
	public function getStruct_name() {return is_object($dep=$this->orgStruct)?$dep->name:'';}
	public function getStruct_id() {return $this->Orgeh;}
	public function getLogin() {return $this->Login;}

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return $this->password === $password;
    }

    /**
     * Возвращает массив ключ=>значение запрошенных/всех записей таблицы
     * @param array $items список элементов для вывода
     * @param string $keyField поле - ключ
     * @param string $valueField поле - значение
     * @param bool $asArray
     * @return array
     */
    public static function listItems($items=null, $keyField = 'id', $valueField = 'Ename', $asArray = true)
    {

        $query = static::find()->filterWhere(['Uvolen'=>0])->andWhere(['!=','Login',''])->orderBy('Ename');
        if (!is_null($items)) $query->filterWhere(['id'=>$items]);
        if ($asArray) $query->select([$keyField, $valueField])->asArray();

        return \yii\helpers\ArrayHelper::map($query->all(), $keyField, $valueField);
    }
	
	/**
	 * Возвращает список неуволенных сотрудников
	 * @param null $current если передан, то возвращает еще этого, независимо от состяния уволен или нет
	 * @return array|null
	 */
	public static function fetchWorking($current=null)
	{
		if (!is_null(static::$working_cache)) return static::$working_cache;
		$query = static::find()->filterWhere(['Uvolen'=>0])->orderBy('Ename');
		$list= (static::$working_cache = \yii\helpers\ArrayHelper::map($query->all(), 'id', 'Ename'));
		if ($current && (!isset($list[$current]))) {
			$list[$current]=static::findOne($current)->Ename;
		}
		return $list;
	}

    public static function fetchNames(){
    	if (!is_null(static::$names_cache)) return static::$names_cache;
	    return static::$names_cache=static::listItems();
    }

	public static function findByLogin($login){
		$login=mb_strtolower($login);
		//при поиске по логину предпочитаем сначала искать среди трудоустроенных
		$list = static::find()->select(['id','Login','Uvolen'])->orderBy(['Uvolen'=>'ASC','id'=>'DESC'])->asArray(true)->all();
		foreach ($list as $item) {
			if (!strcmp(mb_strtolower($item['Login']),$login)) return $item['id'];
		}
		return null;
	}

	public function getLastLogin() {
		return \app\models\LoginJournal::find()
			->where(['users_id'=>$this->id])
			->andWhere(['!=','comps_id','NULL'])
			->orderBy('id desc')->one();
	}

	public function getLastThreeLogins() {
		return \app\models\LoginJournal::fetchUniqComps($this->id);
	}

	public function getLastLoginComp() {
		//$lastLogin=\app\models\LoginJournal::find(['users_id'=>$this->id,'!comp_id'=>null])->orderBy('id desc')->one();
		return \app\models\LoginJournal::find(['users_id'=>$this->id,'!comp_id'=>null])->orderBy('id desc')->one();

	}

	/**
	 * Get Last Name
	 * @return string
	 */
	public function getLn() {
		$tokens=explode(' ',$this->Ename);
		if (!count ($tokens)) return '';
		return $tokens[0];
	}

	/**
	 * Get First Name
	 * @return string
	 */
	public function getFn() {
		$tokens=explode(' ',$this->Ename);
		if (count($tokens)<2) return '';
		return $tokens[1];
	}

	/**
	 * Get First Name
	 * @return string
	 */
	public function getMn() {
		$tokens=explode(' ',$this->Ename);
		if (count($tokens)<3) return '';
		return $tokens[2];
	}
	
	public static function isAdmin() {
		return (empty(Yii::$app->params['useRBAC']) || Yii::$app->user->can('admin_access'));
	}
}
