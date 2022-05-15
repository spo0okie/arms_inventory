<?php

namespace app\models;

use Yii;
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
 * @property Comps[]	$comps
 * @property NetIps[]	$netIps
 * @property AccessTypes[] $accessTypes
 * @property int[]	$netIps_ids
 * @property int[]	$comps_ids
 * @property int[]	$users_ids
 * @property int[]	$access_types_ids
 */
class Aces extends \yii\db\ActiveRecord
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
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'acls_id' => 'ACL',
			'ips' => \app\models\NetIps::$titles,
			'ips_ids' => \app\models\NetIps::$titles,
			'comps_ids' => 'Компьютеры',
			'access_types_ids' => \app\models\AccessTypes::$titles,
			'users_ids' => \app\models\Users::$titles,
			'comment' => 'Прочее',
			'notepad' => 'Записная книжка',
		];
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			//'ips' => 'С которых предоставляется доступ',
			//'comps_ids' => 'С которых предоставляется доступ',
			//'users_ids' => 'Которым предоставляется доступ',
			'comment' => 'Все что не получилось описать через списки выше',
			'notepad' => 'Если есть какие-то заметки, то можно их записать здесь',
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
		return static::hasOne(Acls::className(), ['id' => 'acls_id']);
	}
	
	
	/**
	 * Привязанные пользователи
	 */
	public function getUsers()
	{
		return static::hasMany(Users::class, ['id' => 'users_id'])
			->from(['users_objects'=>Users::tableName()])
			->viaTable('{{%users_in_aces}}', ['aces_id' => 'id']);
	}
	
	/**
	 * Привязанные пользователи
	 */
	public function getComps()
	{
		return static::hasMany(Comps::className(), ['id' => 'comps_id'])
			->from(['comps_objects'=>Comps::tableName()])
			->viaTable('{{%comps_in_aces}}', ['aces_id' => 'id']);
	}
	
	/**
	 * Привязанные пользователи
	 */
	public function getNetIps()
	{
		return static::hasMany(NetIps::className(), ['id' => 'ips_id'])
			->from(['ips_objects'=>NetIps::tableName()])
			->viaTable('{{%ips_in_aces}}', ['aces_id' => 'id']);
	}
	
	/**
	 * Привязанные пользователи
	 */
	public function getAccessTypes()
	{
		return static::hasMany(AccessTypes::className(), ['id' => 'access_types_id'])
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
        return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
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