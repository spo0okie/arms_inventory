<?php

namespace app\models;


/**
 * This is the model class for table "lic_keys".
 *
 * @property int $id id
 * @property int $lic_items_id Закупка
 * @property array $softIds Ссылка на софт
 * @property array                $arms_ids
 * @property array                $comps_ids Ссылка на ОСи
 * @property array                $users_ids Ссылка на пользователей
 * @property string               $key_text Ключ
 * @property string               $keyShort начало и конец ключа (чтобы не палить везде ключ целиком)
 * @property string               $comment Допольнительно
 * @property string               $sname
 * @property string               $dname
 * @property \app\models\LicItems $licItem закупка
 * @property Techs[]	          $arms АРМы
 * @property Comps[]              $comps
 * @property Users[]              $users
 */
class LicKeys extends ArmsModel
{

	public static $title='Лиц. ключи';
	
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
	public function behaviors()
	{
		$model=$this;
		return [
			[
				'class' => \voskobovich\linker\LinkerBehavior::className(),
				'relations' => [
					'arms_ids' => [
						'arms',
						'updater' => [
							'class' => \voskobovich\linker\updaters\ManyToManySmartUpdater::className(),
							'viaTableAttributesValue' => \app\models\links\LicLinks::fieldsBehaviour($model),
						],
					],
					'comps_ids' => [
						'comps',
						'updater' => [
							'class' => \voskobovich\linker\updaters\ManyToManySmartUpdater::className(),
							'viaTableAttributesValue' => \app\models\links\LicLinks::fieldsBehaviour($model),
						],
					],
					'users_ids' => [
						'users',
						'updater' => [
							'class' => \voskobovich\linker\updaters\ManyToManySmartUpdater::className(),
							'viaTableAttributesValue' => \app\models\links\LicLinks::fieldsBehaviour($model),
						],
					],
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
			'id' => 'ID',
			'lic_items_id' => [
				'Закупка',
				'hint' => 'К какой закупке лицензий относятся эти ключи. Тут надо внимательно отнестись, чтобы не вносить путанницу.',
			],
			'lic_item' => ['alias'=>'lic_items_id',	],
			'arms_ids' => [
				'Привязанный(е) АРМ(ы)',
				'hint' => 'К какому рабочему ПК привязан ключ',
			],
			'comps_ids' => [
				'Привязанная(ые) ОС(и)',
				'hint' => 'К какой операционной системе привязан ключ',
			],
			'users_ids' => [
				'Привязанный(е) пользователь(и)',
				'hint' => 'К какому пользователю(пользователям) привязан ключ',
			],
			'links' => [
				'Привязки',
			],
			'key_text' => [
				'Ключ',
				'hint' => 'Текст ключа / серийный номер / чтобы то ни было, что используется для активации продукта',
			],
			'comment' => [
				'Комментарии',
				'comment' => 'Все что стоит знать об этом ключе кроме информации в остальных полях',
			],
			'linkComment' => [
				'Пояснение к добавляемым привязкам',
				'hint' => 'На каком основании эти лицензии закрепляются за добавленными выше объектами. Чтобы спустя время не было вопросов, а кто и зачем эту лицензию туда выделил (уже существующие привязки не меняются, только новые)',
			],
		];
	}
	
	
	/**
	 * Возвращает АРМы, к которым может быть привязан ключ
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getArms()
	{
		return $this->hasMany(Techs::className(), ['id' => 'arms_id'])
			->viaTable('{{%lic_keys_in_arms}}', ['lic_keys_id' => 'id']);
	}
	
	
	/**
	 * Возвращает АРМы, к которым может быть привязан ключ
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getComps()
	{
		return $this->hasMany(Comps::className(), ['id' => 'comps_id'])
			->viaTable('{{%lic_keys_in_comps}}', ['lic_keys_id' => 'id']);
	}
	
	
	/**
	 * Возвращает АРМы, к которым может быть привязан ключ
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getUsers()
	{
		return $this->hasMany(Users::className(), ['id' => 'users_id'])
			->viaTable('{{%lic_keys_in_users}}', ['lic_keys_id' => 'id']);
	}
	
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getLicItem()
	{
		return $this->hasOne(LicItems::className(), ['id' => 'lic_items_id']);
	}


	/**
	 *
	 */
	public function getKeyShort(){
		if (strlen($this->key_text)<=10) return $this->key_text;
		return substr($this->key_text,0,5).' ... '.substr($this->key_text,-5,5);
	}
	
	/**
	 * Search name
	 * @return string
	 */
	public function getSname()
	{
		return $this->licItem->licGroup->descr.' /'.$this->licItem->fullDescr.' /'.$this->keyShort;
	}
	
	/**
	 * Display name
	 * @return string
	 */
	public function getDname()
	{
		return $this->licItem->licGroup->descr.' /'.$this->licItem->descr.' /'.$this->keyShort;
	}
	
	public function getSoftIds()
	{
		return $this->licItem->softIds;
	}
	
	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			//fix: https://github.com/spo0okie/arms_inventory/issues/16
			//если привязаны к АРМ,
			if (is_array($this->arms_ids)&&count($this->arms_ids)) {
				//то ищем эти АРМ в закупке и группе
				if (count($licArms=array_intersect($this->arms_ids,$this->licItem->arms_ids))) {
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
