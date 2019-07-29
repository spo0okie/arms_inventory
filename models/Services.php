<?php

namespace app\models;

use Yii;

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
 * @property \app\models\Comps $comps
 * @property \app\models\Services $depends
 * @property \app\models\Services $dependants
 * @property \app\models\UserGroups $userGroup
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
	        [['depends_ids','comps_ids'], 'each', 'rule'=>['integer']],
	        [['description', 'notebook','links'], 'string'],
            [['is_end_user', 'user_group_id', 'sla_id'], 'integer'],
            [['name'], 'string', 'max' => 64],
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
            'sla_id' => 'SLA',
            'notebook' => 'Записная книжка',
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'ID',
			'name' => 'Короткое название сервиса',
			'description' => 'Развернутое описание назначения этого сервиса',
			'links' => \app\components\UrlListWidget::$hint,
			'is_end_user' => 'Предоставляется ли этот сервис пользователям (иначе используется другими сервисами)',
			'user_group_id' => 'Группа сотрудников ответственных за работоспособность сервиса',
			'depends_ids' => 'От работы каких сервисов зависит работа этого сервиса',
			'comps_ids' => 'На каких серверах выполняется этот сервис',
			'sla_id' => 'Выбор соглашения о качестве предоставления сервиса',
			'notebook' => 'Записная книжка',
		];
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
	 * Возвращает сервисы зависимые от этого сервиса
	 */
	public function getDependants()
	{
		return static::hasMany(Services::className(), ['id' => 'service_id'])
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
	 * Возвращает группу ответственных за сервис
	 */
	public function getUserGroup()
	{
		return static::hasOne(UserGroups::className(), ['id' => 'user_group_id']);
	}


	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}
}
