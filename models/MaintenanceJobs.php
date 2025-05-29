<?php

namespace app\models;

use app\helpers\ArrayHelper;
use app\models\traits\MaintenanceJobsModelCalcFieldsTrait;
use app\models\ui\WikiCache;
use voskobovich\linker\LinkerBehavior;
use voskobovich\linker\updaters\ManyToManySmartUpdater;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "maintenance_jobs".
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int|null $schedules_id
 * @property int|null $services_id
 * @property int|null $parent_id
 * @property string|null $links
 * @property string|null $changed_at
 * @property int|null $changed_by
 * @property string sname
 * @property boolean isBackup
 *
 * @property Comps[] $comps
 * @property MaintenanceReqs[] $reqs
 * @property MaintenanceReqs[] $reqsRecursive
 * @property MaintenanceJobs $parent
 * @property MaintenanceJobs[] $children
 * @property Services[] $services
 * @property Services $service
 * @property Services $serviceRecursive
 * @property Schedules $schedule
 * @property Schedules $scheduleRecursive
 * @property Techs[] $techs
 * @property Users $responsible
 * @property Users[] $support
 */
class MaintenanceJobs extends ArmsModel
{

	use MaintenanceJobsModelCalcFieldsTrait;
	
	public static $title='Регламентное обслуживание';
	public static $titles='Регламентное обслуживание';
	public static $newItemPrefix='Новое';
	
	public $treeChildren=null;
	public $treeDepth=null;
	public $treePrefix=null;
	
	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'maintenance_jobs';
    }
	
	public $linksSchema=[
		'services_ids'=>[Services::class,'maintenance_jobs_ids','updater' => ['class' => ManyToManySmartUpdater::class,]],
		'comps_ids'=>[Comps::class,'maintenance_jobs_ids','updater' => ['class' => ManyToManySmartUpdater::class,]],
		'techs_ids'=>[Techs::class,'maintenance_jobs_ids','updater' => ['class' => ManyToManySmartUpdater::class,],],
		'reqs_ids'=>[MaintenanceReqs::class,'jobs_ids'],
		'children_ids'=>[MaintenanceJobs::class,'parent_id'],
		'services_id' => Services::class,
		'schedules_id' => Schedules::class,
		'parent_id' => MaintenanceJobs::class,
	];
    
	/**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['schedules_id', 'services_id', 'links'], 'default', 'value' => null],
            [['name', 'description'], 'required'],
			[['name'], 'string', 'max' => 255],
			[['description'], 'string'],
			[['schedules_id', 'services_id','archived'], 'integer'],
			[['comps_ids', 'services_ids', 'techs_ids', 'reqs_ids'], 'each','rule'=>['integer']],
            [['links'], 'string'],
			[['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => MaintenanceJobs::class, 'targetAttribute' => ['parent_id' => 'id']],
			[['parent_id'], 'validateRecursiveLink', 'params'=>['getLink' => 'parent']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return ArrayHelper::recursiveOverride(parent::attributeData(),[

            'name' => [
				'Название',
				'hint'=>'Понятное имя для регламентного обслуживания',
			],
			'children' => [
				'Дочерние',
				'hint'=>'Дочерние операции регламентного обслуживания<br>'
					.'входящие в состав этого обслуживания',
			],
			'comps_ids' => [
				'ОС/ВМ',
				'hint'=>'Обслуживаемые в рамках этой регламентной операции',
				'placeholder'=>'ОС/ВМ не обслуживаются',
			],
            'description' => [
				'Описание',
				'hint'=>'Описание регламентных операций с пояснением деталей',
				'type'=>'text',
			],
			'links' => [
				'Ссылки',
				'hint'=>'С информацией по данному обслуживанию',
			],
			'objects' => ['Объекты','indexHint'=>'Обслуживаемые объекты'],
			'parent_id' => [
				'Входит в состав',
				'hint'=>'Если это обслуживание является частью более общего регламента,<br>'
					.'то необходимо указать родительское регламентное обслуживание тут.<br>',
				'placeholder'=>'Не входит в состав других операций',
			],
			'reqs_ids' => [
				'Выполняет требования',
				'hint'=>'Какие требования по регламентному обслуживания выполняет эта операция',
				'placeholder'=>'Никакие не выполняет',
				'is_inheritable'=>true,
			],
			'responsible' => ['Ответственный','Кто отвечает за это регламентное обслуживание'],
            'schedules_id' => [
				'Расписание',
				'hint'=>'Расписание когда производятся регламентные операции',
				'placeholder'=>'Без расписания',
				'is_inheritable'=>true,
			],
            'services_id' => [
				'В рамках сервиса',
				'hint'=>'В рамках какого сервиса/услуги производятся операции обслуживания.<br>'
					.'Нужно для определения ответственного и поддержки<br>'
					.'Если подходящего сервиса/услуги нет, то нужно завести',
				'placeholder'=>'Укажите сервис, в рамках которого производится обслуживание',
				'is_inheritable'=>true,
			],
			'services_ids' => [
				'Сервисы',
				'hint'=>'Обслуживаемые в рамках этой регламентной операции',
				'placeholder'=>'Сервисы не обслуживаются',
			],
			'support' => ['Поддержка','Команда поддержки регламентного обслуживания'],
			'techs_ids' => [
				'Оборудование',
				'hint'=>'Обслуживаемое в рамках этой регламентной операции',
				'placeholder'=>'Оборудование не обслуживается',
			],
		]);
    }
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
    public function getComps()
    {
        return $this->hasMany(Comps::class, ['id' => 'comps_id'])->viaTable('maintenance_jobs_in_comps', ['jobs_id' => 'id']);
    }
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
    public function getReqs()
    {
        return $this->hasMany(MaintenanceReqs::class, ['id' => 'reqs_id'])->viaTable('maintenance_reqs_in_jobs', ['jobs_id' => 'id']);
    }
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getServices()
	{
		return $this->hasMany(Services::class, ['id' => 'services_id'])->viaTable('maintenance_jobs_in_services', ['jobs_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getService()
	{
		return $this->hasOne(Services::class, ['id' => 'services_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getParent()
	{
		return $this->hasOne(MaintenanceJobs::class, ['id' => 'parent_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getChildren()
	{
		return $this->hasMany(MaintenanceJobs::class, ['parent_id'=>'id']);
	}

	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
    public function getTechs()
    {
        return $this->hasMany(Techs::class, ['id' => 'techs_id'])->viaTable('maintenance_jobs_in_techs', ['jobs_id' => 'id']);
    }
	
	/**
	 * @return ActiveQuery
	 */
	public function getSchedule()
	{
		return $this->hasOne(Schedules::class, ['id' => 'schedules_id']);
	}
	
	
	public function reverseLinks()
	{
		return [
			$this->reqs,
			$this->services,
			$this->techs,
			$this->comps,
			
		];
	}
	
	public function afterSave($insert, $changedAttributes)
	{
		if (isset($changedAttributes['parent_id'])) {
			//если изменился родитель, то сбрасываем свой кэш описания
			WikiCache::invalidateParentReference($this,'description');
		}
		if (isset($changedAttributes['description'])) {
			//если изменилось описание, то сбрасываем кэш описания свой и у прямых потомков
			WikiCache::invalidateParentReference($this,'description');
			foreach ($this->children as $item)
				WikiCache::invalidateParentReference($item,'description');
		}
	}
}