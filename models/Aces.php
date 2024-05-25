<?php

namespace app\models;

use app\models\traits\AcesModelCalcFieldsTrait;
use voskobovich\linker\LinkerBehavior;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * This is the model class for table "aces".
 *
 * @property int $id
 * @property int $acls_id
 * @property string $ips
 * @property string $comment
 * @property string $notepad
 * @property string $sname
 * @property Acls	$acl
 * @property Users[]	$users
 * @property Users[]	$usersUniq
 * @property Departments[]	$departments
 * @property Comps[]	$comps
 * @property NetIps[]	$netIps
 * @property AccessTypes[] $accessTypes
 * @property AccessTypes[] $accessTypesUniq
 * @property Partners[] $partners
 * @property int[]	$netIps_ids
 * @property int[]	$comps_ids
 * @property int[]	$users_ids
 * @property int[]	$access_types_ids
 */
class Aces extends ArmsModel
{
	use AcesModelCalcFieldsTrait;
	
	public static $title='доступ';
	public static $titles='доступы';
	
	public static $noAccessName='нет доступа';
	
	/**
	 * {@inheritdoc}
	 */
	public $linksSchema=[
		'access_types_ids' => AccessTypes::class,
		'comps_ids' =>	[Comps::class,'aces_ids'],
		'users_ids' =>	[Users::class,'aces_ids'],
		'acls_id' =>	[Acls::class,'aces_ids'],
	];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'aces';
    }
	
	public function extraFields()
	{
		return array_merge(parent::extraFields(),[
			'accessTypes',
			'users',
			'acl',
		]);
	}
	
	
	/**
	 * В списке поведений прикручиваем many-to-many ссылки
	 * @return array
	 */
	public function behaviors()
	{
		return [
			[
				'class' => LinkerBehavior::class,
				'relations' => [
					'users_ids' => 'users',
					'comps_ids' => 'comps',
					'netIps_ids' => 'netIps',
					'access_types_ids' => 'accessTypes',
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
            [['acls_id'], 'integer'],
			[['comps_ids','users_ids','access_types_ids','netIps_ids'], 'each', 'rule'=>['integer']],
            [['ips', 'notepad'], 'string'],
            [['comment'], 'string', 'max' => 255],
			['ips', function ($attribute) {
				NetIps::validateInput($this,$attribute);
			}],
			['ips', 'filter', 'filter' => function ($value) {
				return NetIps::filterInput($value);
			}],
        ];
    }
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return [
			'id' => 'ID',
			'acls_id' => 'ACL',
			'ips' => [
				NetIps::$titles,
				'hint' => 'IP адреса с которых разрешается доступ<br>'.
					NetIps::$inputHint
			],
			'comps_ids' => [
				'Компьютеры',
				'hint' => 'Сетевые имена компьютеров с которых разрешается доступ'
			],
			'access_types_ids' => AccessTypes::$titles,
			'users_ids' => [
				Users::$titles,
				'hint' => Users::$titles.', которым предоставляется доступ<br>'.
					'Сотрудников других организаций можно также добавить в '.Html::a('список пользователей',['/users/index'])
			],
			'comment' => [
				'Прочее',
				'hint' => 'Если есть какие-то объекты, предоставления доступа которым не получается учесть через поля выше,<br> вписываем их текстом сюда',
			],
			'notepad' => [
				'Записная книжка',
				'hint' => 'Если есть какие-то заметки, то можно их записать здесь',
			]
		];
	}
	
	
	
	public function getAcl()
	{
		return $this->hasOne(Acls::class, ['id' => 'acls_id']);
	}
	
	
	/**
	 * Привязанные пользователи
	 */
	public function getUsers()
	{
		return $this->hasMany(Users::class, ['id' => 'users_id'])
			->from(['users_objects'=>Users::tableName()])
			->viaTable('{{%users_in_aces}}', ['aces_id' => 'id']);
	}
	
	/**
	 * Привязанные пользователи
	 */
	public function getComps()
	{
		return $this->hasMany(Comps::class, ['id' => 'comps_id'])
			->from(['comps_objects'=>Comps::tableName()])
			->viaTable('{{%comps_in_aces}}', ['aces_id' => 'id']);
	}
	
	/**
	 * Привязанные пользователи
	 */
	public function getNetIps()
	{
		return $this->hasMany(NetIps::class, ['id' => 'ips_id'])
			->from(['ips_objects'=>NetIps::tableName()])
			->viaTable('{{%ips_in_aces}}', ['aces_id' => 'id']);
	}
	
	/**
	 * Типы доступа
	 */
	public function getAccessTypes()
	{
		return $this->hasMany(AccessTypes::class, ['id' => 'access_types_id'])
			->viaTable('{{%access_in_aces}}', ['aces_id' => 'id']);
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

	
	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			
			/* взаимодействие с NetIPs */
			$this->netIps_ids=NetIps::fetchIpIds($this->ips);
			
			//грузим старые значения записи
			$old=static::findOne($this->id);
			if (!is_null($old)) {
				//находим все IP адреса которые от этой ОС отвалились
				$removed = array_diff($old->netIps_ids, $this->netIps_ids);
				//если есть отвязанные от это ос адреса
				if (count($removed)) foreach ($removed as $id) {
					//если он есть в БД
					if (is_object($ip=NetIps::findOne($id))) $ip->detachAce($this->id);
				}
			}
			return true;
		}
		return false;
	}
	
	
	/**
	 * @inheritdoc
	 */
	public function beforeDelete()
	{
		if (!parent::beforeDelete()) {
			return false;
		}
		
		//отрываем IP от удаляемого компа
		foreach ($this->netIps as $ip) {
			$ip->detachAce($this->id);
		}
		
		return true;
	}
}