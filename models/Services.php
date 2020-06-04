<?php

namespace app\models;

use Yii;
use yii\web\User;

/**
 * This is the model class for table "services".
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $is_end_user
 * @property int $user_group_id
 * @property int $sla_id
 * @property string $notebook
 * @property string $links
 * @property int $responsible_id
 * @property int $providing_schedule_id
 * @property int $support_schedule_id
 * @property int $segment_id
 *
 * @property \app\models\Comps[] $comps
 * @property \app\models\Services[] $depends
 * @property \app\models\Services[] $dependants
 * @property \app\models\UserGroups $userGroup
 * @property \app\models\Techs[] $techs
 * @property \app\models\Arms[] $arms
 * @property \app\models\Places[] $armPlaces
 * @property \app\models\Places[] $techPlaces
 * @property \app\models\Places[] $sites
 * @property Schedules $providingSchedule
 * @property Users $responsible
 * @property Users[] $support
 * @property Schedules $supportSchedule
 * @property Segments $segment
 */
class Services extends \yii\db\ActiveRecord
{

	public static $title='IT сервисы';


	/**
	 * В списке поведений прикручиваем many-to-many contracts
	 * @return array
	 */
	public function behaviors()
	{
		return [
			[
				'class' => \voskobovich\linker\LinkerBehavior::className(),
				'relations' => [
					'depends_ids' => 'depends',
					'comps_ids' => 'comps',
					'support_ids' => 'support',
					'techs_ids' => 'techs'
				]
			]
		];
	}


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'services';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'description', 'is_end_user'], 'required'],
	        [['depends_ids','comps_ids','support_ids','techs_ids'], 'each', 'rule'=>['integer']],
	        [['description', 'notebook','links'], 'string'],
	        [['is_end_user', 'responsible_id', 'providing_schedule_id', 'support_schedule_id'], 'integer'],
	        [['name'], 'string', 'max' => 64],
	        [['responsible_id'],        'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['responsible_id' => 'id']],
	        [['providing_schedule_id'], 'exist', 'skipOnError' => true, 'targetClass' => Schedules::className(), 'targetAttribute' => ['providing_schedule_id' => 'id']],
	        [['support_schedule_id'],   'exist', 'skipOnError' => true, 'targetClass' => Schedules::className(), 'targetAttribute' => ['support_schedule_id' => 'id']],
			[['segment_id'],			'exist', 'skipOnError' => true, 'targetClass' => Segments::className(), 'targetAttribute' => ['segment_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
	        'description' => 'Описание',
	        'links' => 'Ссылки',
            'is_end_user' => 'Предоставляется пользователям',
            'user_group_id' => 'Группа ответственных',
	        'depends_ids' => 'Зависит от сервисов',
			'comps_ids' => 'Серверы',
			'techs_ids' => 'Оборудование',
	        'providingSchedule' => 'Время предоставления',
	        'providing_schedule_id' => 'Время предоставления',
	        'supportSchedule' => 'Время поддержки',
	        'support_schedule_id' => 'Время поддержки',
	        'responsible' => 'Ответственный, поддержка',
	        'responsible_id' => 'Ответственный',
	        'support_ids' => 'Поддержка',
            'notebook' => 'Записная книжка',
			'segment_id' => 'Сегмент ИТ',
			'segment' => 'Сегмент ИТ',
			'arms' => 'Армы',
			'places' => 'Помещения',
			'sites' => 'Площадки',
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'ID',
			'name' => 'Короткое уникальное название сервиса',
			'description' => 'Развернутое название или краткое описание назначения этого сервиса. Все детали тут описывать не нужно. Нужно в поле ниже вставить ссылку на вики страничку с описанием',
			'links' => \app\components\UrlListWidget::$hint.' Нужно обязательно вставить ссылку на вики страничку описания и, если они есть, на странички входа на сервис и поддержки',
			'is_end_user' => 'Предоставляется ли этот сервис пользователям (иначе используется другими сервисами)',
			'user_group_id' => 'Группа сотрудников ответственных за работоспособность сервиса',
			'depends_ids' => 'От работы каких сервисов зависит работа этого сервиса',
			'comps_ids' => 'На каких серверах выполняется этот сервис',
			'techs_ids' => 'На каком оборудовании выполняется этот сервис',
			'sla_id' => 'Выбор соглашения о качестве предоставления сервиса',
			'notebook' => 'Устаревшее поле. Вся информация должна быть на вики страничке.',
			'support_schedule_id' => 'Расписание, когда нужно реагировать на сбои в работе сервиса',
			'providing_schedule_id' => 'Расписание, когда сервисом могут воспользоваться пользователи или другие сервисы',
			'responsible_id' => 'Ответственный за работу сервиса',
			'support_ids' => 'Дополнительные члены команды по поддержке сервиса',
			'segment_id' => 'Сегмент ИТ инфраструктуры к которому относится этот сервис',
		];
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getSupportSchedule()
	{
		return $this->hasOne(Schedules::className(), ['id' => 'support_schedule_id'])
			->from(['support_schedule'=>Schedules::tableName()]);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getProvidingSchedule()
	{
		return $this->hasOne(Schedules::className(), ['id' => 'providing_schedule_id'])
			->from(['providing_schedule'=>Schedules::tableName()]);
	}
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getResponsible()
	{
		return $this->hasOne(Users::className(), ['id' => 'responsible_id'])->from(['responsible'=>Users::tableName()]);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getSegment()
	{
		return $this->hasOne(Segments::className(), ['id' => 'segment_id']);
	}
	/**
	 * Возвращает сервисы от которых зависит этот сервис
	 */
	public function getDepends()
	{
		return static::hasMany(Services::className(), ['id' => 'depends_id'])
			->viaTable('{{%services_depends}}', ['service_id' => 'id']);
	}
	
	/**
	 * Возвращает сервисы от которых зависит этот сервис
	 */
	public function getSupport()
	{
		return static::hasMany(Users::className(), ['id' => 'user_id'])
			->from(['support'=>Users::tableName()])
			->viaTable('{{%users_in_services}}', ['service_id' => 'id']);
	}
	
	/**
	 * Возвращает сервисы зависимые от этого сервиса
	 */
	public function getDependants()
	{
		return static::hasMany(Services::className(), ['id' => 'service_id'])
			->from(['responsible'=>Services::tableName()])
			->viaTable('{{%services_depends}}', ['depends_id' => 'id']);
	}
	
	/**
	 * Возвращает серверы на которых живет этот сервис
	 */
	public function getComps()
	{
		return static::hasMany(Comps::className(), ['id' => 'comps_id'])
			->viaTable('{{%comps_in_services}}', ['services_id' => 'id']);
	}
	
	/**
	 * Возвращает серверы на которых живет этот сервис
	 */
	public function getTechs()
	{
		return static::hasMany(Techs::className(), ['id' => 'tech_id'])
			->viaTable('{{%techs_in_services}}', ['service_id' => 'id']);
	}
	
	/**
	 * Возвращает группу ответственных за сервис
	 */
	public function getUserGroup()
	{
		return static::hasOne(UserGroups::className(), ['id' => 'user_group_id']);
	}
	
	public function getArms()
	{
		return static::hasMany(Arms::className(), ['id' => 'arm_id'])
			->via('comps');
	}
	
	public function getArmPlaces()
	{
		return static::hasMany(Places::className(), ['id' => 'places_id'])
			->from(['arms_places'=>Places::tableName()])
			->via('arms');
	}
	
	public function getTechPlaces()
	{
		return static::hasMany(Places::className(), ['id' => 'places_id'])
			->from(['tech_places'=>Places::tableName()])
			->via('techs');
	}
	
	public function getSites(){
		$sites=[];
		foreach (array_merge($this->techPlaces,$this->armPlaces) as $place) {
			$site=$place->top;
			if (!array_key_exists($site->id,$sites))
				$sites[$site->id]=$site;
		}
		return $sites;
	}
	
	/**
	 * @return array
	 */
	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}
}
