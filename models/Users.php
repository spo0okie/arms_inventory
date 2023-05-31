<?php

namespace app\models;

use app\helpers\ArrayHelper;
use app\helpers\FieldsHelper;
use app\helpers\QueryHelper;
use Yii;

/**
 * This is the model class for table "users".
 *
 * @property string $id Табельный номер
 * @property string $Orgeh Подразделение (id)
 * @property string $Orgtx Подразделение
 * @property string $Doljnost Должность
 * @property string $Ename Полное имя
 * @property int $org_id Организация
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
 * @property int         $nosync Отключить синхронизацию
 * @property string		$ln Last Name
 * @property string		$fn First Name
 * @property string		$mn Middle Name
 * @property string		$shortName Сокращенные И.О.
 * @property string		$uid
 *
 * @property Aces[]      $aces
 * @property Comps[]     $comps
 * @property Comps[]     $compsFromServices
 * @property Comps[]     $compsTotal
 * @property Contracts[] $contracts
 * @property Techs[]     $techs
 * @property Techs[]     $techsIt
 * @property Techs[]     $techsHead
 * @property Techs[]     $techsResponsible
 * @property Materials[] $materials
 * @property Services[]  $services
 * @property Services[]  $infrastructureServices
 * @property Services[]  $supportServices
 * @property Services[]  $infrastructureSupportServices
 * @property LicGroups[] $licGroups
 * @property LicItems[]  $licItems
 * @property LicKeys[]   $licKeys
 * @property Partners    $org
 * @property OrgStruct   $orgStruct
 */
