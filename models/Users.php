<?php

namespace app\models;

use app\helpers\QueryHelper;
use Exception;
use voskobovich\linker\LinkerBehavior;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "users".
 *
 * @property integer $id Табельный номер
 * @property string $employee_id Табельный номер
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
 * @property string $notepad
 * @property string $password
 * @property string $auth_key Идентификатор Куки для авторизации
 * @property string $authKey Идентификатор Куки для авторизации
 * @property int         $nosync Отключить синхронизацию
 * @property string		$ln Last Name
 * @property string		$fn First Name
 * @property string		$mn Middle Name
 * @property string		$shortName Сокращенные И.О.
 * @property string		$uid
 * @property string		$ips
 * @property array		$netIps_ids
 *
 * @property Aces[]      $aces
 * @property Comps[]     $comps
 * @property Comps[]     $compsFromServices
 * @property Comps[]     $compsFromTechs
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
 * @property LoginJournal[]  $lastThreeLogins
 * @property LicGroups[] $licGroups
 * @property LicItems[]  $licItems
 * @property LicKeys[]   $licKeys
 * @property Partners    $org
 * @property OrgStruct   $orgStruct
 * @property NetIps   	 $netIps
 */
class Users extends ArmsModel implements IdentityInterface
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
	
	public function fields()
	{
		$fields = parent::fields();
		
		// remove fields that contain sensitive information
		unset($fields['auth_key'], $fields['password'], $fields['access_token']);
		
		return $fields;
	}

	public function extraFields()
	{
		return array_merge(parent::extraFields(),[
			'fn',
			'mn',
			'ln',
			'orgStruct',
			'org',
			'licKeys_ids',
			'licItems_ids',
			'licGroups_ids',
			'licKeys',
			'licItems',
			'licGroups',
			'netIps'
		]);
	}
	
	
	/**
	 * В списке поведений прикручиваем many-to-many контрагентов
	 * @return array
	 */
	public function behaviors()
	{
		return [
			[
				'class' => LinkerBehavior::class,
				'relations' => [
					'netIps_ids' => 'netIps',
					'aces_ids' => 'aces',
					'contracts_ids' => 'contracts',
					'support_services_ids' => 'supportServices',
					'infrastructure_support_services_ids' => 'infrastructureSupportServices',
					'services_ids' => 'services',								//one-2-many
					'techs_ids' => 'techs',										//one-2-many
					'it_techs_ids' => 'techsIt',								//one-2-many
					'head_techs_ids' => 'techsHead',							//one-2-many
					'responsible_techs_ids' => 'techsResponsible',				//one-2-many
					'infrastructure_services_ids' => 'infrastructureServices',	//one-2-many
					'materials_ids' => 'materials',								//one-2-many
				]
			]
		];
	}
	

	/**
     * @inheritdoc
	 * @noinspection PhpUnusedParameterInspection
     */
    public function rules()
    {
        return [
	        [['Ename', 'Persg', 'Uvolen', ], 'required'],
	        [['Persg', 'Uvolen', 'nosync','org_id'], 'integer'],
	        [['employee_id', 'Orgeh', 'Bday', 'manager_id'], 'string', 'max' => 16],
	        [['Doljnost', 'Ename', 'Mobile','private_phone','ips','auth_key'], 'string', 'max' => 255],
			[['notepad'],'safe'],
	        [['id'], 'unique'],
	        [['Email','uid'], 'string', 'max' => 64],
	        [['Login', 'Phone', 'work_phone'], 'string', 'max' => 32],
			['Login', function ($attribute, $params, $validator) {
				$exist=static::find()->where(['Login'=>$this->Login])->andWhere(['not',['id'=>$this->id]])->one();
				/** @var $exist Users */
				if (is_object($exist)) {
					//если этот логин у того же самого человека (совпадает uid=>ИНН) то и пофиг
					if (strlen($this->uid) && $this->uid==$exist->uid) return;
					//если ФИО совпадает и такое совпадение в параметрах выставлено достаточным то тоже пофиг
					if (strlen($this->Ename) && $this->Ename==$exist->Ename && Yii::$app->params['user.name_as_uid.enable']) return;
					$this->addError($attribute, 'Такой логин уже занят пользователем '.$exist->Ename);
				}
			}],
			[['netIps_ids'], 'each', 'rule'=>['integer']],
		];
    }

    /**
     * @inheritdoc
     */
	public function attributeData()
	{
		return array_merge(parent::attributeData(),[
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
			'uid' => ['Идентификатор','hint'=>'Уникальный идентификатор человека.<br>ИНН / СНИЛС / MD5(ИНН) и т.п.'],
			'ips' => 'Привязанные IP адреса',
			'LastThreeLogins' => 'Входы',
		]);
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
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getAces()
	{
		return $this->hasMany(Aces::class, ['id' => 'aces_id'])->from(['users_aces'=>Aces::tableName()])
			->viaTable('{{%users_in_aces}}', ['users_id' => 'id']);
	}
	

	/**
     * @return ActiveQuery
     */
    public function getTechsResponsible()
    {
        return $this->hasMany(Techs::class, ['responsible_id' => 'id']);
    }

    /**
     * @return ActiveQuery
     */
    public function getTechsHead()
    {
        return $this->hasMany(Techs::class, ['head_id' => 'id']);
    }
	
	
	
	/**
	 * @return ActiveQuery
	 */
	public function getTechs()
	{
		return $this->hasMany(Techs::class, ['user_id' => 'id']);
	}
	
	
	/**
	 * @return ActiveQuery
	 */
	public function getTechsIt()
	{
		return $this->hasMany(Techs::class, ['it_staff_id' => 'id']);
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
		return $this->hasMany(LicGroups::class, ['id' => 'lic_groups_id'])
			->viaTable('{{%lic_groups_in_users}}', ['users_id' => 'id']);
	}
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getLicItems()
	{
		return $this->hasMany(LicItems::class, ['id' => 'lic_items_id'])
			->viaTable('{{%lic_items_in_users}}', ['users_id' => 'id']);
	}
	
	/**
	 * Возвращает закрепленное на компе ПО
	 */
	public function getLicKeys()
	{
		return $this->hasMany(LicKeys::class, ['id' => 'lic_keys_id'])
			->viaTable('{{%lic_keys_in_users}}', ['users_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getMaterials()
	{
		return $this->hasMany(Materials::class, ['it_staff_id' => 'id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getOrgStruct()
	{
		return $this->hasOne(OrgStruct::class, ['hr_id'=>'Orgeh','org_id'=>'org_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getOrg()
	{
		return $this->hasOne(Partners::class, ['id'=>'org_id']);
	}
	
	/**
	 * Возвращает IP адреса
	 */
	public function getNetIps()
	{
		return $this->hasMany(NetIps::class, ['id' => 'ips_id'])->from(['users_ip'=>NetIps::tableName()])
			->viaTable('{{%ips_in_users}}', ['users_id' => 'id']);
	}
	
	
	/**
	 * Возвращает сервисы, за которые отвечает пользователь
	 * @return ActiveQuery
	 */
	public function getServices()
	{
		return $this->hasMany(Services::class, ['responsible_id' => 'id']);
	}
	
	/**
	 * Возвращает сервисы, за которые отвечает пользователь
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getSupportServices()
	{
		return $this->hasMany(Services::class, ['id' => 'service_id'])
			->from(['support_services'=>Services::tableName()])
			->viaTable('{{%users_in_services}}', ['user_id' => 'id']);
	}
	
	/**
	 * Возвращает сервисы, за которые отвечает пользователь
	 * @return ActiveQuery
	 */
	public function getInfrastructureServices()
	{
		return $this->hasMany(Services::class, ['infrastructure_user_id'=>'id'])
			->from(['infrastructure_services'=>Services::tableName()]);
	}
	
	/**
	 * Возвращает сервисы, за которые отвечает пользователь
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getInfrastructureSupportServices()
	{
		return $this->hasMany(Services::class, ['id' => 'service_id'])
			->from(['support_infrastructure_services'=>Services::tableName()])
			->viaTable('{{%users_in_svc_infrastructure}}', ['users_id' => 'id']);
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
				if (is_object($comp->responsible) && $comp->responsible->id == $this->id)
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
	
	public function getCompsFromTechs()
	{
		$result=[];
		foreach ($this->techs as $tech)
			foreach ($tech->comps as $comp)
				$result[$comp->id]=$comp;
		return $result;
	}
	
	
	/**
	 * Возвращает сервисы, за которые отвечает пользователь
	 * @return ActiveQuery
	 */
	public function getComps()
	{
		return $this->hasMany(Comps::class, ['user_id' => 'id']);
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
        return $authKey===$this->authKey;
    }
	
	public function setPassword($password)
	{
		return $this->password=password_hash($password,PASSWORD_BCRYPT);
	}

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword(string $password)
    {
    	//local backend
    	if (Yii::$app->params['localAuth']??false) {//Local DB Backend
			return password_verify($password,$this->password);
		}
    	
    	//LDAP backend
		if (isset(Yii::$app->ldap)) {
			return Yii::$app->ldap->auth()->attempt($this->Login, $password);
		}
		
		return false;
    }

    /**
     * Возвращает массив ключ=>значение запрошенных/всех записей таблицы
     * @param array|null $items список элементов для вывода
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

        return ArrayHelper::map($query->all(), $keyField, $valueField);
    }
	
	/**
	 * Возвращает список не уволенных сотрудников
	 * @param null $current если передан, то возвращает еще этого, независимо от состояния уволен или нет
	 * @return array|null
	 */
	public static function fetchWorking($current=null)
	{
		if (!is_null(static::$working_cache)) return static::$working_cache;
		$query = static::find()->filterWhere(['Uvolen'=>0])->orderBy(['Ename'=>SORT_ASC,'Login'=>SORT_DESC,'Persg'=>SORT_ASC]);
		$list= (static::$working_cache = ArrayHelper::map($query->all(), 'id', 'Ename'));
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
	 * Finds user by login
	 * @param $login
	 * @return ActiveRecord|static|null
	 */
	public static function findByLogin(string $login){
		//при поиске по логину предпочитаем сначала искать среди трудоустроенных
		return static::find()
			->where(['LOWER(Login)'=>strtolower($login)])
			->orderBy(['Uvolen'=>'ASC','id'=>'DESC'])
			->one();
	}
	
	/**
	 * Finds user by Name
	 * @param string $name
	 * @return ActiveRecord|static|null
	 */
	public static function findByName(string $name)	{
		return static::find()
			->where(['LOWER(Ename)'=>strtolower($name)])
			->orderBy(['Uvolen'=>'ASC','id'=>'DESC'])
			->one();
	}
	
	/**
	 * Универсальная процедура поиска объекта по имени
	 * @param string $name
	 * @return Users|ActiveRecord|null
	 */
	public static function findByAnyName(string $name) {
		if (is_object($model=static::findByLogin($name))) return $model;
		if (is_object($model=static::findByName($name))) return $model;
		return null;
	}

	public function getLastLogin() {
		return LoginJournal::find()
			->where(['users_id'=>$this->id])
			->andWhere(['!=','comps_id','NULL'])
			->orderBy('id desc')->one();
	}

	public function getLastThreeLogins() {
		return LoginJournal::fetchUniqComps($this->id);
	}

	public function getLastLoginComp() {
		return LoginJournal::find()
			->where(['users_id'=>$this->id,'!comp_id'=>null])
			->orderBy('id desc')
			->one();

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
	 * @throws Exception
	 */
	public function absorbUser(Users $user) {
		
		foreach ($user->aces as $ace){
			$ace->users_ids=array_merge(array_diff($ace->users_ids,[$user->id]),[$this->id]);
			$ace->save();
		}
		
		foreach ($user->techs as $tech){
			$tech->user_id=$this->id;
			$tech->save();
		}
		
		foreach ($user->techsResponsible as $tech) {
			$tech->responsible_id=$this->id;
			$tech->save();
		}
		
		foreach ($user->techsHead as $tech) {
			$tech->head_id=$this->id;
			$tech->save();
		}
		
		foreach($user->techsIt as $tech) {
			$tech->it_staff_id=$this->id;
			$tech->save();
		}
		
		foreach ($user->comps as $comp) {
			$comp->user_id=$this->id;
			$comp->save();
		}
		
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
		
		$stringAttributes=['Phone','Email','work_phone','Mobile','private_phone','ips','notepad','password','auth_key','access_token'];
		foreach ($stringAttributes as $attr) //забираем себе те поля, которые тут не проставлены
			if (!$this->$attr || $this->$attr==$user->$attr) {
				$this->$attr=$user->$attr;	$user->$attr='';
			}
		$user->Login='';

		//Если мы используем RBAC модель доступа, то переназначаем все роли новому логину
		if (Yii::$app->params['useRBAC']) {
			foreach (Yii::$app->authManager->getRolesByUser($user->id) as $role) {
				Yii::$app->authManager->assign($role,$this->id);
			}
			Yii::$app->authManager->revokeAll($user->id);
		}

		
		$user->save();
		$this->save();
		
	}
	
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			/* взаимодействие с NetIPs */
			$this->netIps_ids=NetIps::fetchIpIds($this->ips);
			return true;
		}
		return false;
	}
	
	public function afterSave($insert, $changedAttributes)
	{
		parent::afterSave($insert, $changedAttributes);
		
		//если uid нет, или этот уволен, то ничего не проверяем
		if (!$this->uid) return;
		
		//если этот уволен то тоже ничего не проверяем
		if ($this->Uvolen) return;

		//в зависимости от параметра использования ФИО ориентируемся только на ИНН или еще на ФИО
		$uidFilter=Yii::$app->params['user.name_as_uid.enable']?
		['or',['uid'=>$this->uid],['Ename'=>$this->Ename]]:
		['uid'=>$this->uid];

		//ищем нет ли таких же пользователей с таким же логином и uid
		$exist=static::find()
			->where(['Login'=>$this->Login])
			->andWhere($uidFilter)
			->andWhere(['not',['id'=>$this->id]])
			->all();
		
		if (is_array($exist) && count($exist)) foreach ($exist as $user) {
			/** @var $user Users */
			//если найденный пользователь уволен, а этот нет
			$this->absorbUser($user);
		}
	}
	
}
