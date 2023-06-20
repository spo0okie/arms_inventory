<?php

namespace app\models;

use Yii;
use yii\helpers\Html;
use yii\web\User;

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
	
	public static $title='доступ';
	public static $titles='доступы';
	
	public static $noAccessName='нет доступа';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'aces';
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
			['ips', function ($attribute, $params, $validator) {
				\app\models\NetIps::validateInput($this,$attribute);
			}],
			['ips', 'filter', 'filter' => function ($value) {
				return \app\models\NetIps::filterInput($value);
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
				\app\models\NetIps::$titles,
				'hint' => 'IP адреса с которых разрешается доступ<br>'.
					\app\models\NetIps::$inputHint
			],
			'comps_ids' => [
				'Компьютеры',
				'hint' => 'Сетевые имена компьютеров с которых разрешается доступ'
			],
			'access_types_ids' => \app\models\AccessTypes::$titles,
			'users_ids' => [
				\app\models\Users::$titles,
				'hint' => \app\models\Users::$titles.', которым предоставляется доступ<br>'.
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
	
	
	/**
	 * Name for search
	 * @return string
	 */
	public function getSname()
	{
		return strlen($this->comment)?
			$this->comment:
			'ACE#'.$this->id;
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
		return $this->hasMany(AccessTypes::className(), ['id' => 'access_types_id'])
			->viaTable('{{%access_in_aces}}', ['aces_id' => 'id']);
	}
	
	/**
	 * Типы доступа
	 */
	public function getAccessTypesUniq()
	{
		if (!is_array($this->accessTypes)) return[];
		$types=[];
		foreach ($this->accessTypes as $type)
			$types[$type->id]=$type;
		return $types;
	}
	
	/**
	 * Набор пользователей
	 */
	public function getUsersUniq()
	{
		if (!is_array($this->users)) return[];
		$users=[];
		foreach ($this->users as $user)
			$users[$user->id]=$user;
		return $users;
	}
	
	/**
	 * Набор пользователей
	 */
	public function getDepartments()
	{
		if (!is_array($this->usersUniq)) return[];
		$departments=[];
		foreach ($this->usersUniq as $user)
			if (is_object($department=$user->orgStruct))
				$departments[$department->id]=$department;
		return $departments;
	}
	
	public function getPartners() {
		if (!count($this->users_ids)) return [];
		$partners=[];
		foreach ($this->users as $user)
			$partners[$user->org_id]=$user->org;
		return $partners;
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
        return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
    }
	
	public function hasIpAccess(){
		foreach ($this->accessTypesUniq as $accessType) {
			if ($accessType->isIpRecursive) return true;
		}
		return false;
	}
	
	public function hasPhoneAccess(){
		foreach ($this->accessTypesUniq as $accessType) {
			if ($accessType->isTelephonyRecursive) return true;
		}
		return false;
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