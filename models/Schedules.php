<?php

namespace app\models;

use app\models\traits\SchedulesModelCalcFieldsTrait;
use voskobovich\linker\LinkerBehavior;
use yii\data\ArrayDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use app\helpers\ArrayHelper;

/**
 * Hint: В оформлении расписания надо придерживаться правила, что расписание отвечает на вопрос когда?
 */

/**
 * This is the model class for table "schedules".
 *
 * @property int $id
 * @property int $baseId ID основного расписания, если это перекрытие
 * @property int $parent_id
 * @property int $override_id
 * @property string $name
 * @property string $start_date
 * @property string $end_date
 * @property string $description
 * @property string $history
 * @property string $providingMode
 * @property string $weekWorkTimeDescription //недельное расписание
 * @property string $dateWorkTimeDescription //начало и конец расписания (даты)
 * @property string $workTimeDescription	 //полное описание из двух выше
 * @property string $usageDescription	 	 //описание применения расписания
 * @property string $usageWorkTimeDescription//полное описание: график, период, применение
 *
 * @property Services[] $providingServices
 * @property Services[] $supportServices
 * @property Acls[] $acls
 * @property AccessTypes[] $accessTypes
 * @property Partners[] $acePartners
 * @property OrgStruct[] $aceDepartments
 * @property Places[] $aclSites
 * @property Schedules $parent
 * @property Schedules $base
 * @property Schedules $overriding
 * @property Schedules[] $overrides
 * @property Schedules[] $children
 * @property Schedules[] $parentsChain
 * @property Schedules[] $childrenNonOverrides
 * @property SchedulesEntries $entries
 * @property SchedulesEntries $periods
 * @property MaintenanceJobs[] $maintenanceJobs
 * @property ArrayDataProvider $WeekDataProvider
 */
class Schedules extends ArmsModel
{
	use SchedulesModelCalcFieldsTrait;
	
	public static $titles = 'Расписания';
	public static $title  = 'Расписание';
	public static $noData = 'никогда';
	public static $allDaysTitle = 'ежедн.';
	public static $allDayTitle = 'круглосуточно.';
	
	const SCENARIO_OVERRIDE = 'scenario_override';
	const SCENARIO_ACL = 'scenario_acl';
	
	public function scenarios()
	{
		$scenarios = parent::scenarios();
		$scenarios[self::SCENARIO_OVERRIDE] = $scenarios[self::SCENARIO_DEFAULT];
		$scenarios[self::SCENARIO_ACL] = $scenarios[self::SCENARIO_DEFAULT];
		return $scenarios;
	}
	
	
	public $linksSchema=[
		'parent_id' => 				[Schedules::class,'children_ids'],
		'override_id' =>			[Schedules::class,'overrides_ids'],
		'entries_ids' =>			[SchedulesEntries::class,'schedule_id'],
		'acls_ids' => 				[Acls::class,'schedules_id'],
		'providing_services_ids' => [Services::class,'providing_schedule_id'],
		'support_services_ids' => 	[Services::class,'support_schedule_id'],
		'maintenance_jobs_ids' => 	[MaintenanceJobs::class,'schedules_id'],
		'overrides_ids' => 			[Schedules::class,'override_id'],
	];

	/**
	 * В списке поведений прикручиваем many-to-many связи
	 * @return array
	 */
	public function behaviors()
	{
		return [
			[
				'class' => LinkerBehavior::class,
				'relations' => [
					'entries_ids' => 'entries',							//
					'providing_services_ids' => 'providingServices',	//это все не many-2-many ссылки
					'support_services_ids' => 'supportServices',		//мне просто нужно вытаскивать
					'acls_ids' => 'acls',								//_ids этих ссылок
					'maintenance_jobs_ids' => 'maintenanceJobs',		//для ведения истории
					'overrides_ids' => 'overrides',						//
					'children_ids' => 'children',						//
				]
			]
		];
	}
	
