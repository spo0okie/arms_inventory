<?php

namespace app\models;

use app\models\traits\AcesModelCalcFieldsTrait;
use Yii;
use app\helpers\ArrayHelper;
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
 * @property Departments[]	$departments
 * @property Comps[]	$comps
 * @property NetIps[]	$netIps
 * @property AccessTypes[] $accessTypes
 * @property Partners[] $partners
 * @property Networks[] $networks
 * @property Services[] $services
 * @property int[]	$netIps_ids
 * @property int[]	$comps_ids
 * @property int[]	$users_ids
 * @property int[]	$services_ids
 * @property int[]	$networks_ids
 * @property int[]	$access_types_ids
 */
class Aces extends ArmsModel
{
	use AcesModelCalcFieldsTrait;
	
	public static $title='Доступ';
	public static $titles='Доступы';
	
	public static $noAccessName='нет доступа';
	
	public $ipParamsStorage;	//ip параметры доступа
	
	/**
	 * {@inheritdoc}
	 */
	public $linksSchema=[
		'access_types_ids' => [AccessTypes::class,'aces_ids'],
		'comps_ids' =>		[Comps::class,'aces_ids'],
		'users_ids' =>		[Users::class,'aces_ids'],
		'services_ids' =>	[Services::class,'aces_ids'],
		'networks_ids' =>	[Networks::class,'aces_ids'],
		'netIps_ids' =>		[NetIps::class,'aces_ids'],
		'acls_id' =>		[Acls::class,'aces_ids'],
	];
	
