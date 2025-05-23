<?php

namespace app\models;

use app\models\traits\MaintenanceReqsModelCalcFieldsTrait;
use kartik\grid\BooleanColumn;
use voskobovich\linker\LinkerBehavior;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "maintenance_reqs".
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int|null $spread_comps
 * @property int|null $spread_techs
 * @property int|null $is_backup
 * @property string|null $links
 * @property string|null $updated_at
 * @property string|null $updated_by
 * @property string sname
 * @property bool archived
 * @property bool absorbed
 * @property bool archivedOrAbsorbed
 *
 * @property Users $changedBy
 * @property Comps[] $comps
 * @property MaintenanceReqs[] $includes
 * @property MaintenanceJobs[] $jobs
 * @property MaintenanceReqs[] $includedBy
 * @property Services[] $services
 * @property Techs[] $techs
 */
class MaintenanceReqs extends ArmsModel
{
	use MaintenanceReqsModelCalcFieldsTrait;

	public static $title='Требования по обслуживанию';
	public static $titles='Требования по обслуживанию';
	
	//признак, что в итоговом наборе требований конкретно это можно игнорировать,
	//т.к. оно удовлетворяется более общим требованием
	public $absorbed=false;
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'maintenance_reqs';
    }
    
	public $linksSchema=[
		'services_ids'=>[Services::class,'maintenance_jobs_ids'],
		'comps_ids'=>[Comps::class,'maintenance_jobs_ids'],
		'techs_ids'=>[Techs::class,'maintenance_jobs_ids'],
		'jobs_ids'=>[MaintenanceJobs::class,'reqs_ids'],
		'includes_ids'=>[MaintenanceReqs::class,'included_ids'],
		'included_ids'=>[MaintenanceReqs::class,'includes_ids','loader'=>'includedBy'],
	];

    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['links', 'updated_at', 'updated_by'], 'default', 'value' => null],
			[['spread_comps', 'spread_techs'], 'default', 'value' => 1],
			[['name', 'description'], 'required'],
			[['name', ], 'unique'],
            [['spread_comps', 'spread_techs','is_backup','archived'], 'integer'],
            [['links'], 'string'],
            [['updated_at', 'updated_by'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string'],
			//[['comps_ids'],'each', 'rule'=>['integer']],
			[['includes_ids','included_ids'],'each', 'rule'=>['integer']],
			['includes_ids',function ($attribute){
				$this->validateRecursiveLink($attribute,$params=[
					'getLink'=>'includes',
					'initialLink'=>static::find()
						->where(['id'=>$this->$attribute])
						->all()
				]);
			}],
			['included_ids',function ($attribute){
				$this->validateRecursiveLink($attribute,$params=[
					'getLink'=>'includedBy',
					'initialLink'=>static::find()
						->where(['id'=>$this->$attribute])
						->all()
				]);
			}],
	
			//['jobs_ids','each', 'rule'=>['integer']],
			//['services_ids','each', 'rule'=>['integer']],
			//['techs_ids','each', 'rule'=>['integer']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
		return array_merge(parent::attributeData(),[
            'name' => [
				'Название',
				'hint'=>'Короткое наименование требований по обслуживанию',
			],
            'description' => [
				'Описание',
				'hint'=>'Описание требований по регламентному обслуживанию',
				'type'=>'text',
			],
			'is_backup' => [
				'Относится к резервному копированию',
				'hint'=>'Это требование является требованием по резервному копированию.'
					.'<br>Нужно для выделения таких требований отдельно от прочих',
				'indexHint'=>'{same}',
				'indexLabel'=>'<i class="fas fa-archive"></i>',
				'column'=>['class'=>BooleanColumn::class],
			],
			'spread_comps' => [
				'Распространяется на ОС',
				'indexLabel'=>'<i class="fas fa-laptop-code"></i>',
				'indexHint'=>'{same}',
				'hint'=>'При прикреплении требований к сервису, автоматически предъявлять эти требования к операционным системам на которых он работает',
				'column'=>['class'=>BooleanColumn::class],
			],
            'spread_techs' => [
				'Распространяется на оборудование',
				'indexLabel'=>'<i class="fas fa-screwdriver"></i>',
				'indexHint'=>'{same}',
				'hint'=>'При прикреплении требований к сервису, автоматически предъявлять эти требования к оборудованию на которых он работает. (не распространяется АРМ на которых крутятся операционные системы)',
				'column'=>['class'=>BooleanColumn::class],
			],
			'includes' => [
				'Перекрывает требования',
				'hint'=>'Какие еще требования будут удовлетворены выполнением этих требований',
				'indexHint'=>'{same}',
				'placeholder'=>'Никакие не перекрывает',
			],
			'includes_ids'=>['alias'=>'includes'],
			'includedBy' => [
				'Перекрывается требованиями',
				'hint'=>'Выполнением каких других требований удовлетворяются это требование',
				'indexHint'=>'{same}',
				'placeholder'=>'Никакими не перекрывается',
			],
			'included_ids'=>['alias'=>'includedBy'],
			'services'=>'Сервисы',
			'services_ids'=>['alias'=>'services'],
			'comps'=>'Операционные системы/ВМ',
			'comps_ids'=>['alias'=>'comps'],
			'techs'=>'Оборудование',
			'techs_ids'=>['alias'=>'techs'],
			'jobs'=>MaintenanceJobs::$titles
        ]);
    }
	
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
    public function getComps()
    {
        return $this->hasMany(Comps::class, ['id' => 'comps_id'])
			->viaTable('maintenance_reqs_in_comps', ['reqs_id' => 'id']);
    }
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
    public function getIncludes()
    {
        return $this->hasMany(MaintenanceReqs::class, ['id' => 'includes_id'])
			->viaTable('maintenance_reqs_in_reqs', ['reqs_id' => 'id']);
    }
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
    public function getJobs()
    {
        return $this->hasMany(MaintenanceJobs::class, ['id' => 'jobs_id'])
			->viaTable('maintenance_reqs_in_jobs', ['reqs_id' => 'id']);
    }
	
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
    public function getIncludedBy()
    {
        return $this->hasMany(MaintenanceReqs::class, ['id' => 'reqs_id'])
			->viaTable('maintenance_reqs_in_reqs', ['includes_id' => 'id']);
    }
	
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
    public function getServices()
    {
        return $this->hasMany(Services::class, ['id' => 'services_id'])
			->viaTable('maintenance_reqs_in_services', ['reqs_id' => 'id']);
    }
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
    public function getTechs()
    {
        return $this->hasMany(Techs::class, ['id' => 'techs_id'])
			->viaTable('maintenance_reqs_in_techs', ['reqs_id' => 'id']);
    }
	
	
	/*public function reverseLinks()
	{
		return [
			$this->services,
			$this->comps,
			$this->techs,
			$this->includes,
			$this->includedBy
		];
	}*/
	
}