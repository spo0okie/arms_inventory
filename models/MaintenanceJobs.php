<?php

namespace app\models;

use app\helpers\ArrayHelper;
use app\models\traits\MaintenanceJobsModelCalcFieldsTrait;
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
 * @property string|null $links
 * @property string|null $changed_at
 * @property int|null $changed_by
 * @property string sname
 * @property boolean isBackup
 *
 * @property Comps[] $comps
 * @property MaintenanceReqs[] $reqs
 * @property Services[] $services
 * @property Services $service
 * @property Schedules $schedule
 * @property Techs[] $techs
 * @property Users $responsible
 * @property Users[] $support
 */
class MaintenanceJobs extends ArmsModel
{

	use MaintenanceJobsModelCalcFieldsTrait;
	
	public static $title='Регламентное обслуживание';
	public static $titles='Регламентное обслуживание';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'maintenance_jobs';
    }
	
	public $linksSchema=[
		'services_ids'=>[Services::class,'maintenance_jobs_ids'],
		'comps_ids'=>[Comps::class,'maintenance_jobs_ids'],
		'techs_ids'=>[Techs::class,'maintenance_jobs_ids'],
		'reqs_ids'=>[MaintenanceReqs::class,'jobs_ids'],
		'services_id' => Services::class,
		'schedules_id' => Schedules::class,
	];
    
    public function behaviors()
	{
		return [
			[
				'class' => LinkerBehavior::class,
				'relations' => [
					'comps_ids' => [
						'comps',
						'updater' => ['class' => ManyToManySmartUpdater::class,],
					],
					'reqs_ids' => [
						'reqs',
						//'updater' => ['class' => ManyToManySmartUpdater::class,],
					],
					'services_ids' => [
						'services',
						'updater' => ['class' => ManyToManySmartUpdater::class,],
					],
					'techs_ids' => [
						'techs',
						'updater' => ['class' => ManyToManySmartUpdater::class,],
					],
				]
			]
		];
	}


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
            'description' => [
				'Описание',
				'hint'=>'Описание регламентных операций с пояснением деталей',
			],
            'schedules_id' => [
				'Расписание',
				'hint'=>'Расписание когда производятся регламентные операции',
			],
            'schedule' => ['alias'=>'schedules_id'],
            'services_id' => [
				'В рамках сервиса',
				'hint'=>'В рамках какого сервиса производятся операции обслуживания.'
					.'<br>Нужно для определения ответственного',
			],
			'service' => ['alias'=>'services_id'],
            'links' => [
				'Ссылки',
				'hint'=>'С информацией по данному обслуживанию',
			],
			'reqs_ids' => [
				'Выполняет требования',
				'hint'=>'Какие требования по регламентному обслуживания выполняет эта операция',
			],
			'reqs' => ['alias'=>'reqs_ids'],
			'comps_ids' => [
				'ОС/ВМ',
				'hint'=>'Обслуживаемые в рамках этой регламентной операции',
			],
			'comps' => ['alias'=>'comps_ids'],
			'techs_ids' => [
				'Оборудование',
				'hint'=>'Обслуживаемое в рамках этой регламентной операции',
			],
			'techs' => ['alias'=>'techs_ids'],
			'services_ids' => [
				'Сервисы',
				'hint'=>'Обслуживаемые в рамках этой регламентной операции',
			],
			'services' => ['alias'=>'services_ids'],
			'responsible' => ['Ответственный'],
			'support' => ['Поддержка'],
			'objects' => ['Объекты','indexHint'=>'Обслуживаемые объекты'],
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
}