	public function getLinksSchema()
	{
		//дополняем нашу статичную схему связей апдейтером для параметров типов доступа
		return ArrayHelper::recursiveOverride($this->linksSchema,[
			'access_types_ids' => ['updater'=>[
				'viaTableAttributesValue' => [
					'ip_params' => function($updater, $relatedPk) {
						$ace = $updater->getBehavior()->owner;
						/** @var Aces $ace */
						return $ace->getIpParams()[$relatedPk]??null;
					},
				]
			]],
		]);
	}
	
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
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['acls_id'], 'integer'],
			[['comps_ids','users_ids','access_types_ids','netIps_ids','services_ids','networks_ids'], 'each', 'rule'=>['integer']],
			[['ipParams'], 'each', 'rule'=>['string']],
            [['ips', 'notepad','name'], 'string'],
            [['comment'], 'string', 'max' => 255],
			['ips', function ($attribute) {
				Networks::validateInput($this,$attribute);
			}],
			['ips', 'filter', 'filter' => function ($value) {
				return NetIps::filterInput($value);
			}],
			[['services_ids', 'ips', 'comps_ids', 'users_ids', 'comment'],
				'validateRequireOneOf',
				'skipOnEmpty' => false,
				'params'=>['attrs'=>['services_ids', 'ips', 'comps_ids', 'users_ids', 'comment']]
			]
        ];
    }
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return [
			'acls_id' => 'ACL',
			'name' => [
				'Пояснение',
				'hint'=>'С какой целью у этого объекта доступ к этому ресурсу<br>'
					.'<i>Например:</i><ul>'
					.'<li>Забирает список пользователей по WEB-API <i>(про доступ одного сервиса к другому)</i></li>'
					.'<li>Подключается к своему АРМ <i>(про доступ пользователя к ОС)</i></li>'
					.'<li>Отправляет уведомления по почту <i>(про доступ одного сервиса к другому по SMTP)</i></li>'
					.'</ul>'
			],
			'ips' => [
				'IP адреса и сети',
				'hint' => 'IP адреса и сети из которых разрешается доступ<br>'
					.'По одному в строке. Если добавляется доступ из сети, то она уже должна быть заведена<br>'
					.'Для обозначения сетей обязательна маска, например 192.168.1.0/24<br>'
					.'Для обозначения адресов маска должна отсутствовать, например 192.168.1.1'
			],
			'comps_ids' => [
				'Компьютеры',
				'hint' => 'Сетевые имена компьютеров с которых разрешается доступ'
			],
			'access_types_ids' => [
				AccessTypes::$titles,
				'indexHint'=>'Какой доступ субъекты получают к ресурсам'
			],
			'access_types'=>['alias'=>'access_types_ids'],
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
			],
			'subjects' => [
				'Субъекты',
				'indexHint' => 'Субъекты доступа: кто получает доступ',
			],
			'subject_nodes' => [
				'Узлы субъектов',
				'indexHint' => 'Какие узлы субъектов получают доступ:<br>'
					.'В случае если доступ предоставляется сервису, то<br>'
					.'он автоматически предоставляется узлам, на которых сервис крутится',
			],
			'resource' => [
				'Ресурс',
				'indexHint' => 'К какому ресурсу субъект получает доступ',
			],
			'resource_nodes' => [
				'Узлы ресурса',
				'indexHint' => 'К каким узлам ресурса получают доступ субъекты:<br>'
					.'В случае если доступ предоставляется к сервису, то<br>'
					.'он автоматически предоставляется и к узлам, на которых сервис крутится',
			],
			'network_hosts' => [
				'Узлы сети',
				'indexHint' => 'К каким узлам ресурса в этой сети получают доступ субъекты:<br>'
					.'В случае если доступ предоставляется к сервису, то<br>'
					.'он автоматически предоставляется и к узлам, на которых сервис крутится',
			],
			'schedule'=>[
				'Временное ограничение',
				'Наименование временного доступа в рамках которого действет эта ACE (запись доступа)'
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
			->from(['users_subjects'=>Users::tableName()])
			->viaTable('{{%users_in_aces}}', ['aces_id' => 'id']);
	}
	
	/**
	 * Привязанные сервисы
	 */
	public function getServices()
	{
		return $this->hasMany(Services::class, ['id' => 'services_id'])
			->from(['services_subjects'=>Services::tableName()])
			->viaTable('{{%services_in_aces}}', ['aces_id' => 'id']);
	}
	
	/**
	 * Привязанные сети
	 */
	public function getNetworks()
	{
		return $this->hasMany(Networks::class, ['id' => 'networks_id'])
			->from(['networks_subjects'=>Networks::tableName()])
			->viaTable('{{%networks_in_aces}}', ['aces_id' => 'id']);
	}
	
	/**
	 * Привязанные пользователи
	 */
	public function getComps()
	{
		return $this->hasMany(Comps::class, ['id' => 'comps_id'])
			->from(['comps_subjects'=>Comps::tableName()])
			->viaTable('{{%comps_in_aces}}', ['aces_id' => 'id']);
	}
	
	/**
	 * Привязанные пользователи
	 */
	public function getNetIps()
	{
		return $this->hasMany(NetIps::class, ['id' => 'ips_id'])
			->from(['ips_subjects'=>NetIps::tableName()])
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
			$this->netIps_ids=NetIps::fetchIpIds($this->ips,true);
			$this->networks_ids=Networks::fetchNetworkIds($this->ips);
			
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
	
	/**
	 * Получить IP параметры доступов
	 */
	public function getIpParams() {
		if ($this->isNewRecord) return [];
		if (isset($this->attrsCache['ipParams'])) return $this->attrsCache['ipParams'];
		$types= ArrayHelper::index($this->accessTypes,'id');
		$query=Yii::$app->db->createCommand("select * from access_in_aces where aces_id={$this->id}");
		$data=$query->queryAll();
		$params=[];
		foreach ($data as $row) {
			$ip_params=(string)$row['ip_params'];
			$type_id=$row['access_types_id'];
			if (isset($types[$type_id])) {
				$type=$types[$type_id];
				if ($type->is_ip) {
					$params[$type_id]=$ip_params;
				}
			}
		}
		
		return $this->attrsCache['ipParams']=$params;
	}
	
	public function setIpParams($value) {
		$value=ArrayHelper::recursiveOverride($this->getIpParams(),$value);
		$this->attrsCache['ipParams']=$value;
	}
}