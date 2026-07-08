<?php

namespace app\models;



use app\models\base\ArmsModel;
use app\models\traits\AclsModelCalcFieldsTrait;
use app\modules\schedules\models\Schedules;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "acls".
 *
 * @property int $id
 * @property int $schedules_id
 * @property int $services_id
 * @property int $networks_id
 * @property int $ips_id
 * @property int $comps_id
 * @property int $techs_id
 * @property string $comment
 * @property string $notepad
 * @property string sname
 *
 * @property Schedules	$schedule
 * @property Comps		$comp
 * @property Techs		$tech
 * @property NetIps		$ip
 * @property Networks	$network
 * @property Services	$service
 * @property Aces[]		$aces
 * @property AccessTypes[] $accessTypes
 * @property Partners[] $partners
 * @property Segments[]	$segments
 */
class Acls extends ArmsModel
{

	use AclsModelCalcFieldsTrait;

	public static $title='Список доступа';
	public static $titles='Списки доступа';

	//TODO-REVIEW: описание сгенерировано по коду
	public static function modelDescription(): string
	{
		return 'Списки доступа (ACL): наборы записей доступа к одному ресурсу (сервису, ОС, IP, сети); могут действовать по расписанию.';
	}

	/*
	 * Если к расписанию прикрутить ACL - то нужно на него смотреть несколько иначе, это не просто расписание
	 * а расписание предоставления доступа. В таком случае интерфейс немного надо скорректировать
	 */
	public static $scheduleTitles = 'Временные доступы';
	public static $scheduleTitle = 'Временный доступ';
	public static $scheduleNameHint = 'Какое-то название для этого доступа. Например "предоставление доступа ООО Рога и копыта к кластеру VMWare в Нюрнберге"';
	public static $scheduleHistoryHint = 'Подробная информация о предоставлении временного доступа.';


	public static $emptyComment='Заполни меня';

	/**
	 * Сценарий группового создания/редактирования: ресурсы задаются мультиселектами
	 * (массивы *_ids), «хотя бы один ресурс» проверяется по ним, а не по одиночным *_id.
	 */
	const SCENARIO_GROUP='group';

	/**
	 * Виртуальные (не колоночные) массивы ресурсов для группового сценария.
	 * Каждый разворачивается контроллером в отдельные одиночные ACL (по одному ресурсу на ACL).
	 * Безопасны/валидируются ТОЛЬКО в SCENARIO_GROUP, поэтому не участвуют в генерации и REST.
	 * @var int[]
	 */
	public $comps_ids;
	public $techs_ids;
	public $ips_ids;
	public $networks_ids;
	public $services_ids;

