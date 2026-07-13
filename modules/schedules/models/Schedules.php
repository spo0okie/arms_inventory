<?php

namespace app\modules\schedules\models;

use app\helpers\DateTimeHelper;
use app\modules\schedules\compile\CompiledScheduleHelper;
use app\modules\schedules\compile\SchedulesCompiler;
use app\modules\schedules\helpers\TimeIntervalsHelper;
use app\modules\schedules\models\traits\SchedulesModelCalcFieldsTrait;
use app\modules\schedules\models\SchedulesEntries;
use app\types\TextType;
use voskobovich\linker\LinkerBehavior;
use yii\data\ArrayDataProvider;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use app\helpers\ArrayHelper;
use app\helpers\StringHelper;
use app\types\DateType;

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
 * @property \app\models\Services[] $providingServices
 * @property \app\models\Services[] $supportServices
 * @property \app\models\Acls[] $acls
 * @property \app\models\AccessTypes[] $accessTypes
 * @property \app\models\Partners[] $acePartners
 * @property \app\models\OrgStruct[] $aceDepartments
 * @property \app\models\Places[] $aclSites
 * @property Schedules $parent
 * @property Schedules $base
 * @property Schedules $overriding
 * @property Schedules[] $overrides
 * @property Schedules[] $children
 * @property Schedules[] $parentsChain
 * @property Schedules[] $childrenNonOverrides
 * @property SchedulesEntries $entries
 * @property SchedulesEntries $periods
 * @property \app\models\MaintenanceJobs[] $maintenanceJobs
 * @property ArrayDataProvider $WeekDataProvider
 */
class Schedules extends \app\models\base\ArmsModel
{
	use SchedulesModelCalcFieldsTrait;
	
	public static $titles = 'Расписания';