class Users extends ArmsModel implements \yii\web\IdentityInterface
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
	        [['Ename', 'Persg', 'Uvolen', ], 'required'],
	        [['Persg', 'Uvolen', 'nosync','org_id'], 'integer'],
	        [['employee_id', 'Orgeh', 'Bday', 'manager_id'], 'string', 'max' => 16],
	        [['Doljnost', 'Ename', 'Login','Mobile','private_phone'], 'string', 'max' => 255],
			[['notepad'],'safe'],
	        [['id'], 'unique'],
	        [['Email','uid'], 'string', 'max' => 64],
	        [['Phone', 'work_phone'], 'string', 'max' => 32],
			['Login', function ($attribute, $params, $validator) {
				$exist=static::find()->where(['Login'=>$this->Login])->one();
				/** @var $exist Users */
				if (is_object($exist)) {
					//если этот логин у того же самого человека (совпадает uid) то и пофик
					if (strlen($this->uid) && $this->uid==$exist->uid) return;
					$this->addError($attribute, 'Такой логин уже занят пользователем '.$exist->Ename);
				}
			}],
        ];
    }

    /**
     * @inheritdoc
     */
	public function attributeData()
	{
		return [
			'employee_id' => [
				'Таб. №',
				'hint'=>'Табельный номер сотрудника<br>(конкретно этого его трудоустройства)'
			],
			'org_id' => 'Организация',
			'org_name'=>['alias'=>'org_id'],
			'Orgeh' => 'Подразделение',
			'orgStruct_name' => ['alias'=>'Orgeh'],
			'Doljnost' => 'Должность',
			'Ename' => 'Полное имя',
			'shortName' => [
				'Короткое имя',
				'indexHint'=>'Отображаться будет "Фамилия И.О.",<br>'.
					'поиск будет вестись по полному имени.<br>'.
					QueryHelper::$stringSearchHint
			],
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
			'nosync' => [
				'Отключить синхронизацию',
				'hint'=>'Запрет внешнему скрипту синхронизации с кадровой БД обновлять эту запись<br>'.
					'<i>(Должно быть реализовано во внешнем скрипте)</i>',
			],
			'arms' => 'АРМ',
			'techs' => 'Оборудование',
			'uid' => 'Идентификатор',
			'LastThreeLogins' => 'Входы',
		];
	}
	
	/**
	 * Список всех объектов ссылающихся на этот
	 * @return array
	 */
	public function reverseLinks() {
		return [
			$this->aces,
			$this->techs,
			$this->techsResponsible,
			$this->techsHead,
			$this->techsIt,
			$this->comps,
			$this->licGroups,
			$this->licKeys,
			$this->licItems,
			$this->materials,
			$this->services,
			$this->infrastructureServices,
			$this->supportServices,
			$this->infrastructureSupportServices,
			$this->contracts,
		];
	}
	
	/**
	 * Возвращает привязанные элементы доступа
	 * @return \yii\db\ActiveQuery
	 */
	public function getAces()
	{
		return $this->hasMany(Aces::className(), ['id' => 'aces_id'])->from(['users_aces'=>Aces::tableName()])
			->viaTable('{{%users_in_aces}}', ['users_id' => 'id']);
	}
	

	/**
     * @return \yii\db\ActiveQuery
     */
    public function getTechsResponsible()
    {
        return $this->hasMany(Techs::className(), ['responsible_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTechsHead()
    {
        return $this->hasMany(Techs::className(), ['head_id' => 'id']);
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
	 * Возвращает закрепленное на компе ПО
	 */
	public function getContracts()
	{
		return $this->hasMany(Contracts::class, ['id' => 'contracts_id'])
			->viaTable('{{%users_in_contracts}}', ['users_id' => 'id']);
	}
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getLicGroups()
	{
		return $this->hasMany(LicGroups::className(), ['id' => 'lic_groups_id'])
			->viaTable('{{%lic_groups_in_users}}', ['users_id' => 'id']);
	}
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getLicItems()
	{
		return $this->hasMany(LicItems::className(), ['id' => 'lic_items_id'])
			->viaTable('{{%lic_items_in_users}}', ['users_id' => 'id']);
	}
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getLicKeys()
	{
		return $this->hasMany(LicKeys::className(), ['id' => 'lic_keys_id'])
			->viaTable('{{%lic_keys_in_users}}', ['users_id' => 'id']);
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
		return $this->hasOne(OrgStruct::className(), ['id'=>'Orgeh','org_id'=>'org_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getOrg()
	{
		return $this->hasOne(\app\models\Partners::className(), ['id'=>'org_id']);
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
	 * Возвращает сервисы, за которые отвечает пользователь
	 * @return \yii\db\ActiveQuery
	 */
	public function getSupportServices()
	{
		return $this->hasMany(Services::class, ['service_id' => 'id'])
			->from(['support_services'=>Services::tableName()])
			->viaTable('{{%users_in_services}}', ['user_id' => 'id']);
	}
	
	/**
	 * Возвращает сервисы, за которые отвечает пользователь
	 * @return \yii\db\ActiveQuery
	 */
	public function getInfrastructureSupportServices()
	{
		return $this->hasMany(Services::class, ['service_id' => 'id'])
			->from(['support_infrastructure_services'=>Services::tableName()])
			->viaTable('{{%users_in_svc_infrastructure}}', ['users_id' => 'id']);
	}
	

	/**
	 * Возвращает сервисы, за которые отвечает пользователь
	 * @return \yii\db\ActiveQuery
	 */
	public function getInfrastructureServices()
	{
		return $this->hasMany(Services::className(), ['infrastructure_user_id' => 'id'])
			->from(['infrastructure_services'=>Services::tableName()]);;
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
    
    public function getName() {
    	return $this->Ename;
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
		return \Yii::$app->ldap->auth()->attempt($this->Login, $password);
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
		$query = static::find()->filterWhere(['Uvolen'=>0])->orderBy(['Ename'=>SORT_ASC,'Login'=>SORT_DESC,'Persg'=>SORT_ASC]);
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
	
	/**
	 * @param $login
	 * @return \yii\db\ActiveRecord|null
	 */
	public static function findByLogin($login){
		//при поиске по логину предпочитаем сначала искать среди трудоустроенных
		return static::find()
			->select(['id','Login','Uvolen'])
			->where(['LOWER(Login)'=>strtolower($login)])
			->orderBy(['Uvolen'=>'ASC','id'=>'DESC'])
			->one();
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
	
	/**
	 * @param $user Users
	 */
	public function absorbUser($user) {
		
		foreach ($user->aces as $ace){
			$ace->users_ids=array_merge(array_diff($ace->users_ids,[$user->id]),[$this->id]);
			$ace->save();
		}
		
		foreach ($user->techs as $tech){
			$tech->user_id=$this->id;
			$tech->save();
		};
		
		foreach ($user->techsResponsible as $tech) {
			$tech->responsible_id=$this->id;
			$tech->save();
		};
		
		foreach ($user->techsHead as $tech) {
			$tech->head_id=$this->id;
			$tech->save();
		};
		
		foreach($user->techsIt as $tech) {
			$tech->it_staff_id=$this->id;
			$tech->save();
		};
		
		foreach ($user->comps as $comp) {
			$comp->user_id=$this->id;
			$comp->save();
		};
		
		foreach ($user->licGroups as $licGroup) {
			$licGroup->users_ids=array_merge(array_diff($licGroup->users_ids,[$user->id]),[$this->id]);
			$licGroup->save();
		}
		
		foreach ($user->licKeys as $licKey) {
			$licKey->users_ids=array_merge(array_diff($licKey->users_ids,[$user->id]),[$this->id]);
			$licKey->save();
		}
		
		foreach ($user->licItems as $licItem) {
			$licItem->users_ids=array_merge(array_diff($licItem->users_ids,[$user->id]),[$this->id]);
			$licItem->save();
		}
		
		foreach ($user->materials as $material) {
			$material->it_staff_id=$this->id;
			$material->save();
		}
		
		foreach ($user->services as $service) {
			$service->responsible_id=$this->id;
			$service->save();
		}
		
		foreach ($user->infrastructureServices as $service) {
			$service->infrastructure_user_id=$this->id;
			$service->save();
		}
		
		foreach ($this->infrastructureServices as $service) {
			$service->support_ids=array_merge(array_diff($service->support_ids,[$user->id]),[$this->id]);
			$service->save();
		}
		
		foreach ($this->infrastructureSupportServices as $service) {
			$service->infrastructure_support_ids=array_merge(array_diff($service->infrastructure_support_ids,[$user->id]),[$this->id]);
			$service->save();
		}

		foreach ($user->contracts as $contract) {
			$contract->users_ids=array_merge(array_diff($contract->users_ids,[$user->id]),[$this->id]);
			$contract->save();
		}

	}
	
	public function afterSave($insert, $changedAttributes)
	{
		parent::afterSave($insert, $changedAttributes);
		
		//если uid нет, или этот уволен, то ничего не проверяем
		if (!$this->uid) return;

		//ищем нет ли таких же пользователей с таким же логином и uid
		$exist=static::find()
			->where(['Login'=>$this->Login])
			->andWhere(['uid'=>$this->uid])
			->andWhere(['not',['id'=>$this->id]])
			->all();
		
		if (is_array($exist) && count($exist)) foreach ($exist as $user) {
			/** @var $user Users */
			//если найденный пользователь уволен, а этот нет
			if (!$this->Uvolen && $user->Uvolen) $this->absorbUser($user);
			$user->Login='';
			$user->save();
		}
	}
	
}
