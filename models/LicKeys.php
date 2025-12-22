<?php

namespace app\models;


use app\models\links\LicLinks;
use app\models\traits\LicKeysModelCalcFieldsTrait;
use voskobovich\linker\updaters\ManyToManySmartUpdater;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "lic_keys".
 *
 * @property int $id id
 * @property int      $lic_items_id Закупка
 * @property array    $softIds Ссылка на софт
 * @property array    $arms_ids
 * @property array    $comps_ids Ссылка на ОСи
 * @property array    $users_ids Ссылка на пользователей
 * @property string   $key_text Ключ
 * @property string   $keyShort начало и конец ключа (чтобы не палить везде ключ целиком)
 * @property string   $comment Допольнительно
 * @property string   $sname
 * @property string   $dname
 * @property LicItems $licItem закупка
 * @property Techs[]  $arms АРМы
 * @property Comps[]  $comps
 * @property Users[]  $users
 */
class LicKeys extends ArmsModel
{
	use LicKeysModelCalcFieldsTrait;
	
	public static $title='Лиц. ключи';
	public static $titles='Лиц. ключи';
	
	public $linkComment=null; //комментарий, добавляемый при привязке лицензий
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'lic_keys';
    }

	/**
	 * В списке поведений прикручиваем many-to-many contracts
	 * @return array
	 */
	public function getLinksSchema()
	{
		$model=$this;
		return [
			'lic_items_id' => [LicItems::class,'lic_keys_ids'],
			'arms_ids' => [Techs::class,'lic_keys_ids', 'updater' => [
				'class' => ManyToManySmartUpdater::class,
				'viaTableAttributesValue' => LicLinks::fieldsBehaviour($model),
			]],
			'comps_ids' => [Comps::class,'lic_keys_ids', 'updater' => [
				'class' => ManyToManySmartUpdater::class,
				'viaTableAttributesValue' => LicLinks::fieldsBehaviour($model),
			]],
			'users_ids' => [Users::class,'lic_keys_ids', 'updater' => [
				'class' => ManyToManySmartUpdater::class,
				'viaTableAttributesValue' => LicLinks::fieldsBehaviour($model),
			]],
		];
	}


    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['lic_items_id', 'key_text'], 'required'],
            [['lic_items_id'], 'integer'],
	        [['arms_ids','comps_ids','users_ids'], 'each', 'rule'=>['integer']],
            [['key_text', 'comment','linkComment'], 'string'],
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return [
			'comment' => [
				'Комментарии',
				'comment' => 'Все что стоит знать об этом ключе кроме информации в остальных полях',
				'type' => 'text'
			],
			'lic_items_id' => [
				'Закупка',
				'hint' => 'К какой закупке лицензий относятся эти ключи. Тут надо внимательно отнестись, чтобы не вносить путаницу.',
				'placeholder' => 'Выберите закупку',
			],
			'lic_item' => ['alias'=>'lic_items_id',	],
			'arms_ids' => [
				'Привязанные АРМ(ы)',
				'hint' => 'К какому рабочему ПК привязан ключ',
				'placeholder' => 'Ключ не привязан к АРМ',
			],
			'comps_ids' => [
				'Привязанные ОС/ВМ',
				'hint' => 'К какой операционной системе привязан ключ',
				'placeholder' => 'Ключ не привязан к ОС/ВМ',
			],
			'users_ids' => [
				'Привязанный(е) пользователь(и)',
				'hint' => 'К какому пользователю(пользователям) привязан ключ',
				'placeholder' => 'Ключ не привязан к пользователям',
			],
			'links' => [
				'Привязки',
			],
			'key_text' => [
				'Ключ',
				'hint' => 'Текст ключа / серийный номер / чтобы то ни было, что используется для активации продукта',
				'placeholder' => 'Введите ключ',
			],
			'linkComment' => [
				'Пояснение к добавляемым привязкам',
				'hint' => 'На каком основании эти лицензии закрепляются за добавленными выше объектами. Чтобы спустя время не было вопросов, а кто и зачем эту лицензию туда выделил (уже существующие привязки не меняются, только новые)',
			],
		];
	}
	
	
	/**
	 * Возвращает АРМы, к которым может быть привязан ключ
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getArms()
	{
		return $this->hasMany(Techs::class, ['id' => 'arms_id'])
			->viaTable('{{%lic_keys_in_arms}}', ['lic_keys_id' => 'id']);
	}
	
	
	/**
	 * Возвращает АРМы, к которым может быть привязан ключ
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getComps()
	{
		return $this->hasMany(Comps::class, ['id' => 'comps_id'])
			->viaTable('{{%lic_keys_in_comps}}', ['lic_keys_id' => 'id']);
	}
	
	
	/**
	 * Возвращает АРМы, к которым может быть привязан ключ
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getUsers()
	{
		return $this->hasMany(Users::class, ['id' => 'users_id'])
			->viaTable('{{%lic_keys_in_users}}', ['lic_keys_id' => 'id']);
	}
	
	
	/**
	 * @return ActiveQuery
	 */
	public function getLicItem()
	{
		return $this->hasOne(LicItems::class, ['id' => 'lic_items_id']);
	}



	
	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			//fix: https://github.com/spo0okie/arms_inventory/issues/16
			//если привязаны к АРМ,
			//и есть licItem (а как его может не быть вы спросите, а так: если мы делаем clearReverseLinks()
			if (is_array($this->arms_ids)&&count($this->arms_ids)&&($this->licItem)) {
				//то ищем эти АРМ в закупке и группе
				if (count($licArms=array_intersect($this->arms_ids,$this->licItem->arms_ids??[]))) {
					$this->licItem->arms_ids=array_diff($this->licItem->arms_ids,$licArms);
					$this->licItem->save();
				}
				if (count($groupArms=array_intersect($this->arms_ids,$this->licItem->licGroup->arms_ids))) {
					$this->licItem->licGroup->arms_ids=array_diff($this->licItem->licGroup->arms_ids,$groupArms);
					$this->licItem->licGroup->save();
				}
			}
			
			return true;
		}
		return false;
	}
}
