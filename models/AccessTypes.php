<?php

namespace app\models;

use app\models\base\ArmsModel;
use stdClass;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "access_types".
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $comment
 * @property string $ip_params_def
 * @property string $notepad
 * @property boolean $is_app
 * @property boolean $is_ip
 * @property boolean $is_phone
 * @property boolean $is_vpn
 * @property boolean $is_forward
 * @property boolean $isForwardRecursive
 * @property boolean $isIpRecursive
 * @property boolean $isTelephonyRecursive
 * @property AccessTypes[] $children
 * @property int[] $default_services_ids
 * @property Services[] $defaultServices
 */
class AccessTypes extends ArmsModel
{

	public static $title='Тип доступа';
	public static $titles='Типы доступа';

	public static function modelDescription(): string
	{
		return 'Справочник типов доступа (например RDP, VPN, SSH), которыми описываются записи доступа.';
	}

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'access_types';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
			[['notepad','ip_params_def'], 'string'],
            [['code', 'name'], 'string', 'max' => 64],
			[['is_app','is_ip','is_phone','is_vpn','is_forward'],'integer'],
            [['comment'], 'string', 'max' => 255],
			[['children_ids'], 'each', 'rule'=>['integer']],
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public $linksSchema=[
		'children_ids' => [AccessTypes::class,'loader'=>'children'],
		'aces_ids' => [Aces::class,'access_types_ids'],
		'default_services_ids' => [Services::class,'default_access_types_ids'],
	];


    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return array_merge(parent::attributeData(),[
			'aces_ids' => [
				'ACEs',
				'hint' => 'Перечисляет ACEs, в которых используется этот тип доступа',

			],
			'default_services_ids' => [
				'Тип по умолчанию для сервисов',
				'hint' => 'Сервисы, у которых этот тип доступа указан как тип доступа по умолчанию',
				'typeClass' => \app\types\LinkType::class,
			],
            'name' => [
            	'Название',
				'hint' => 'Желательно короткое, т.к. везде где употребляется обычно под него отводится мало места',
				'viewHint' => 'Название типа доступа',
				'typeClass' => \app\types\StringType::class,
			],
            'comment' => [
            	'Комментарий',
				'hint' => 'Пояснение к названию, если из него не все ясно',
				'typeClass' => \app\types\StringType::class,
			],
            'notepad' => [
            	'Записная книжка',
				'hint' => 'Тут можно вообще все в деталях описать если нужно',
				'type' => 'text',
				'typeClass' => \app\types\TextType::class,
			],
			'children_ids'=>[
				'Включает в себя',
				'hint' => 'Если это комплексный доступ/роль, который включает в себя другие, то их надо перечислить здесь',
				'apiLabel'=>'Дочерние типы доступа',
				'apiHint'=>'Список ID типов доступа, которые включены в этот комплексный тип доступа',
				'typeClass' => \app\types\LinkType::class,
			],
			//'children'=>['alias'=>'children_ids'],
			'is_app'=>[
				'Доступ на уровне приложения',
				'hint' => 'Признак, что этот уровень доступа дает полномочия на уровне приложения',
				'typeClass' => \app\types\BooleanType::class,
			],
			'is_ip'=>[
				'Доступ по IP',
				'hint' => 'Признак, что этот уровень доступа дает доступ на уровне IP (разрешения на фаерволе)'.
					'<br />При отображении выдачи такого доступа, будет дополнительно отображать IP адреса объектов',
				'typeClass' => \app\types\BooleanType::class,
			],
			'is_phone'=>[
				'Доступ уровня телефонии',
				'hint' => 'Признак, что этот уровень доступа дает какие-то разрешения на уровне телефонии/диалплана'.
					'<br />При отображении выдачи такого доступа, будет дополнительно отображать внутренний телефон пользователя',
				'typeClass' => \app\types\BooleanType::class,
			],
			//собирательные атрибуты (рекурсия к дочерним типам доступа, не наследование)
			'isTelephonyRecursive'=>[
				'Телефония (включая дочерние)',
				'hint'=>'Дает ли этот тип доступа (или любой из включенных в него) разрешения уровня телефонии',
				'is_collectable'=>true,
				'typeClass' => \app\types\BooleanType::class,
			],
			'isIpRecursive'=>[
				'Доступ по IP (включая дочерние)',
				'hint'=>'Дает ли этот тип доступа (или любой из включенных в него) доступ на уровне IP',
				'is_collectable'=>true,
				'typeClass' => \app\types\BooleanType::class,
			],
			'is_vpn'=>[
				'Доступ через VPN',
				'hint' => 'Признак что этот доступ предоставляет возможность удаленного VPN подключения',
				'typeClass' => \app\types\BooleanType::class,
			],
			'is_forward'=>[
				'Проброс (форвард)',
				'hint' => 'Признак, что этот тип описывает проброс/транзит соединения (NAT, реверс-прокси, туннель), '
					.'а не доступ к самому ресурсу.<br>'
					.'В записи доступа с таким типом субъект — адрес входа (белый IP), ресурс — узел назначения '
					.'или его адрес, сетевые параметры — вида <b>TCP 443->8443</b> (порт входа -> порт назначения; '
					.'без стрелки порт не меняется).<br>'
					.'Такие записи показываются у узла, его адресов и DNS-имён как «доступен снаружи»',
				'typeClass' => \app\types\BooleanType::class,
			],
			'isForwardRecursive'=>[
				'Проброс (включая дочерние)',
				'hint'=>'Описывает ли этот тип доступа (или любой из включенных в него) проброс соединения',
				'is_collectable'=>true,
				'typeClass' => \app\types\BooleanType::class,
			],
			'ip_params_def'=>[
				'Параметры IP по умолчанию',
				'hint'=>'Если это IP доступ, то какие порты каких IP протоколов он требует<br>'
					.'Например:<ul>'
					.'<li>TCP 443 <i>(для HTTPS)</i></li>'
					.'<li>UDP 5060,20000-20100 <i>(для SIP)</i></li>'
					.'<li>TCP,UDP 53 <i>(для DNS)</i></li>'
					.'<li>TCP 443->8443 <i>(для проброса: порт входа -> порт назначения)</i></li>'
					.'</ul> Для каждого конкретного предоставления доступа этот параметр может быть изменен. Здесь именно значение по умолчанию',
				'example'=>'UDP 5060,20000-20100',
				'typeClass' => \app\types\StringType::class,
			]
        ]);
    }

	/**
	 * Возвращает набор контрагентов в договоре
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getChildren()
	{
		return $this->hasMany(AccessTypes::class, ['id' => 'child_id'])
			->viaTable('{{%access_types_hierarchy}}', ['parent_id' => 'id']);
	}

	public function getAces()
	{
		return $this->hasMany(Aces::class, ['id' => 'aces_id'])
			->viaTable('{{%access_in_aces}}', ['access_types_id' => 'id']);
	}

	/**
	 * Сервисы, у которых этот тип доступа — тип по умолчанию
	 * @return ActiveQuery
	 */
	public function getDefaultServices()
	{
		return $this->hasMany(Services::class, ['id' => 'services_id'])
			->viaTable('{{%default_access_in_services}}', ['access_types_id' => 'id']);
	}

	/**
	 * Name for search
	 * @return string
	 */
	public function getSname()
	{
		return $this->name;
	}

	/** @var null|array Кэш иерархии типов доступа: parent_id => [child_id,...] (грузится один раз на запрос) */
	private static $hierarchyCache=null;

	/**
	 * ID дочерних типов доступа из кэша иерархии
	 * (рекурсивные флаги дергаются на каждый ACE в списках - без кэша это запрос на каждый тип)
	 * @param int $id
	 * @return int[]
	 */
	protected static function childrenIdsCached($id)
	{
		if (is_null(static::$hierarchyCache)) {
			static::$hierarchyCache=[];
			foreach ((new \yii\db\Query())->from('access_types_hierarchy')->all() as $row)
				static::$hierarchyCache[$row['parent_id']][]=$row['child_id'];
		}
		return static::$hierarchyCache[$id]??[];
	}

	public function getFlagRecursive($flag)
	{
		if ($this->$flag) return true;
		foreach (static::childrenIdsCached($this->id) as $childId) {
			$child=static::getLoadedItem($childId,true);
			if (is_object($child) && $child->getFlagRecursive($flag)) return true;
		}
		return false;
	}

	public function getIsTelephonyRecursive()
	{
		return $this->getFlagRecursive('is_phone');
	}

	public function getIsIpRecursive()
	{
		return $this->getFlagRecursive('is_ip');
	}

	public function getIsForwardRecursive()
	{
		return $this->getFlagRecursive('is_forward');
	}

	/** @var null|int[] кэш ID форвард-типов (на запрос) */
	private static $forwardTypeIdsCache=null;

	/**
	 * ID всех типов доступа, описывающих проброс (сами с флагом is_forward либо
	 * включающие такой тип). Выборки пробросов узла/адреса дергаются на каждую
	 * карточку — список типов считаем один раз на запрос из общего кэша справочника.
	 * @return int[]
	 */
	public static function forwardTypeIds(): array
	{
		if (is_null(static::$forwardTypeIdsCache)) {
			static::$forwardTypeIdsCache=[];
			foreach (static::getAllItems(true) as $type) {
				/** @var AccessTypes $type */
				if ($type->isForwardRecursive) static::$forwardTypeIdsCache[]=(int)$type->id;
			}
		}
		return static::$forwardTypeIdsCache;
	}

	/**
	 * {@inheritdoc}
	 * Состав форвард-типов кэшируется статически — сбрасываем вместе с кэшем справочника
	 */
	public static function invalidateAllItemsCache()
	{
		static::$forwardTypeIdsCache=null;
		static::$hierarchyCache=null;
		parent::invalidateAllItemsCache();
	}

	/**
	 * Возвращает список всех элементов
	 * @return array|mixed|null
	 */
    public static function fetchNames(){
        $list= static::find()
            //->joinWith('some_join')
            //->select(['id','name'])
			->orderBy(['name'=>SORT_ASC])
            ->all();
        return ArrayHelper::map($list, 'id', 'sname');
    }

	/**
	 * Задача, получить на вход $accessTypes_ids выбранные в форме как список доступов в рамках ACE
	 * Вернуть список accessTypes в котором могут
	 *   - добавиться новые accessTypes (дочерние от выставленных явно)
	 *   - быть заблокированными от снятия (дочерние от выставленных явно)
	 * также нужно вернуть
	 *   - сетевые параметры по умолчанию
	 *   - имена типов доступов (т.к. в форме могут появиться новые пункты ввода сетевых параметров)
	 * формат ответа [
	 *   id1: {'optional':1,'default_param':'TCP 443','name':'HTTPS'},
	 *   id2: {'optional':0,'default_param':'UDP 5060','name':'SIP'},
	 *   ...
	 * ]
	 * @param $id
	 * @param $access_types_ids
	 */
	public static function bundleAccessTypes(array $access_types_ids) {
		$formData=[];
		foreach ($access_types_ids as $type_id) {
			$accessType=AccessTypes::getLoadedItem($type_id,true);
			/** @var AccessTypes $accessType */
			if (!isset($formData[$type_id])) {
				static::addTypeInBundle($formData,$accessType);
				$formData[$type_id]->optional=1;
			}

			foreach ($accessType->children as $child) {
				static::addTypeInBundle($formData,$child);
				$formData[$child->id]->optional=0;
			}
		}
		return $formData;
	}

	/**
	 * Добавляет в массив types тип type для вывода методом выше
	 * @param $types
	 * @param $type
	 */
	public static function addTypeInBundle(array &$types, AccessTypes $type) {
		if (!isset($types[$type->id])) {
			$types[$type->id]=new stdClass();
			$types[$type->id]->name=$type->name;
			if ($type->is_ip) {
				$types[$type->id]->is_ip=1;
				if(!empty($param=$type->ip_params_def)) {
					$types[$type->id]->default_param=$param;
				}
			}
		}
	}
}
