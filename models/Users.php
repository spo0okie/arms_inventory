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
 * @property string $private_phone Личный сотовый, домашний и всякое такое
 * @property string $Bday День рождения
 * @property string $employ_date Дата приема
 * @property string $resign_date Дата увольнения
 * @property string $manager_id Руководитель
 * @property int $nosync Отключить синхронизацию
 * @property int $ln Last Name
 * @property int $fn First Name
 * @property int $mn Middle Name
 * @property int $shortName Сокращенные И.О.
 *
 * @property Comps[] $comps
 * @property Comps[] $compsFromServices
 * @property Comps[] $compsTotal
 * @property Arms[] $arms
 * @property Techs[] $techs
 * @property Arms[] $armsHead
 * @property Arms[] $armsIt
 * @property Arms[] $armsResponsible
 * @property Materials[] $materials
 * @property Services[] $services
 * @property LicGroups[] $licGroups
 * @property LicItems[] $licItems
 * @property LicKeys[] $licKeys
 */
class Users extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{


	public static $users=[];
	public static $working_cache=null;
	public static $names_cache=null;
	public static $title="Сотрудник";
	public static $titles="Сотрудники";
	
	private $tokens_cache=null; //имя разбитое на токены
	
	
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
		1=>['Основное трудоустройство','Осн.'],
	    2=>['Совместительство внутреннее','Совм.'],
		3=>['Совместительство внешние','Внеш.'],
		4=>['ДГПХ','ДГПХ',],
		5=>['Инвалид','Инв.',],
		6=>['Пенсионер','Пенс.',],
		7=>['Несовершеннолетние','Несов.',],
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
		$fields[]='licKeys_ids';
		$fields[]='licItems_ids';
		$fields[]='licGroups_ids';
		$fields[]='licKeys';
		$fields[]='licItems';
		$fields[]='licGroups';
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
	        [['Doljnost', 'Ename', 'Login','Mobile','private_phone'], 'string', 'max' => 255],
			[['notepad'],'safe'],
	        [['id'], 'unique'],
	        [['Email'], 'string', 'max' => 64],
	        [['Phone', 'work_phone'], 'string', 'max' => 32],
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
			'private_phone' => 'Личный тел',
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
	 * Возвращает сервисы, за которые отвечает пользователь
	 * @return \yii\db\ActiveQuery
	 */
	public function getServices()
	{
		return $this->hasMany(Services::className(), ['responsible_id' => 'id']);
	}
	
	/**
	 * Возвращает компы, за которые отвечает пользователь (явно через прямое назначение)
	 * @return Comps[]
	 */
	public function getCompsFromServices()
	{
		$result=[];
		foreach ($this->services as $service)
			foreach ($service->comps as $comp)
				if ($comp->responsible->id == $this->id)
					$result[$comp->id]=$comp;
		return $result;
	}
	
	public function getCompsTotal() {
		$result=[];
		foreach ($this->comps as $comp)
			$result[$comp->id]=$comp;
		foreach ($this->compsFromServices as $comp)
			$result[$comp->id]=$comp;
		return $result;
	}
	
	/**
	 * Возвращает сервисы, за которые отвечает пользователь
	 * @return \yii\db\ActiveQuery
	 */
	public function getComps()
	{
		return $this->hasMany(Comps::className(), ['user_id' => 'id']);
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

	public function getFullName() {return $this->Ename;}
	public function getStructName() {return is_object($dep=$this->orgStruct)?$dep->name:null;}
	public function getStruct_id() {return $this->Orgeh;}
	public function getLogin() {return $this->Login;}

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
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
		$query = static::find()->filterWhere(['Uvolen'=>0])->orderBy(['Ename'=>'ASC','Persg'=>'ASC'])->groupBy('Ename');
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
	 * Get First Name
	 * @return array
	 */
	public function getTokens() {
		if (!is_null($this->tokens_cache)) return $this->tokens_cache;
		return $this->tokens_cache=explode(' ',$this->Ename);
	}
	
	/**
	 * Get Last Name
	 * @return string
	 */
	public function getLn() {
		if (!count($tokens=$this->getTokens())) return '';
		return $tokens[0];
	}

	
	/**
	 * Get First Name
	 * @return string
	 */
	public function getFn() {
		if (count($tokens=$this->getTokens())<2) return '';
		return $tokens[1];
	}
	
	/**
	 * Get First Name
	 * @return string
	 */
	public function getMn() {
		if (count($tokens=$this->getTokens())<3) return '';
		return $tokens[2];
	}
	
	/**
	 * Get First Name
	 * @return string
	 */
	public function getShortName() {
		if (($count=count($tokens=$this->getTokens()))<2) return $this->Ename;
		for ($i=1;$i<$count;$i++) {
			$tokens[$i]=mb_substr($tokens[$i],0,1).'.';
		}
		return implode(' ',$tokens);
	}
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getLicGroups()
	{
		return static::hasMany(LicGroups::className(), ['id' => 'lic_groups_id'])
			->viaTable('{{%lic_groups_in_users}}', ['users_id' => 'id']);
	}
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getLicItems()
	{
		return static::hasMany(LicItems::className(), ['id' => 'lic_items_id'])
			->viaTable('{{%lic_items_in_users}}', ['users_id' => 'id']);
	}
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getLicKeys()
	{
		return static::hasMany(LicKeys::className(), ['id' => 'lic_keys_id'])
			->viaTable('{{%lic_keys_in_users}}', ['users_id' => 'id']);
	}
	
	/**
	 * @return bool
	 */
	public static function isAdmin() {
		return (empty(Yii::$app->params['useRBAC']) || Yii::$app->user->can('acl'));
	}

	/**
	 * @return bool
	 */
	public static function isViewer() {
		return (
			empty(Yii::$app->params['useRBAC']) ||
			empty(Yii::$app->params['authorizedView']) ||
			Yii::$app->user->can('view')
		);
	}
}
