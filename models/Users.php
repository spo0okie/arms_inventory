<?php

namespace app\models;

use app\helpers\QueryHelper;
use app\models\base\ArmsModel;
use app\models\traits\UsersModelCalcFieldsTrait;
use app\modules\schedules\models\Schedules;
use Exception;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;

/**
 * This is the model class for table "users".
 *
 * @property integer $id Внутренний идентификатор записи
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
 * @property int $manager_id Руководитель (ссылка на запись сотрудника)
 * @property Users $manager Непосредственный руководитель
 * @property string $notepad
 * @property string $password
 * @property string $auth_key Идентификатор Куки для авторизации
 * @property string $authKey Идентификатор Куки для авторизации
 * @property int         $nosync Отключить синхронизацию
 * @property string		$ln Last Name
 * @property string		$fn First Name
 * @property string		$mn Middle Name
 * @property string		$shortName Сокращенные И.О.
 * @property string		$effectivePhone Эффективный номер телефона (явный или через привязанное оборудование)
 * @property string		$uid
 * @property string		$ips
 * @property array		$netIps_ids
 * @property array $lic_groups_ids Массив ID привязанных типов лицензий
 * @property array $lic_items_ids Массив ID привязанных закупок лицензий
 * @property array $lic_keys_ids Массив ID привязанных лицензионных ключей
 * @property Aces[]      $aces
 * @property Comps[]     $comps
 * @property Comps[]     $adminComps
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
	use UsersModelCalcFieldsTrait;

	public static $users=[];
	public static $working_cache=null;
	public static $names_cache=null;
	public static $title="Сотрудник";
	public static $titles="Сотрудники";

	public static function modelDescription(): string
	{
		return 'Сотрудники/пользователи: контактные данные, трудоустройство '
			.'(подразделение, должность, табельный номер) и привязки к АРМ, '
			.'лицензиям, IP адресам и доступам. '
			.'Обычно заполняются синхронизацией с кадровой системой.';
	}
	
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
		3=>['Совместительство внешнее','Внеш.'],
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
			'lic_keys_ids',
			'lic_items_ids',
			'lic_groups_ids',
			'licKeys',
			'licItems',
			'licGroups',
			'netIps',
			'effectivePhone',
		]);
	}
	
	public $linksSchema=[
		'org_id'=>									[Partners::class,'users_ids'],
		'manager_id'=>								Users::class,
		'netIps_ids' =>								[NetIps::class,'users_ids'],
		'aces_ids' => 								[Aces::class,'users_ids'],
		'lic_groups_ids' => 						[LicGroups::class,'users_ids'],
		'lic_keys_ids' => 							[LicKeys::class,'users_ids'],
		'lic_items_ids' =>			 				[LicItems::class,'users_ids'],
		'contracts_ids' => 							[Contracts::class,'users_ids'],
		'comps_ids' =>								[Comps::class,'user_id'],
		'admin_comps_ids' =>						[Comps::class,'admins_ids'],
		'services_ids' => 							[Services::class,'responsible_id'],
		'support_services_ids' =>			 		[Services::class,'support_ids'],
		'infrastructure_services_ids' =>			[Services::class,'infrastructure_user_id'],
		'infrastructure_support_services_ids' =>	[Services::class,'infrastructure_support_ids'],
		'techs_ids' => 								[Techs::class,'user_id'],
		'it_techs_ids' => 							[Techs::class,'it_staff_id','loader'=>'techsIt'],
		'head_techs_ids' =>							[Techs::class,'head_id','loader'=>'techsHead'],
		'responsible_techs_ids' =>					[Techs::class,'responsible_id','loader'=>'techsResponsible'],
		'materials_ids' =>							[Materials::class,'it_staff_id'],
		'logons_ids' =>								[LoginJournal::class,'users_id'],
	];
 
	

	/**
     * @inheritdoc
	 * @noinspection PhpUnusedParameterInspection
     */
    public function rules()
    {
        return [
	        [['Ename', 'Persg', 'Uvolen', ], 'required'],
	        [['Persg', 'Uvolen', 'nosync','org_id','manager_id'], 'integer'],
	        [['manager_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['manager_id' => 'id']],
	        [['employee_id', 'Orgeh', 'Bday', 'employ_date','resign_date'], 'string', 'max' => 16],
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
			'org' => [
				'Организация',
				'hint'=>'Организация (юрлицо), в которой числится сотрудник',
				//read-only вычисляемая ссылка (категория C): только вывод
				'ref'=>\app\models\Partners::class,
			],
			'orgStruct' => [
				'Подразделение',
				'hint'=>'Подразделение оргструктуры, к которому относится сотрудник',
				'ref'=>\app\models\OrgStruct::class,	//read-only вычисляемая ссылка (категория C)
			],
			//search-параметры контроллера (алиасы на реальные атрибуты — тип берётся оттуда)
			'num' => ['alias'=>'employee_id'],
			'login' => ['alias'=>'Login'],
			'uvolen' => ['alias'=>'Uvolen'],
			'arms' => [
				'АРМ',
				'indexHint'=>'Закреплённые за сотрудником АРМ:<br>'
					.'компьютеры из числящегося за ним оборудования',
				'join'=>['techs.model.type','techs.state']
			],
			'acls' => [
				'Доступ к',
				'indexHint'=>'К каким ресурсам пользователю предоставлен доступ',
				//read-only вычисляемая ссылка (категория C): только вывод
				'ref'=>\app\models\Acls::class, 'refMulti'=>true,
				'join'=>[
					'aces.acl.schedule',
					'aces.acl.comp.sandbox',
					'aces.acl.comp.domain',
					'aces.acl.tech',
					'aces.acl.network',
					'aces.acl.service',
					'aces.acl.ip',
				],
			],
			'Bday' => ['День рождения','typeClass'=>\app\types\DateType::class],
			'Doljnost' => [
				'Должность',
				'hint'=>'Должность сотрудника.<br>Обычно заполняется синхронизацией с кадровой системой',
				'typeClass'=>\app\types\StringType::class,
			],
			'Email' => [
				'E-Mail',
				'hint'=>'Адрес электронной почты сотрудника',
				'absorb'=>'ifEmpty','typeClass'=>\app\types\EmailType::class,
			],
			'employee_id' => [
				'Таб. №',
				'hint'=>'Табельный номер сотрудника<br>(конкретно этого его трудоустройства)',
				'typeClass'=>\app\types\StringType::class,
			],
			'employ_date' => ['Дата приёма','hint'=>'Дата приёма сотрудника на работу'],
			'resign_date' => ['Дата увольнения','hint'=>'Дата увольнения сотрудника'],
			'id' => ['id','hint'=>'Внутренний идентификатор записи'],
			'netIps_ids' => ['alias'=>'ips'],
			'Ename' => [
				'Полное имя',
				'hint'=>'Полное имя (ФИО) сотрудника',
				'typeClass'=>\app\types\StringType::class,
			],
			'ips' => [
				'Привязанные IP адреса',
				'hint' => 'IP адреса, закреплённые за пользователем',
				'indexLabel' => 'IPs',
				'indexHint' => 'Привязанные к пользователю IP адреса',
				'absorb'=>'ifEmpty',
				'join'=>['netIps.network.segment']
			],
			'lastThreeLogins' => [
				'Входы',
				'viewLabel'=>'Входы в комп',
				'hint'=>'Журнал входов пользователей на компьютеры, заполняемый скриптами инвентаризации.<br>'
					.'Показываются последние входы этого сотрудника на 3 разных компьютера',
			],
			'lics'=>[
				'Лицензии',
				'indexHint'=>'Назначенные пользователю лицензии',
				//read-only вычисляемая ссылка (категория C): гетерогенный список
				//лицензий (LicGroups/LicItems/LicKeys), потому базовый класс
				'ref'=>\app\models\base\ArmsModel::class, 'refMulti'=>true,
				'join'=>['licItems','licGroups','licKeys'],
			],
			//read-only вычисляемые ссылки (категория C): только вывод
			'compsFromTechs' => [
				'Привязанные ОС',
				'hint'=>'Операционные системы, обнаруженные на числящемся за сотрудником оборудовании',
				'ref'=>\app\models\Comps::class, 'refMulti'=>true,
			],
			'comps' => [
				'Ответственный за ОС',
				'hint'=>'ОС/ВМ, в которых этот сотрудник явно указан пользователем '
					.'(имеет смысл для серверов и ВМ, где пользователь ОС отличается от пользователя АРМ)',
				'ref'=>\app\models\Comps::class, 'refMulti'=>true,
			],
			'compsTotal' => [
				'Ответственный за ОС',
				'hint'=>'ОС/ВМ в ответственности сотрудника: где он явно указан пользователем ОС, '
					.'а поскольку сотрудник отвечает за сервисы — также ОС этих сервисов, '
					.'по которым он определяется ответственным',
				'ref'=>\app\models\Comps::class, 'refMulti'=>true,
			],
			'adminComps' => [
				'Полномочия администратора',
				'hint'=>'ОС/ВМ, на которых этому сотруднику (рядовому пользователю) '
					.'выданы полномочия администратора',
				'ref'=>\app\models\Comps::class, 'refMulti'=>true,
			],
			'services' => [
				'Ответственный за сервисы',
				'hint'=>'Сервисы, в которых сотрудник назначен ответственным за работу сервиса '
					.'/ оказание услуги',
				'ref'=>\app\models\Services::class, 'refMulti'=>true,
			],
			'infrastructureServices' => [
				'Ответственный за инфраструктуру',
				'hint'=>'Сервисы, в которых сотрудник назначен ответственным за инфраструктуру '
					.'(когда ответственность за сервис и за его инфраструктуру разделена)',
				'ref'=>\app\models\Services::class, 'refMulti'=>true,
			],
			'techsHead' => [
				'Техника подчинённых',
				'hint'=>'АРМ/оборудование, в паспорте которого сотрудник указан руководителем отдела пользователя',
				'ref'=>\app\models\Techs::class, 'refMulti'=>true,
			],
			'techsIt' => [
				'Обслуживаемая техника',
				'hint'=>'АРМ/оборудование, которое сотрудник (как сотрудник службы ИТ) '
					.'обслуживает на месте установки',
				'ref'=>\app\models\Techs::class, 'refMulti'=>true,
			],
			'techsResponsible' => [
				'Техника в адм. ответственности',
				'hint'=>'АРМ/оборудование, за которое сотрудник несёт административную ответственность:'
					.'<br>ему переданы административные полномочия, и он отвечает '
					.'за действия этого АРМ/оборудования',
				'ref'=>\app\models\Techs::class, 'refMulti'=>true,
			],
			'contracts' => [
				'Документы',
				'hint'=>'Привязанные к сотруднику документы (договоры, акты и т.п.)',
				'ref'=>\app\models\Contracts::class, 'refMulti'=>true,
			],
			//read-only вычисляемое поле (категория C): только вывод
			'effectivePhone' => [
				'Эффективный тел.',
				'hint'=>'Номер телефона: сначала ищутся VoIP-номера привязанного оборудования '
					.'(может быть несколько — через запятую с пробелом), если таких нет — '
					.'используется внутренний номер (Phone)',
				'typeClass'=>\app\types\StringType::class,
			],
			'Login' => [
				'Логин (AD)',
				'hint'=>'Учётная запись пользователя в домене/AD',
				'typeClass'=>\app\types\StringType::class,
			],
			'logon_ids' => ['absorb'=>false], //вручную переключим
			'manager_id' => [
				'Руководитель',
				'hint'=>'Непосредственный руководитель — ссылка на запись сотрудника.<br>'
					.'Может заполняться вручную или синхронизацией с кадровой системой',
				'placeholder'=>'Руководитель не указан',
				'typeClass'=>\app\types\LinkType::class,
			],
			'Mobile' => [
				'Мобильный тел',
				'hint'=>'Мобильный телефон сотрудника',
				'indexHint'=>'{same}<br>В списке выводятся вместе рабочий мобильный и личный телефоны '
					.'(через запятую); поиск ведётся по обоим',
				'absorb'=>'ifEmpty','typeClass'=>\app\types\StringType::class,
			],
			'netIps' => ['alias'=>'ips'],
			'nosync' => [
				'Отключить синхронизацию',
				'hint'=>'Запрет внешнему скрипту синхронизации с кадровой БД обновлять эту запись<br>'.
					'<i>(Должно быть реализовано во внешнем скрипте)</i>',
				'typeClass'=>\app\types\BooleanType::class,
			],
			'notepad' => [
				'Записная книжка',
				'hint' => 'Все важные и не очень заметки и примечания по жизненному циклу этого объекта',
				'absorb'=>'ifEmpty',
				'typeClass'=>\app\types\TextType::class,
			],
			'org_id' => [
				'Организация',
				'Контрагент в котором числится этот сотрудник/пользователь',
				'placeholder' => 'Организация',
				'join'=>['org'],
				'typeClass'=>\app\types\LinkType::class,
			],
			'org_name'=>['alias'=>'org_id'],
			'Orgeh' => [
				'Подразделение',
				'hint'=>'Код подразделения в кадровой системе;<br>связывает сотрудника с оргструктурой',
				'join'=>['orgStruct'],
				'typeClass'=>\app\types\StringType::class,
			],
			'orgStruct_name' => ['alias'=>'Orgeh'],
			'Persg' => [
				'Тип трудоустройства',
				'hint'=>'Код типа трудоустройства из кадровой системы',
				'typeClass'=>\app\types\IntegerType::class,
			],
			'Phone' => [
				'Внутренний тел',
				'hint'=>'Внутренний телефонный номер сотрудника',
				'absorb'=>'ifEmpty',
				'join'=>['techs.model.type','techs.state'],
				'typeClass'=>\app\types\StringType::class,
			],
			'private_phone' => [
				'Личный тел',
				'hint'=>'Личный телефон сотрудника',
				'absorb'=>'ifEmpty','typeClass'=>\app\types\StringType::class,
			],
			'scheduledAccess'=> [
				'Вр. доcтупы',
				'indexHint' => 'Предоставленные пользователю временные доступы',
				//read-only вычисляемая ссылка (категория C): только вывод
				'ref'=>\app\modules\schedules\models\Schedules::class, 'refMulti'=>true,
				'join' => 'scheduledAccess'
			],
			'shortName' => [
				'Короткое имя',
				'indexHint'=>'Отображаться будет "Фамилия И.О.",<br>'.
					'поиск будет вестись по полному имени',
				'typeClass'=>\app\types\StringType::class,
			],
			'techs' => [
				'Оборудование',
				'indexHint'=>'Всё АРМ/оборудование, числящееся за сотрудником (он указан пользователем)',
				'join'=>['techs.model.type','techs.state']
			],
			'uid' => ['Идентификатор','hint'=>'Уникальный идентификатор человека.<br>ИНН / СНИЛС / MD5(ИНН) и т.п.','typeClass'=>\app\types\StringType::class],
			'Uvolen' => [
				'Уволен',
				'hint'=>'Признак того, что сотрудник уволен (запись остаётся для истории)',
				'typeClass'=>\app\types\BooleanType::class,
			],
			'work_phone' => [
				'Городской рабочий тел',
				'hint'=>'Городской рабочий телефон сотрудника',
				'absorb'=>'ifEmpty','typeClass'=>\app\types\StringType::class,
			],

			'access_token'=>['absorb'=>'ifEmpty','typeClass'=>\app\types\StringType::class],
			'auth_key'=>[
				'hint'=>'Служебный ключ авторизации (заполняется автоматически)',
				'absorb'=>'ifEmpty','typeClass'=>\app\types\StringType::class,
			],
			'password'=>['absorb'=>'ifEmpty','typeClass'=>\app\types\StringType::class],
		]);
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
	
	public function getAcls()
	{
		return $this->hasMany(Acls::class, ['id'=>'acls_id'])->from(['users_acls'=>Acls::tableName()])
			->via('aces');
	}
	
	public function getScheduledAccess()
	{
		return $this->hasMany(Schedules::class, ['id'=>'schedules_id'])->from(['users_scheduled_access'=>Schedules::tableName()])
			->via('acls');
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
	
	public function getLics() {
		return array_merge($this->licGroups,$this->licItems,$this->licKeys);
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
	 * Сканы (фото/изображения), прикреплённые к сотруднику
	 * @return ActiveQuery
	 */
	public function getScans()
	{
		return $this->hasMany(Scans::class, ['users_id'=>'id']);
	}

	/**
	 * Фотографии сотрудника — сканы-изображения, отсортированные от новых к старым
	 * (последнее по дате — первым, аналогия с аватаром в мессенджерах). PDF и прочие
	 * не-изображения сюда не попадают.
	 * @return Scans[]
	 */
	public function getPhotos()
	{
		if (isset($this->attrsCache['photos'])) return $this->attrsCache['photos'];
		$photos=[];
		foreach ($this->scans as $scan) if ($scan->isImage) $photos[]=$scan;
		usort($photos, static fn($a,$b) => $b->fileDate <=> $a->fileDate);
		return $this->attrsCache['photos']=$photos;
	}

	/**
	 * Портрет сотрудника — последнее по дате изображение (или null, если фото нет).
	 * Именно его SAPsync выгружает в PERSONAL_PHOTO Bitrix.
	 * @return Scans|null
	 */
	public function getPhoto()
	{
		return $this->photos[0] ?? null;
	}

	/**
	 * Непосредственный руководитель
	 * @return ActiveQuery
	 */
	public function getManager()
	{
		return $this->hasOne(Users::class, ['id'=>'manager_id'])
			->from(['managers'=>Users::tableName()]);
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
	 * Возвращает сервисы, за которые отвечает пользователь
	 * @return ActiveQuery
	 */
	public function getAdminComps()
	{
		return $this->hasMany(Comps::class, ['id' => 'comps_id'])
			->viaTable('{{%admins_in_comps}}', ['users_id' => 'id']);
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
	
	public function setPassword(?string $password): void
	{
		$this->password = $password ? password_hash($password, PASSWORD_BCRYPT) : null;
	}

    /**
     * Validates password.
     * If the user has a local password hash — verifies against it.
     * Otherwise falls back to LDAP.
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword(string $password): bool
    {
		if (!empty($this->password)) {
			return password_verify($password, $this->password);
		}

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
	
	public function getLogons() {
		return $this->hasMany(LoginJournal::class,['users_id'=>'id']);
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
		
		$user->Login='';
		$this->absorbModel($user);
		
		//журнал огромный и по одной записи менять это гемор
		LoginJournal::updateAll(['users_id'=>$this->id],['users_id'=>$user->id]);

		//Если мы используем RBAC модель доступа, то переназначаем все роли новому логину
		if (Yii::$app->params['useRBAC']) {
			foreach (Yii::$app->authManager->getRolesByUser($user->id) as $role) {
				Yii::$app->authManager->assign($role,$this->id);
			}
			Yii::$app->authManager->revokeAll($user->id);
		}
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
	
	public function getIsArchived() {
		return $this->Uvolen;
	}
	
}