	public static $dictionary=[
		'usage'=>[
			'acl'=>'Доступ предоставляется',
			'providing'=>'Услуга/сервис предоставляется',
			'support'=>'Услуга/сервис поддерживается',
			'job'=>'Выполняется',
			'working'=>'Рабочее время'
		],
		'usage_complete'=>[
			'acl'=>'Доступ предоставлялся',
			'providing'=>'Услуга/сервис предоставлялся',
			'support'=>'Услуга/сервис поддерживался',
			'job'=>'Выполнялось',
			'working'=>'Рабочее время было'
		],
		'usage_will_be'=>[
			'acl'=>'Доступ будет предоставляться',
			'providing'=>'Услуга/сервис будет предоставляться',
			'support'=>'Услуга/сервис будет поддерживаться',
			'job'=>'Будет выполняться',
			'working'=>'Рабочее время будет'
		],
		'nodata'=>[
			'acl'=>'Доступ не предоставляется никогда',
			'providing'=>'Услуга/сервис не предоставляется никогда',
			'support'=>'Услуга/сервис не поддерживается никогда',
			'job'=>'Не выполняется никогда',
			'working'=>'Рабочее время отсутствует (не работает никогда)'
		],
		'always'=>[
			'acl'=>'всегда',
			'providing'=>'без перерывов (24/7)',
			'support'=>'без перерывов (24/7)',
			'job'=>'всегда (24/7)',
			'working'=>'всегда (24/7)'
		],
		'period_start'=>[
			'acl'=>'Начало периода предоставления доступа (если есть)',
			'providing'=>'Дата начала предоставления услуги (если есть)',
			'support'=>'Дата начала поддержки услуги (если есть)',
			'job'=>'Дата начала выполнения обслуживания (если есть)',
			'working'=>'Дата начала действия расписания (если есть)'
		],
		'period_end'=>[
			'acl'=>'Конец периода предоставления доступа (если есть)',
			'providing'=>'Дата окончания предоставления услуги (если есть)',
			'job'=>'Дата окончания выполнения обслуживания (если есть)',
			'support'=>'Дата окончания поддержки услуги (если есть)',
			'working'=>'Дата окончания действия расписания (если есть)'
		],
		'override_start'=>'Дата начала действия измененного расписания (обязательно)',
		'override_end'=>'Дата возвращения расписания к исходному (не обязательно)',
	];
	
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'schedules';
    }
	
	public function extraFields()
	{
		return array_merge(parent::extraFields(),[
			'status',
			'acls',
		]);
	}
	
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
			[['name','description','defaultItemSchedule'], 'string', 'max' => 255],
			[['start_date','end_date'], 'string', 'max' => 64],
			['start_date','required','on'=>self::SCENARIO_OVERRIDE],
			[['start_date','end_date'],function ($attribute) {
        		if (!is_object($this->parent)) return;
        		foreach ($this->parent->overrides as $override){
        			if ($override->id != $this->id && (
        				$override->matchDate($this->$attribute) ||
						$this->matchDate($override->start_date) ||
						$this->matchDate($override->end_date)
					)) {
						$this->addError($attribute,'Пересекается с периодом '.$override->getPeriodDescription());
					}
				}
			},'on'=>self::SCENARIO_OVERRIDE],
			[['history'],'safe'],
			[['override_id'],'integer'],
			[['parent_id'],	'validateRecursiveLink', 'params'=>['getLink' => 'parent']],
        ];
    }
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return [
			'accessTypes' => [
				'Тип доступа', //для ACLs
				'indexHint' => 'Все типы доступа к ресурсам, которые присутствуют в этом временном доступе'.
					static::searchableOrHint
			],
			'accessPeriods' => 'Активность доступа', //для ACLs
			'aceDepartments' => [
				'Подразделения', //для ACLs
				'indexHint' => 'Подразделения, в которые входят сотрудники, которым предоставлен доступ.',
				//static::searchableOrHint
			],
			'acePartners' => [
				'Контрагенты', //для ACLs
				'indexHint' => 'Контрагенты, которым предоставляется доступ.<br>'.
					'(определяются на основании трудоустройства пользователей, которым предоставлен доступ).<br>',
				'Можно искать как по юр. названию так и по названию бренда'.
				static::searchableOrHint
			],
			'aclSites' => [
				'Площадки', //для ACLs
				'indexHint' => 'Площадки, на которых расположены ресурсы, к которым предоставляется доступ'
				//static::searchableOrHint
			],
			'aclSegments' => [
				'Сегменты', //для ACLs
				'indexHint' => 'Сегменты ИТ инфраструктуры, в которых расположены ресурсы, к которым предоставляется доступ'
				//static::searchableOrHint
			],
			'acls' => 'ACL',
			'children' => 'Дочерние расписания',
			'defaultItemSchedule' => [
				'Расписание на день',
				'hint' => 'Позже можно будет уточнить дни недели и отдельные даты',
			],
			'description' => [
				'Пояснение',
				'hint' => 'Пояснение выводится в списке расписаний',
			],
			'end_date'=>[
				'Дата окончания',
				'placeholder' => 'Конец периода'
			],
			'entries' => 'Даты/периоды',
			'history' => [
				'Заметки',
				'hint' => 'В списке расписаний не видны. Чтобы прочитать надо будет проваливаться в расписание',
				'type' => 'text',
			],
			'maintenanceJobs'=>['Регл. работы','indexHint'=>'Регламентные работы выполняющиеся по этому расписанию',],
			'name' => [
				'name' => 'Как-то надо назвать это расписание',
				'Наименование'
			],
			'objects' => [
				'Субъекты', //для ACLs
				'indexHint' => 'Все субъекты которым предоставляется доступ.<br>'.
					'Поиск по этому полю ведется без учета кому какой доступ'.
					static::searchableOrHint
			],
			'overrides' => 'Периоды - исключения',
			'override' => ['Перекрывает','indexHint'=>'Если является расписанием-перекрытием, то какое расписание перекрывает'],
			'parent_id' => [
				'Исходное расписание',
				'hint'=>'Если указать, то расписание будет повторять исходное, а внесенные дни будут правками поверх исходного. (т.е. в текущее расписание надо будет вносить только отличия от исходного)',
				'indexHint'=>'Базовое расписание, которое является основой для этого',
				'placeholder' => 'Выберите расписание',
			],
			'providingServices'=>['Предост. сервисы','indexHint'=>'Сервисы предоставляемые по этому расписанию',],
			'resources' => [
				'Ресурсы', //для ACLs
				'indexHint' => 'Все ресурсы к которым предоставляется доступ.<br>'.
					'Поиск по этому полю ведется без учета к какому ресурсу какой доступ'.
					static::searchableOrHint
			],
			'start_date'=>[
				'Дата начала',
				'placeholder' => 'Начало периода'
			],
			'supportServices'=>['Поддерж. сервисы','indexHint'=>'Сервисы поддерживаемые по этому расписанию',],
			'workTimeDescription' => [
				'График по дням',
			],
		];
	}
	

	/**
	 * Находим исключения в расписании в указанный период
	 * @param $start int
	 * @param $end int|null
	 * @return array|ActiveRecord[]
	 */
	public function findExceptions(int $start,int $end=null)
	{
		return SchedulesEntries::find()
			->Where(['not',['in', 'date', ['1','2','3','4','5','6','7','def']]])
			->andWhere([
				'is_period'=>0
			])
			->andWhere(['in','schedule_id',array_keys($this->parentsChain)])
			->andWhere(is_null($end)?
				[
					'>=', 'UNIX_TIMESTAMP(date)', $start
				]:
				['and',
					['<=', 'UNIX_TIMESTAMP(date)', $end],
					['>=', 'UNIX_TIMESTAMP(date)', $start],
				])
			->all();
	}
	
	/**
	 * Ищем периоды в расписании в указанный период
	 * @param $start
	 * @param $end
	 * @return SchedulesEntries[]
	 */
	public function findPeriods($start=null,$end=null) {
		$query= SchedulesEntries::find()
			->where([
				'schedule_id'=>$this->id,
				'is_period'=>1
			]);
		
		if ($start || $end)
			$query->andWhere(['and',
				[
					'or',
					['<=', 'UNIX_TIMESTAMP(date)', $end],
					['date'=>null]
				],
				[
					'or',
					['>=', 'UNIX_TIMESTAMP(date_end)', $start],
					['date_end'=>null],
				],
			]);
		
		return $query->all();
		
	}
	
	/**
	 * Возвращает все привязанные к расписанию записи (периоды и дни)
	 * @return ActiveQuery
	 */
	public function getEntries() {
		return $this->hasMany(SchedulesEntries::class, ['schedule_id' => 'id']);
	}
	
	
	/**
	 * @return ActiveQuery
	 */
	public function getParent()
	{
		return $this->hasOne(Schedules::class, ['id' => 'parent_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getChildren()
	{
		return $this->hasMany(Schedules::class, ['parent_id' => 'id']);
	}
	
	/**
	 * Возвращает потомков, которые не являются перекрытиями
	 * @return Schedules[]
	 */
	public function getChildrenNonOverrides()
	{
		return ArrayHelper::getItemsByFields($this->children,['override_id'=>null]);
	}
	
	/**
	 * Возвращает перекрываемое расписание (если это расписание перекрывает другое)
	 * @return ActiveQuery
	 */
	public function getOverriding()
	{
		return $this->hasOne(Schedules::class, ['id' => 'override_id']);
	}
	
	/**
	 * Возвращает перекрывающие расписания (если это расписание перекрывается другими)
	 * @return ActiveQuery
	 */
	public function getOverrides()
	{
		return $this->hasMany(Schedules::class, ['override_id' => 'id'])
			->orderBy(['start_date'=>SORT_DESC]);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getProvidingServices()
	{
		return $this->hasMany(Services::class, ['providing_schedule_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getAcls()
	{
		return $this->hasMany(Acls::class, ['schedules_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getMaintenanceJobs()
	{
		return $this->hasMany(MaintenanceJobs::class, ['schedules_id' => 'id']);
	}
	
	
	/**
	 * @return ActiveQuery
	 */
	public function getSupportServices()
	{
		return $this->hasMany(Services::class, ['support_schedule_id' => 'id']);
	}
	
	
	/**
	 * Возвращает текстовое описание периода вида [unixtime1,unixtime2]
	 * @param $period
	 * @return string
	 */
	public static function generatePeriodDescription($period)
	{
		if (!$period[0] && !$period[1]) return '';
		if (!$period[0]) return 'до '.date('Y-m-d',$period[1]);
		if (!$period[1]) return 'с '.date('Y-m-d',$period[0]);
		return 'с '.date('Y-m-d',$period[0]).' до '.date('Y-m-d',$period[1]);
	}
	

	
	
	
	public function beforeDelete()
	{
		if (count($this->acls)) {
			foreach ($this->acls as $acl)
				if (!$acl->delete()) return false;
		}
		return parent::beforeDelete(); // TODO: Change the autogenerated stub
	}
	
	public function beforeValidate()
	{
		//корректируем сценарии перед валидацией
		if ($this->isOverride) $this->scenario=self::SCENARIO_OVERRIDE;
		elseif ($this->isAcl) $this->scenario=self::SCENARIO_ACL;
		return parent::beforeValidate();
	}
	
	public static function fetchNames(){
		$list= static::find()
			->joinWith('acls')
			->select(['schedules.id','name'])
			->where(['and',
				['acls.schedules_id'=>null],
				['schedules.override_id'=>null],
			])
			->all();
		return ArrayHelper::map($list, 'id', 'name');
	}
	
	public function reverseLinks()
	{
		return [
			$this->services,
			$this->acls,
			$this->maintenanceJobs,
			$this->overrides,
			$this->children,
		];
	}
}