	public static function modelDescription(): string
	{
		return 'Расписания: календарные графики (рабочие недели, исключения, перекрытия) — используются сервисами, обслуживанием и временными доступами.';
	}
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
		'acls_ids' => 				[\app\models\Acls::class,'schedules_id'],
		'providing_services_ids' => [\app\models\Services::class,'providing_schedule_id'],
		'support_services_ids' => 	[\app\models\Services::class,'support_schedule_id'],
		'maintenance_jobs_ids' => 	[\app\models\MaintenanceJobs::class,'schedules_id'],
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
				],
			],
		];
	}
	
	public static $dictionary=[
		'usage'=>[
			'acl'=>'Доступ предоставляется',
			'providing'=>'Услуга/сервис предоставляется',
			'support'=>'Услуга/сервис поддерживается',
			'job'=>'Выполняется',
			'working'=>'Рабочее время',
		],
		'usage_complete'=>[
			'acl'=>'Доступ предоставлялся',
			'providing'=>'Услуга/сервис предоставлялся',
			'support'=>'Услуга/сервис поддерживался',
			'job'=>'Выполнялось',
			'working'=>'Рабочее время было',
		],
		'usage_will_be'=>[
			'acl'=>'Доступ будет предоставляться',
			'providing'=>'Услуга/сервис будет предоставляться',
			'support'=>'Услуга/сервис будет поддерживаться',
			'job'=>'Будет выполняться',
			'working'=>'Рабочее время будет',
		],
		'nodata'=>[
			'acl'=>'Доступ не предоставляется никогда',
			'providing'=>'Услуга/сервис не предоставляется никогда',
			'support'=>'Услуга/сервис не поддерживается никогда',
			'job'=>'Не выполняется никогда',
			'working'=>'Рабочее время отсутствует (не работает никогда)',
		],
		'always'=>[
			'acl'=>'всегда',
			'providing'=>'без перерывов (24/7)',
			'support'=>'без перерывов (24/7)',
			'job'=>'всегда (24/7)',
			'working'=>'всегда (24/7)',
		],
		'period_start'=>[
			'acl'=>'Начало периода предоставления доступа (если есть)',
			'providing'=>'Дата начала предоставления услуги (если есть)',
			'support'=>'Дата начала поддержки услуги (если есть)',
			'job'=>'Дата начала выполнения обслуживания (если есть)',
			'working'=>'Дата начала действия расписания (если есть)',
		],
		'period_end'=>[
			'acl'=>'Конец периода предоставления доступа (если есть)',
			'providing'=>'Дата окончания предоставления услуги (если есть)',
			'job'=>'Дата окончания выполнения обслуживания (если есть)',
			'support'=>'Дата окончания поддержки услуги (если есть)',
			'working'=>'Дата окончания действия расписания (если есть)',
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
        		foreach ($this->parent->overrides as $override) {
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
			//общий синтаксис поиска (| & !) показывается в search-тултипе автоматически
			'accessTypes' => [
				'Тип доступа', //для ACLs
				'indexHint' => 'Все типы доступа к ресурсам, которые присутствуют в этом временном доступе',
				'join'=>['acls.aces.accessTypes'],
				'filter'=>'access_types.name',
			],
			'accessPeriods' => [
				'Активность доступа', //для ACLs
				'join'=>['periods'],
			],
			'aceDepartments' => [
				'Подразделения', //для ACLs
				'indexHint' => 'Подразделения, в которые входят сотрудники, которым предоставлен доступ.',
				//read-only вычисляемая ссылка (категория C): только вывод
				'ref'=>\app\models\OrgStruct::class, 'refMulti'=>true,
				'join'=>['acls.aces.users.orgStruct'],
			],
			'acePartners' => [
				'Контрагенты', //для ACLs
				//строка про поиск была отдельным элементом массива (выпала из indexHint) - починено
				'indexHint' => 'Контрагенты, которым предоставляется доступ<br>'.
					'(определяются на основании трудоустройства пользователей, которым предоставлен доступ).<br>'.
					'Искать можно как по юр. названию, так и по названию бренда',
				//read-only вычисляемая ссылка (категория C): только вывод
				'ref'=>\app\models\Partners::class, 'refMulti'=>true,
				'join'=>['acls.aces.users.org'],
			],
			'aclSites' => [
				'Площадки', //для ACLs
				'indexHint' => 'Площадки, на которых расположены ресурсы, к которым предоставляется доступ',
				//join просто огромный, и search не сделать
			],
			'aclSegments' => [
				'Сегменты', //для ACLs
				'indexHint' => 'Сегменты ИТ инфраструктуры, к которым предоставляется доступ:<ul>'
					.'<li>Для сервисов - явно указанный или наследованный от родительского (дочерние не учитываются)</li>'
					.'<li>Для IP сетей - явно указанный сегмент</li>'
					.'<li>Для IP адресов - сегмент сети (если есть такая сеть)</li>'
					.'<li>Для оборудования и ОС - сегмент IP адреса(ов)</li>'
				.'</ul>',
				//read-only вычисляемая ссылка (категория C): только вывод
				'ref'=>\app\models\Segments::class, 'refMulti'=>true,
				//join просто огромный, и search не сделать
			],
			'acls' => [
				'ACL',
				'join'=>['acls'],
			],
			'children' => 'Дочерние расписания',
			'defaultItemSchedule' => [
				'Расписание на день',
				'hint' => 'Позже можно будет уточнить дни недели и отдельные даты',
				'join'=>['entries'],
			],
			'description' => [
				'Пояснение',
				'hint' => 'Пояснение выводится в списке расписаний',
			],
			'end_date'=>[
				'Дата окончания',
				'placeholder' => 'Конец периода',
				'typeClass' => DateType::class,
			],
			'entries' => [
				'Даты/периоды',
				'join'=>['entries'],
			],
			'history' => [
				'Заметки',
				'hint' => 'В списке расписаний не показываются — видны только при просмотре самого расписания',
				'type' => 'text',
				'typeClass' => TextType::class,
			],
			'maintenanceJobs'=>['Регл. работы','indexHint'=>'Регламентные работы выполняющиеся по этому расписанию',],
			'name' => [
				'name' => 'Как-то надо назвать это расписание',
				'Наименование',
				'hint' => 'Название расписания/временного доступа',
			],
			'override_id' => [
				'Перекрывает',
				'hint' => 'Базовое расписание, поверх которого действует это расписание-перекрытие',
			],
			'objects' => [
				'Субъекты', //для ACLs
				'indexHint' => 'Все субъекты которым предоставляется доступ.<br>'.
					'Поиск по этому полю ведется без учета кому какой доступ',
			],
			'overrides' => [
				'Периоды - исключения',
				'join'=>['entries'],
			],
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
					'Поиск по этому полю ведется без учета к какому ресурсу какой доступ',
			],
			'start_date'=>[
				'Дата начала',
				'placeholder' => 'Начало периода',
				'typeClass' => DateType::class,
			],
			'supportServices'=>['Поддерж. сервисы','indexHint'=>'Сервисы поддерживаемые по этому расписанию',],
			'status' => [
				'Активно сейчас',
				'indexHint' => 'Попадает ли текущий момент в рабочее время расписания (расчёт на сервере)',
				'typeClass' => \app\types\BooleanType::class,
			],
			'statusJs' => [
				'Активно (live)',
				'indexHint' => 'Активность расписания, пересчитываемая в браузере каждую минуту по compiled_json',
				'typeClass' => \app\types\BooleanType::class,
			],
			'compiled_json' => [
				'typeClass' => \app\types\JsonType::class,
			],
			'workTimeDescription' => [
				'График по дням',
				'typeClass' => \app\types\TextType::class,
			],
		];
	}
	
	/**
	 * Путь до папки views
	 * перекрываем т.к. у нас есть 2 типа рендеров для расписания доступа более специфичные шаблоны по специальному пути
	 * @return mixed|string
	 */
	public function getViewsPath() {
		if (isset($this->attrsCache['viewsPath'])) return $this->attrsCache['viewsPath'];
		return $this->attrsCache['viewsPath']=StringHelper::class2ViewsPath($this->isAcl?
			'app\modules\schedules\models\ScheduledAccess':
			'app\modules\schedules\models\Schedules'
		);
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
				'is_period'=>0,
			])
			->andWhere(['in','schedule_id',array_keys($this->parentsChain)])
			->andWhere(is_null($end)?
				[
					'>=', 'UNIX_TIMESTAMP(date)', $start,
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
				'is_period'=>1,
			]);
		
		if ($start || $end)
			$query->andWhere(['and',
				[
					'or',
					['<=', 'UNIX_TIMESTAMP(date)', $end],
					['date'=>null],
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
	 * Родительское расписание.
	 *
	 * НЕСТАНДАРТНО (не hasOne-relation) намеренно - разделяемый экземпляр из общего
	 * кэша расписаний: рекурсивный подъем по родителям (getDayEntryRecursive и т.п.)
	 * имеет неограниченную глубину, hasOne давал бы каждому расписанию СВОЮ копию
	 * родителя и каждый шаг рекурсии от каждой копии был бы отдельным запросом.
	 * Валидатору рекурсии (validateRecursiveLink, params getLink='parent') нужен
	 * объект/null - контракт соблюден. Цена: with('parent') невозможен.
	 * @return Schedules|null
	 */
	public function getParent()
	{
		if (!$this->parent_id) return null;
		return static::getLoadedItem($this->parent_id,true);
	}
	
	public function getPeriods()
	{
		return $this->hasMany(SchedulesEntries::class, ['schedule_id' => 'id'])
			->andOnCondition(['is_period'=>1])
			->from(['schedules_periods'=>SchedulesEntries::tableName()]);
	}
	
	/**
	 * Рабочие периоды
	 * @return ActiveQuery
	 */
	public function getPosPeriods()
	{
		return $this->getPeriods()->andOnCondition(['is_work'=>1]);
	}
	
	/**
	 * Нерабочие периоды
	 * @return ActiveQuery
	 */
	public function getNegPeriods()
	{
		return $this->getPeriods()->andOnCondition(['is_work'=>0]);
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
		return $this->hasMany(\app\models\Services::class, ['providing_schedule_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getAcls()
	{
		return $this->hasMany(\app\models\Acls::class, ['schedules_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getMaintenanceJobs()
	{
		return $this->hasMany(\app\models\MaintenanceJobs::class, ['schedules_id' => 'id']);
	}
	
	
	/**
	 * @return ActiveQuery
	 */
	public function getSupportServices()
	{
		return $this->hasMany(\app\models\Services::class, ['support_schedule_id' => 'id']);
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

	/**
	 * Флаг, помогающий избежать рекурсии при каскадной перекомпиляции.
	 * @var bool
	 */
	private $compiling = false;

	/**
	 * Перекомпилировать расписание и каскадно — все зависимые (overrides + потомков по parent_id).
	 *
	 * Использует прямой UPDATE вместо save(), чтобы:
	 * - не дёргать повторно afterSave (и тем самым снова запускать компиляцию);
	 * - не требовать валидации;
	 * - не задевать updated_at/updated_by, которые не относятся к компиляции.
	 */
	public function recompileCascade(array $visited = []): void
	{
		if (isset($visited[$this->id]) || $this->compiling) return;
		$visited[$this->id] = true;

		// Берём свежий экземпляр без кэшированных relations — иначе getOverrides()
		// вернёт устаревшие данные после того как child был только что сохранён.
		$fresh = static::findOne($this->id);
		if ($fresh === null) return;

		$fresh->compiling = true;
		try {
			// Сбой компиляции не должен ронять afterSave/save — это нарушит редактирование
			// расписания. Логируем и продолжаем — потребители compiled_json должны выдержать
			// устаревшее или отсутствующее значение.
			try {
				$compiled = SchedulesCompiler::compile($fresh);
				$json = json_encode($compiled, JSON_UNESCAPED_UNICODE);
			} catch (\Throwable $e) {
				\Yii::error("Schedules#{$fresh->id} compile failed: " . $e->getMessage(), __METHOD__);
				return;
			}
			if ($json === false) return;
			if ($fresh->compiled_json !== $json) {
				static::updateAll(['compiled_json' => $json], ['id' => $fresh->id]);
				$fresh->compiled_json = $json;
				if ($this !== $fresh) $this->compiled_json = $json;
				//updateAll идет мимо AR-событий - сбрасываем кэш разделяемых экземпляров
				//(getMaster/getParent) вручную, иначе они отдадут старый compiled_json
				static::invalidateAllItemsCache();
			}
		} finally {
			$fresh->compiling = false;
		}

		// Каскад: overrides этого расписания (они участвуют в compiled_json родителя через getOverrides())
		foreach ($fresh->overrides as $override) {
			$override->recompileCascade($visited);
		}
		// Каскад: дочерние расписания (parent_id = $this->id), не являющиеся overrides
		foreach ($fresh->childrenNonOverrides as $child) {
			$child->recompileCascade($visited);
		}
	}

	public function afterSave($insert, $changedAttributes)
	{
		parent::afterSave($insert, $changedAttributes);
		// Избегаем рекурсии при вызове из recompileCascade
		if ($this->compiling) return;

		// Если это override — перекомпилировать родителя, потому что compiled_json родителя
		// содержит overrides в своей структуре.
		if ($this->isOverride && $this->override_id) {
			$parent = static::findOne($this->override_id);
			if ($parent) $parent->recompileCascade();
		} else {
			$this->recompileCascade();
		}
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

	// =========================================================================
	// Параметрические методы расчёта расписания и работы со временем.
	// Не являются calc-полями (имеют обязательные параметры или служебные helper'ы),
	// поэтому живут в самой модели, а не в SchedulesModelCalcFieldsTrait.
	// SchedulesHistory эти методы НЕ получает — для архивных данных они не имеют смысла.
	// =========================================================================

	/**
	 * Расписание заканчивается до даты.
	 * @param int|string $date
	 * @return bool
	 */
	public function endsBeforeDate($date) {
		if (!is_int($date)) $date = strtotime($date);
		return ($this->endUnixTime && $this->endUnixTime < $date);
	}

	/**
	 * Расписание начинается после даты.
	 * @param int|string $date
	 * @return bool
	 */
	public function startsAfterDate($date) {
		if (!is_int($date)) $date = strtotime($date);
		return ($this->startUnixTime && $this->startUnixTime > $date);
	}

	/**
	 * Расписание перекрывает дату.
	 * @param int|string $date
	 * @return bool
	 */
	public function matchDate($date) {
		if (!$date) return false;
		if (is_string($date)) $date = strtotime($date);
		if ($this->startsAfterDate($date)) return false;
		if ($this->endsBeforeDate($date)) return false;
		return true;
	}

	/**
	 * Находит расписание недели, действующее на дату (с учётом overrides).
	 * @param string|int $date
	 * @return Schedules|null
	 */
	public function getWeekSchedule($date) {
		foreach ($this->overrides as $override)
			if ($override->matchDate($date)) return $override;
		if ($this->matchDate($date)) return $this;
		return null;
	}

	/**
	 * Слово из словаря с учётом providingMode.
	 * @param string $word
	 * @return string|array
	 */
	public function getDictionary($word) {
		if (!isset(self::$dictionary[$word])) return $word;
		return self::$dictionary[$word][$this->getProvidingMode()] ?? self::$dictionary[$word];
	}

	/**
	 * Запись на конкретный день недели/дату/'def' в этом расписании (без подъёма по предкам).
	 * @param string $day
	 * @return SchedulesEntries|null
	 */
	public function getDayEntry($day) {
		if (!isset($this->attrsCache['daysEntries'])) {
			$this->attrsCache['daysEntries'] = ['def'=>null,'1'=>null,'2'=>null,'3'=>null,'4'=>null,'5'=>null,'6'=>null,'7'=>null];
			foreach ($this->entries as $entry)
				if (!$entry->is_period)
					$this->attrsCache['daysEntries'][$entry->date] = $entry;
		}
		return $this->attrsCache['daysEntries'][$day] ?? null;
	}

	/**
	 * Запись на день, поднимаясь по родителям и учитывая overrides.
	 * @param string $day день недели/'def'
	 * @param string|null $date если задано — учитывается перекрытие активное на эту дату
	 * @return SchedulesEntries|null
	 */
	public function getDayEntryRecursive($day, $date)
	{
		// override ничего не наследует
		if ($this->isOverride) return $this->getDayEntry($day);

		$period = is_null($date) ? $this : $this->getWeekSchedule($date);
		if (!is_null($period) && !is_null($daySchedule = $period->getDayEntry($day))) {
			return $daySchedule;
		}
		return is_object($this->parent) ? $this->parent->getDayEntryRecursive($day, $date) : null;
	}

	/**
	 * Запись на день недели с fallback на 'def'.
	 * @param string $weekday
	 * @param string $date
	 * @return SchedulesEntries|null
	 */
	public function getWeekdayEntryRecursive($weekday, $date)
	{
		if (is_null($daySchedule = $this->getDayEntryRecursive($weekday, $date))) {
			if (is_null($daySchedule = $this->getDayEntryRecursive('def', $date))) return null;
		}
		$daySchedule = clone $daySchedule;
		$daySchedule->requestedWeekDay = $weekday;
		$daySchedule->requestedDate = $date;
		return $daySchedule;
	}

	/**
	 * Запись на конкретную дату с учётом всей иерархии и overrides (без периодов).
	 * @param string $date
	 * @return SchedulesEntries|null
	 */
	public function getDateEntryRecursive($date)
	{
		if (!is_null($daySchedule = $this->getDayEntryRecursive($date, null))) return $daySchedule;
		$words = explode('-', $date);
		if (count($words) < 3) return null;
		$unixDate = strtotime($date);
		if (
			($this->startUnixTime && $unixDate < $this->startUnixTime)
			||
			($this->endUnixTime && $unixDate > $this->endUnixTime)
		) return null;
		$weekday = date('N', mktime(0, 0, 0, $words[1], $words[2], $words[0]));
		return $this->getWeekdayEntryRecursive($weekday, $date);
	}

	/**
	 * Полное расписание на дату (legacy путь): объединяет график с периодами.
	 * @param string $date
	 * @return array
	 */
	public function getDateSchedule($date)
	{
		$sources = [];
		$dateScheduleEntry = $this->getDateEntryRecursive($date);
		if (!is_object($dateScheduleEntry)) {
			$dateScheduleEntry = new SchedulesEntries();
			$dateScheduleEntry->load(['is_period'=>0,'schedule'=>'-','date'=>'def'], '');
		} else {
			$dateScheduleEntry = clone $dateScheduleEntry;
		}
		$dateScheduleEntry->previousDateEntry = $this->getDateEntryRecursive(DateTimeHelper::previousDay($date));
		$periods = $this->findPeriods(strtotime($date.' 00:00:00'), strtotime($date.' 23:59:59'));
		if (is_object($dateScheduleEntry->master)) $sources['master'] = $dateScheduleEntry->master;
		if (count($periods)) $sources['periods'] = $periods;

		$positive = $dateScheduleEntry->getIntervals($date);
		$posPeriods = [];
		$negative = [];
		$negPeriods = [];
		foreach ($periods as $period) {
			if (!is_object($period)) continue;
			if ($period->is_work) {
				$positive = array_merge($positive, $period->getIntervals($date));
				$posPeriods[] = $period;
			} else {
				$negative = array_merge($negative, $period->getIntervals($date));
				$negPeriods[] = $period;
			}
		}
		$positive = TimeIntervalsHelper::intervalMerge($positive);
		if (count($negative)) {
			$negative = TimeIntervalsHelper::intervalMerge($negative);
			$positive = TimeIntervalsHelper::intervalsSubtraction($positive, $negative);
		}
		TimeIntervalsHelper::intervalsSort($positive);
		$arSchedule = [];
		foreach ($positive as $interval) $arSchedule[] = SchedulesEntries::unixIntervalToSchedule($interval);
		$strSchedule = count($arSchedule) ? implode(',', $arSchedule) : '-';
		$dateScheduleEntry->schedule = $strSchedule;
		return [
			'schedule'   => $strSchedule,
			'day'        => $dateScheduleEntry,
			'posPeriods' => $posPeriods,
			'negPeriods' => $negPeriods,
			'sources'    => $sources,
		];
	}

	/**
	 * Попадание дата/время в рабочий интервал.
	 * Возвращает int 0|1, правая граница интервала ВКЛЮЧЕНА (legacy-семантика).
	 * Реализован поверх CompiledScheduleHelper.
	 * @param string $date
	 * @param string $time
	 * @return int
	 */
	public function isWorkTime($date, $time)
	{
		$rt = $this->getCompiledRuntime();
		if ($rt === null) return 0;
		$dt = $date.' '.$time;
		if ($rt->isWorkTime($dt)) return 1;
		// Compiled-рантайм исключает правую границу. Legacy её включает —
		// проверим точное попадание на end отдельно.
		$tsm = CompiledScheduleHelper::strToTsm($dt);
		if ($tsm === null) return 0;
		$intervals = $rt->getDateIntervals($tsm);
		$minutesFromDay = $tsm - CompiledScheduleHelper::tsmToDateTsm($tsm);
		foreach ($intervals as $interval) {
			if ($interval[1] === $minutesFromDay) return 1;
		}
		return 0;
	}

	/**
	 * Метаданные активного интервала.
	 * Возвращает '{}' если время не рабочее или meta пустая, иначе JSON-строка.
	 * @param string $date
	 * @param string $time
	 * @return string
	 */
	public function metaAtTime($date, $time)
	{
		$rt = $this->getCompiledRuntime();
		if ($rt === null) return '{}';
		$meta = $rt->getMeta($date.' '.$time);
		if (empty($meta)) return '{}';
		return json_encode($meta, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Метаданные текущего или ближайшего следующего рабочего интервала.
	 * @param string $date
	 * @param string $time
	 * @return string '{}' либо JSON-строка
	 */
	public function nextWorkingMeta($date, $time)
	{
		$rt = $this->getCompiledRuntime();
		if ($rt === null) return '{}';
		$meta = $rt->nextWorkingMeta($date.' '.$time);
		if (empty($meta)) return '{}';
		return json_encode($meta, JSON_UNESCAPED_UNICODE);
	}

	/**
	 * CompiledScheduleHelper поверх свежего compiled_json (не трейтное calc-поле).
	 * При наличии id всегда читает свежий compiled_json из БД (in-memory может
	 * устареть после каскадной перекомпиляции от дочерних SchedulesEntries).
	 */
	private function getCompiledRuntime(): ?CompiledScheduleHelper
	{
		$compiled = null;
		if (!empty($this->id)) {
			$compiled = static::find()->select('compiled_json')->where(['id' => $this->id])->scalar();
			if ($compiled !== false && $compiled !== null) {
				$this->compiled_json = $compiled;
			} else {
				$compiled = null;
			}
		}
		if (empty($compiled)) $compiled = $this->compiled_json ?? null;
		if (empty($compiled)) {
			try {
				$compiled = SchedulesCompiler::compile($this);
			} catch (\Throwable $e) {
				\Yii::error("Schedules#{$this->id} live compile failed: " . $e->getMessage(), __METHOD__);
				return null;
			}
		}
		return new CompiledScheduleHelper($compiled);
	}
}