	public $linksSchema=[
		'aces_ids'=>[Aces::class,'acls_id'],
		'schedules_id'=>[Schedules::class,'acls_ids'],
		'services_id'=>[Services::class,'acls_ids'],
		'networks_id'=>[Networks::class,'acls_ids'],
		'ips_id'=>[NetIps::class,'acls_ids'],
		'comps_id'=>[Comps::class,'acls_ids'],
		'techs_id'=>[Techs::class,'acls_ids'],
	];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'acls';
    }

	public function extraFields()
	{
		return array_merge(parent::extraFields(),[
			'schedule',
			'aces'
		]);
	}

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['notepad'], 'string'],
            [['comment'], 'string', 'max' => 255],
			//одиночный сценарий (обычный ACL): ровно один ресурс из одиночных полей либо comment
			[['schedules_id', 'services_id', 'ips_id', 'comps_id', 'techs_id','networks_id'], 'integer'],
			[['services_id', 'ips_id', 'comps_id', 'techs_id','networks_id','comment'],
				'validateRequireOneOf',
				'skipOnEmpty' => false,
				'params'=>['attrs'=>['services_id', 'ips_id', 'comps_id', 'techs_id','networks_id','comment']],
				'except' => self::SCENARIO_GROUP,
			],
			//групповой сценарий: ресурсы — массивы *_ids (мультиселект), хотя бы один из них либо comment
			[['comps_ids','techs_ids','ips_ids','networks_ids','services_ids'], 'each', 'rule'=>['integer'], 'on'=>self::SCENARIO_GROUP],
			[['comps_ids','techs_ids','ips_ids','networks_ids','services_ids','comment'],
				'validateRequireOneOf',
				'skipOnEmpty' => false,
				'params'=>['attrs'=>['comps_ids','techs_ids','ips_ids','networks_ids','services_ids','comment']],
				'on' => self::SCENARIO_GROUP,
			],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return array_merge(parent::attributeData(),[
			'aces' => ['ACEs','indexHint'=>'Access Control Entries <br> (Записи кому предоставляется какой доступ)','typeClass'=>\app\types\LinkType::class],
			//read-only вычисляемые ссылки (категория C): гетерогенные значения
			//(Comps/Techs/Services/строка-комментарий), потому базовый класс
			'nodes' => ['ref'=>\app\models\base\ArmsModel::class, 'refMulti'=>true],
			'resource' => ['ref'=>\app\models\base\ArmsModel::class],
			'accessTypes' => ['ref'=>\app\models\AccessTypes::class, 'refMulti'=>true],
			//групповые массивы ресурсов (только для формы группового создания/редактирования)
			'comps_ids' => ['alias'=>'comps_id'],
			'techs_ids' => ['alias'=>'techs_id'],
			'ips_ids' => ['alias'=>'ips_id'],
			'networks_ids' => ['alias'=>'networks_id'],
			'services_ids' => ['alias'=>'services_id'],
			'comment' => ['Описание','Описание ресурса к которому предоставляется доступ (просто текст без привязки к объекту БД)','typeClass'=>\app\types\TextType::class],
			'comps_id' => [
				'ОС',
				'Имя компьютера (Операционной Системы) к которому предоставляется доступ',
				'placeholder'=>'Выберите ОС',
				'typeClass'=>\app\types\LinkType::class,
			],
			'id' => ['ID','typeClass'=>\app\types\IntegerType::class],
			'schedules_id' => [
				'Расписание доступа',
				'hint'=>'Расписание, определяющее временные рамки предоставления доступа',
				'apiHint'=>'Объект типа Schedule, определяющий временные рамки предоставления доступа',
				'typeClass'=>\app\types\LinkType::class,
			],
            'services_id' => [
				'Сервис',
				'К какому сервису нужно предоставить доступ (включая дочерние сервисы)',
				'placeholder'=>'Выберите сервис',
				'viewHint'=>'Сервис к которому предоставляется доступ (включая дочерние сервисы)',
				'typeClass'=>\app\types\LinkType::class,
			],
			'ips_id' => [
				'IP адрес',
				'IP адрес к которому предоставляется доступ',
				'placeholder'=>'Выберите IP адрес',
				'typeClass'=>\app\types\LinkType::class,
			],
			'networks_id' => [
				'IP сеть',
				'IP сеть к которой предоставляется доступ',
				'placeholder'=>'Выберите IP сеть',
				'typeClass'=>\app\types\LinkType::class,
			],
            'techs_id' => [
				'Оборудование',
				'Оборудование к которому предоставляется доступ',
				'placeholder'=>'Выберите оборудование',
				'typeClass'=>\app\types\LinkType::class,
			],
            'notepad' => [
            	'Записная книжка',
				//TODO-REVIEW: подсказка сгенерирована по коду
				'hint'=>'Заметки по этому списку доступа',
             	'typeClass'=>\app\types\TextType::class
            ],
        ]);
    }

	public function getSchedule() {
		return $this->hasOne(Schedules::class, ['id' => 'schedules_id']);
	}

	public function getService() {
		return $this->hasOne(Services::class, ['id' => 'services_id'])
			->from(['services_resources'=>Services::tableName()]);
	}
	public function getComp() {
		return $this->hasOne(Comps::class, ['id' => 'comps_id'])
			->from(['comps_resources'=>Comps::tableName()]);
	}

	public function getTech() {
		return $this->hasOne(Techs::class, ['id' => 'techs_id'])
			->from(['techs_resources'=>Techs::tableName()]);
	}

	public function getIp() {
		return $this->hasOne(NetIps::class, ['id' => 'ips_id'])
			->from(['ips_resources'=>NetIps::tableName()]);
	}

	public function getNetwork() {
		return $this->hasOne(Networks::class, ['id' => 'networks_id'])
			->from(['networks_resources'=>Networks::tableName()]);
	}

	public function getAces() {
		return $this->hasMany(Aces::class, ['acls_id' => 'id']);
	}

	public function beforeDelete()
	{
		if (count($this->aces))
			foreach ($this->aces as $ace) {
				$ace->delete();
			}
		return parent::beforeDelete();
	}

	/**
	 * Канонический отпечаток набора ACE этого ACL.
	 *
	 * Используется для определения «группы ACL» (несколько ACL одного расписания доступа
	 * с одинаковым набором ACE, отличающихся ресурсами). Порядок ACE не важен.
	 * См. plans/group-acls.md, итерация 2.
	 *
	 * @return string
	 */
	public function acesSignature(): string
	{
		$sigs=[];
		foreach ($this->aces as $ace) $sigs[]=$ace->aceSignature();
		sort($sigs);
		return md5(implode('|',$sigs));
	}

	/**
	 * Полная сигнатура, включая собственные атрибуты
	 */
	public function signature(): string
	{
		return md5($this->notepad??'').$this->acesSignature();
	}

	/**
	 * Находит в этом ACL запись доступа (ACE) с заданной сигнатурой («ACE-близнец»).
	 *
	 * @param string $signature результат {@see Aces::aceSignature()}
	 * @return Aces|null
	 */
	public function findAceBySignature(string $signature): ?Aces
	{
		foreach ($this->aces as $ace) {
			if ($ace->aceSignature()===$signature) return $ace;
		}
		return null;
	}

	/**
	 * Возвращает все ACL «группы» этого ACL: ACL того же расписания доступа
	 * с таким же набором ACE (включая сам этот ACL).
	 *
	 * ACL без расписания доступа не группируются (возвращается только сам ACL),
	 * чтобы не объединять глобально все ACL без schedule.
	 *
	 * @return Acls[]
	 */
	public function groupMembers(): array
	{
		if (!$this->schedules_id) return [$this];
		$sig=$this->signature();
		$members=[];
		foreach (static::find()->where(['schedules_id'=>$this->schedules_id])->all() as $acl) {
			if ($acl->signature()===$sig) $members[]=$acl;
		}
		return $members;
	}

	/**
	 * Группирует переданные ACL по одинаковому набору ACE.
	 *
	 * Группировка идёт ТОЛЬКО по набору ACE — расписание доступа должно ограничиваться
	 * вызывающим кодом (передавать ACL одного расписания). Порядок ACL внутри группы
	 * сохраняется.
	 *
	 * @param Acls[] $acls
	 * @return Acls[][] список групп; каждая группа — массив ACL с одинаковым набором ACE
	 */
	public static function groupBySignatures(array $acls): array
	{
		$groups=[];
		foreach ($acls as $acl) {
			$groups[$acl->signature()][]=$acl;
		}
		return array_values($groups);
	}

	/**
	 * Возвращает список всех элементов
	 * @return array|mixed|null
	 */
    public static function fetchNames(){
        $list= static::find()
            //->joinWith('some_join')
            //->select(['id','name'])
			->orderBy(['name'])
            ->all();
        return ArrayHelper::map($list, 'id', 'sname');
    }
}
