<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "lic_groups".
 *
 * @property int $id Идентификатор
 * @property array $soft_ids Ссылка на софт
 * @property array $arms_ids Ссылка на софт
 * @property array $keyArmsIds Ссылка на софт
 * @property array $itemArmsIds Ссылка на софт
 * @property string $descr Описание
 * @property string $comment Комментарий
 * @property string $created_at Время создания
 * @property int $totalCount общее количество лицензий
 * @property int $activeCount общее количество активных лицензий (не просроченных)
 * @property int $usedCount общее количество используемых лицензий (активных занятых)
 * @property int $freeCount общее количество доступных лицензий (активных не занятых)
 *
 * @property Soft $soft
 * @property LicItems[] $licItems
 * @property Arms[] $arms
 */
class LicGroups extends \yii\db\ActiveRecord
{
	public static $titles='Типы лицензий';
	public static $title='Тип лицензий';

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lic_groups';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['soft_ids', 'lic_types_id', 'descr'], 'required'],
	        [['soft_ids','arms_ids'], 'each', 'rule'=>['integer']],
            [['lic_types_id'], 'integer'],
            [['created_at','comment'], 'safe'],
            [['descr',], 'string', 'max' => 255],
	        [['lic_types_id'], 'exist', 'skipOnError' => true, 'targetClass' => LicTypes::className(), 'targetAttribute' => ['lic_types_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор',
	        'soft_ids' => 'Программные продукты',
	        'arms_ids' => 'АРМы, куда распределять лицензии',
	        'lic_types_id' => 'Тип лицензирования',
            'descr' => 'Описание',
            'comment' => 'Комментарий',
            'created_at' => 'Время создания',
        ];
    }

	/**
	 * @inheritdoc
	 */
	public function attributeHints()
	{
		return [
			'id' => 'Идентификатор',
			'soft_ids' => 'Какие программные продукты затрагиваются лицензией. Не менее одного продукта. Если лицензия дает право на несколько продуктов (правило даунгрейда или иные) нужно перечислить их все.',
			'lic_types_id' => 'Какой тип лицензирования используется во всех закупленных в этой группе лицензиях',
			'descr' => 'Описание группы лицензий. Не слишком длинное, должно в полной мере отражать что это за группа лицензий. Например все коробочные MS Office 2016 H&B / Все VL Microsoft Windows 10 pro',
			'comment' => 'Комментарий. Все что нужно знать об этой группе лицензий. Длина не ограничена.',
			'arms_ids' => 'Если не назначать закупки напрямую, можно привязывать АРМы к группе лицензий, и на эти рабочие места будут распределяться все доступные лицензии из группы',
			'created_at' => 'Время создания',
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
					'soft_ids' => 'soft',
					'arms_ids' => 'arms',
				]
			]
		];
	}

	/**
	 * Возвращает набор контрагентов в договоре
	 * @return array
	 */
	public function getSoft()
	{
		return static::hasMany(Soft::className(), ['id' => 'soft_id'])
			->viaTable('{{%soft_in_lics}}', ['lics_id' => 'id']);
	}

	/**
	 * Возвращает набор контрагентов в договоре
	 * @return array
	 */
	public function getArms()
	{
		return static::hasMany(Arms::className(), ['id' => 'arms_id'])
			->viaTable('{{%lic_groups_in_arms}}', ['lics_id' => 'id']);
	}

	/**
	 * Возвращает АРМы из закупок
	 * @return array
	 */
	public function getItemArms()
	{
		$arms=[];
		foreach ($this->licItems as $item) if (is_array($item->arms)&&count($item->arms)) {
			$arms=array_merge($arms,$item->arms);
		}
		return $arms;
	}
	
	/**
	 * Возвращает ИД АРМов из закупок
	 * @return array
	 */
	public function getItemArmsIds()
	{
		$arms=[];
		foreach ($this->licItems as $item) if (is_array($item->arms_ids)&&count($item->arms_ids)) {
			$arms=array_merge($arms,$item->arms_ids);
		}
		return $arms;
	}
	
	
	/**
	 * Возвращает АРМы из ключей закупок
	 * @return array
	 */
	public function getKeyArms()
	{
		$arms=[];
		foreach ($this->licItems as $item) {
			foreach ($item->keys as $key) if (count($key->arms)) {
				$arms=array_merge($arms,$key->arms);
			}
		}
		return $arms;
	}
	
	/**
	 * Возвращает ИДы АРМов из ключей закупок
	 * @return array
	 */
	public function getKeyArmsIds()
	{
		$arms=[];
		foreach ($this->licItems as $item) {
			foreach ($item->keys as $key) if (count($key->arms_ids)) {
				$arms=array_merge($arms,$key->arms_ids);
			}
		}
		return $arms;
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getLicType()
	{
		return $this->hasOne(LicTypes::className(), ['id' => 'lic_types_id']);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLicItems()
    {
        return $this->hasMany(LicItems::className(), ['lic_group_id' => 'id']);
    }

	public function getTotalCount() {
		$total=0;
		foreach ($this->licItems as $item) $total+=$item->count;
		return $total;
	}

	public function getActiveCount() {
		$total=0;
		foreach ($this->licItems as $item) if ($item->active) $total+=$item->count;
		return $total;
	}
	public function getActiveItemsCount() {
		$total=0;
		foreach ($this->licItems as $item) if ($item->active) $total++;
		return $total;
	}

	public function getUsedCount() {
		$total=count($this->arms);
		foreach ($this->licItems as $item) if ($item->active) $total+=$item->usages;
		return $total;
	}

	public function getFreeCount() {
		return $this->activeCount - $this->usedCount;
	}

	public static function fetchNames(){
		$list= static::find()
			->select(['id','descr'])
			->all();
		return yii\helpers\ArrayHelper::map($list, 'id', 'descr');
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
				//то сначала выкидываем все армы привязанные к закупкам и ключам
				if (count($keys=array_merge($this->keyArmsIds,$this->itemArmsIds))) {
					$this->arms_ids=array_diff($this->arms_ids,$keys);
				}
			}
			
			return true;
		}
		return false;
	}
	
}
