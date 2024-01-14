<?php

namespace app\models;

use voskobovich\linker\LinkerBehavior;
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
 *
 * @property Comps[] $comps
 * @property MaintenanceReqs[] $reqs
 * @property Services[] $services
 * @property Techs[] $techs
 */
class MaintenanceJobs extends ArmsModel
{

public static $title='Регламентное обслуживание';
public static $titles='Регламентное обслуживание';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'maintenance_jobs';
    }

	public function behaviors()
	{
		return [
			[
				'class' => LinkerBehavior::class,
				'relations' => [
					'comps_ids' => [
						'comps',
						//'updater' => ['class' => ManyToManySmartUpdater::class,],
					],
					'reqs_ids' => [
						'reqs',
						//'updater' => ['class' => ManyToManySmartUpdater::class,],
					],
					'services_ids' => [
						'services',
						//'updater' => ['class' => ManyToManySmartUpdater::class,],
					],
					'techs_ids' => [
						'techs',
						//'updater' => ['class' => ManyToManySmartUpdater::class,],
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
            [['schedules_id', 'services_id', 'links', 'changed_at', 'changed_by'], 'default', 'value' => null],
            [['name', 'description'], 'required'],
            [['schedules_id', 'services_id', 'changed_by'], 'integer'],
            [['links'], 'string'],
            [['changed_at'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string', 'max' => 1024],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return [
            'id' => [
				'ID',
				'hint'=>'id hint',
			],
            'name' => [
				'Name',
				'hint'=>'name hint',
			],
            'description' => [
				'Description',
				'hint'=>'description hint',
			],
            'schedules_id' => [
				'Schedules ID',
				'hint'=>'schedules_id hint',
			],
            'services_id' => [
				'Services ID',
				'hint'=>'services_id hint',
			],
            'links' => [
				'Links',
				'hint'=>'links hint',
			],
            'changed_at' => [
				'Changed At',
				'hint'=>'changed_at hint',
			],
            'changed_by' => [
				'Changed By',
				'hint'=>'changed_by hint',
			],
        ];
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
	 * @throws InvalidConfigException
	 */
    public function getTechs()
    {
        return $this->hasMany(Techs::class, ['id' => 'techs_id'])->viaTable('maintenance_jobs_in_techs', ['jobs_id' => 'id']);
    }

}