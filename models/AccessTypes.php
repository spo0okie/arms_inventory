<?php

namespace app\models;

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
 * @property boolean $isIpRecursive
 * @property boolean $isTelephonyRecursive
 * @property AccessTypes[] $children
 */
class AccessTypes extends ArmsModel
{

	public static $title='Тип доступа';
	public static $titles='Типы доступа';

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
			[['is_app','is_ip','is_phone','is_vpn'],'integer'],
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
	];
 
	
    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return [
            'id' => 'ID',
            'code' => [
            	'Код',
				'hint' => 'хз пока зачем. Еще нигде не используется'
			],
            'name' => [
            	'Название',
				'hint' => 'Желательно короткое, т.к. везде где употребляется обычно под него отводится мало места'
			],
            'comment' => [
            	'Комментарий',
				'hint' => 'Пояснение к названию, если из него не все ясно'
			],
            'notepad' => [
            	'Зап. книжка',
				'hint' => 'Тут можно вообще все в деталях описать если нужно',
				'type' => 'text'
			],
			'children_ids'=>[
				'Включает в себя',
				'hint' => 'Если это комплексный доступ/роль, который включает в себя другие, то их надо перечислить здесь'
			],
			//'children'=>['alias'=>'children_ids'],
			'is_app'=>[
				'Доступ на уровне приложения',
				'hint' => 'Подразумевает, что этот уровень доступа дает полномочия на уровне приложения'
			],
			'is_ip'=>[
				'Доступ по IP',
				'hint' => 'Подразумевает, что этот уровень доступа дает доступ на уровне IP (разрешения на фаерволе)'.
					'<br />При отображении выдачи такого доступа, будет дополнительно отображать IP адреса объектов'
			],
			'is_phone'=>[
				'Доступ уровня телефонии',
				'hint' => 'Подразумевает, что этот уровень доступа дает какие-то разрешения на уровне телефонии/диалплана'.
					'<br />При отображении выдачи такого доступа, будет дополнительно отображать внутренний телефон пользователя'
			],
			'is_vpn'=>[
				'Доступ через VPN',
				'hint' => 'Подразумевает что этот доступ предоставляет возможность удаленного VPN подключения'
			],
			'ip_params_def'=>[
				'Параметры IP по умолчанию',
				'hint'=>'Если это IP доступ, то какие порты каких IP протоколов он требует<br>'
					.'Например:<ul>'
					.'<li>TCP 443 <i>(для HTTPS)</li>'
					.'<li>UDP 5060,20000-20100 <i>(для SIP)</li>'
					.'<li>TCP,UDP 53<i>(для DNS)</li>'
					.'</ul> Для каждого конкретного предоставления доступа этот параметр может быть изменен. Здесь именно значение по умолчанию'
			]
        ];
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
	 * Name for search
	 * @return string
	 */
	public function getSname()
	{
		return $this->name;
	}
	
	public function getFlagRecursive($flag)
	{
		if ($this->$flag) return true;
		foreach ($this->children as $child) {
			if ($child->getFlagRecursive($flag)) return true;
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