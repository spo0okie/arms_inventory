<?php

namespace app\models;


/**
 * This is the model class for table "lic_keys".
 *
 * @property int $id id
 * @property int $lic_items_id Закупка
 * @property array $arms_ids
 * @property string $key_text Ключ
 * @property string $keyShort начало и конец ключа (чтобы не палить везде ключ целиком)
 * @property string $comment Допольнительно

 * @property \app\models\LicItems $licItem закупка
 * @property \app\models\Arms[] $arms АРМы
 */
class LicKeys extends \yii\db\ActiveRecord
{

	public static $title='Лиц. ключи';

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
		return [
			[
				'class' => \voskobovich\linker\LinkerBehavior::className(),
				'relations' => [
					'arms_ids' => 'arms',
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
	        [['arms_ids'], 'each', 'rule'=>['integer']],
            [['key_text', 'comment'], 'string'],
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'lic_items_id' => 'Закупка лицензий',
			'arms_ids' => 'Привязанный(е) АРМ(ы)',
			'key_text' => 'Ключ',
			'lic_item' => 'Группа/Закупка',
			'comment' => 'Комментарии',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'ID',
			'lic_items_id' => 'К какой закупке лицензий относятся эти ключи. Тут надо внимательно отнестись, чтобы не вносить путанницу.',
			'arms_ids' => 'Привязанный(е) АРМ(ы)',
			'key_text' => 'Текст ключа / серийный номер / чтобы то ни было, что используется для активации продукта',
			'comment' => 'Все что стоит знать об этом ключе кроме информации в остальных полях',
		];
	}


	/**
	 * Возвращает АРМы, к которым может быть привязан ключ
	 * @return \yii\db\ActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getArms()
	{
		return static::hasMany(Arms::className(), ['id' => 'arms_id'])
			->viaTable('{{%lic_keys_in_arms}}', ['lic_keys_id' => 'id']);
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

	public function getSname()
	{
		return $this->licItem->licGroup->descr.' /'.$this->licItem->fullDescr.' /'.$this->keyShort;
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
