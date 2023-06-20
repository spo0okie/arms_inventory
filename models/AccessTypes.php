<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "access_types".
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $comment
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
            [['notepad'], 'string'],
            [['code', 'name'], 'string', 'max' => 64],
			[['is_app','is_ip','is_phone','is_vpn'],'integer'],
            [['comment'], 'string', 'max' => 255],
			[['children_ids'], 'each', 'rule'=>['integer']],
        ];
    }
	
	/**
	 * В списке поведений прикручиваем many-to-many контрагентов
	 * @return array
	 */
	public function behaviors()
	{
		return [
			[
				'class' => \voskobovich\linker\LinkerBehavior::className(),
				'relations' => [
					'children_ids' => 'children',
				]
			]
		];
	}
    
    
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
				'hint' => 'Тут можно вообще все в деталях описать если нужно'
			],
			'children_ids'=>[
				'Включает в себя',
				'hint' => 'Если это комплексный доступ/роль, который включает в себя другие, то их надо перечислить здесь'
			],
			'children'=>['alias'=>'children_ids'],
			'is_app'=>[
				'Доступ в приложение',
				'hint' => 'Подразумевает, что этот уровень доступа дает полномочия на уровне приложения'
			],
			'is_ip'=>[
				'Доступ по IP',
				'hint' => 'Подразумевает, что этот уровень доступа дает доступ на уровне IP (разрешения на фаерволе)'.
					'<br />При отображении выдачи такого доступа, будет дополнительно отображать IP адреса объектов'
			],
			'is_phone'=>[
				'Доступ телефонии',
				'hint' => 'Подразумевает, что этот уровень доступа дает какие-то разрешения на уровне телефонии/диалплана'.
					'<br />При отображении выдачи такого доступа, будет дополнительно отображать внутренний телефон пользователя'
			],
			'is_vpn'=>[
				'Доступ через VPN',
				'hint' => 'Подразумевает что этот доступ предоставляет возможность удаленного VPN подключения'
			],
        ];
    }
	
	/**
	 * Возвращает набор контрагентов в договоре
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getChildren()
	{
		return $this->hasMany(AccessTypes::className(), ['id' => 'child_id'])
			->viaTable('{{%access_types_hierarchy}}', ['parent_id' => 'id']);
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
        return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
    }
}