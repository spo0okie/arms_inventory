<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "user_groups".
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property string $notebook
 * @property string $ad_group
 * @property string $sync_time
 * @property \app\models\Users $users
 * @property \app\models\Services $services
 */
class UserGroups extends \yii\db\ActiveRecord
{

	public static $title='Группы пользователей';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user_groups';
    }


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
					'users_ids' => 'users',
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
            [['name', 'description'], 'required'],
	        [['users_ids'], 'each', 'rule'=>['string']],
            [['description', 'notebook'], 'string'],
            [['sync_time'], 'safe'],
            [['name'], 'string', 'max' => 64],
            [['ad_group'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Имя группы',
            'description' => 'Описание группы',
	        'users_ids' => 'Члены группы',
	        'notebook' => 'Записная книжка',
            'ad_group' => 'Синхронизация с AD',
            'sync_time' => 'Время последней синхронизации',
        ];
    }


	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'Идентификатор, только для чтения. Назначается автоматически',
			'name' => 'Короткое имя для обозначения группы',
			'description' => 'Развернутое объяснение назначения этой группы',
			'users_ids' => 'Список сотрудников входящих в эту группу',
			'notebook' => 'Все что нужно записать по этой группе кроме заданных отдельными полями параметров',
			'ad_group' => 'Имя группы в AD с которой синхронизировать состав этой группы (скрипт работает на стороне AD c этой БД через REST API). Если пусто - синхронизации не будет',
			'sync_time' => 'Параметр только для чтения. Показывает когда производилась последняя синхронизация с AD',
		];
	}


	/**
	 * Возвращает членов группы
	 */
	public function getUsers()
	{
		return $this->hasMany(Users::className(), ['id' => 'users_id'])
			->viaTable('{{%users_in_groups}}', ['groups_id' => 'id']);
	}

	/**
	 * Возвращает обслуживаемые сервисы
	 */
	public function getServices()
	{
		return $this->hasMany(Services::className(), ['user_group_id'=>'id']);
	}


	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}

}
