<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "lic_items".
 *
 * @property int $id Идентификатор
 * @property int $lic_group_id В группе лицензий
 * @property string $descr Описание закупки
 * @property int $count Количество приобретенных лицензий
 * @property string $comment Комментарий
 * @property string $active_from Начало периода действия
 * @property string $active_to Окончание периода действия
 * @property string $created_at Время создания
 * @property array $arms_ids
 * @property array $comps_ids Ссылка на ОСи
 * @property array $users_ids Ссылка на пользователей
 * @property array       $softIds Ссылка на софт
 * @property array       $contracts_ids
 * @property int         $usages
 * @property float       $utilization
 * @property bool        $active
 * @property string      $sname
 * @property string      $fullDescr
 * @property string      $status
 *
 *
 * @property Techs[] 	 $arms
 * @property Comps[]     $comps
 * @property Users[]     $users
 * @property array       $keyArmsIds
 * @property LicKeys[]   $keys
 * @property LicKeys[]   $usedKeys
 * @property Contracts[] $contracts
 * @property LicGroups   $licGroup
 * @property LicTypes    $licType
 */
class LicItems extends ArmsModel
{
	
	public static $titles='Закупленные лицензии';
	public static $title='Закупленные лицензии';
	
	public $linkComment=null; //комментарий, добавляемый при привязке лицензий

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'lic_items';
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
					'contracts_ids' => 'contracts',
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
            [['descr', 'count'], 'required'],
	        [['contracts_ids','arms_ids','comps_ids','users_ids'], 'each', 'rule'=>['integer']],
	        [['lic_group_id',  'count'], 'integer'],
            [['active_from', 'active_to', 'created_at', 'comment', 'linkComment'], 'safe'],
	        [['descr'], 'string', 'max' => 255],
	        ['descr', function ($attribute, $params, $validator) {
		        $same=static::findOne([$attribute=>$this->$attribute,'lic_group_id'=>$this->lic_group_id]);
		        if (is_object($same)&&($same->id != $this->id)) {
			        $this->addError($attribute, "Такая закупка (с таким описанием) уже существует в этой группе лицензий. Необходимо дать такое описание, чтобы было очевидно, какая закупка для чего была сделана.");
		        }
	        }],
            [['lic_group_id'], 'exist', 'skipOnError' => true, 'targetClass' => LicGroups::className(), 'targetAttribute' => ['lic_group_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return [
	        'lic_group_id' => [
	        	'Тип лицензий',
				'hint' => 'Тип лицензий которые были закуплены<br>'.
				'Выберите один из вариантов. Если нужного нет - то надо сначала завести',
			],
	        'descr' => [
				'Описание закупки',
				'hint' => 'Куда/Кому/С какой целью производится конкретно эта закупка. Например на какой АРМ, группу АРМ, проект и т.п. Описание должно идентифицировать эту закупку в группе от остальных. Желательно кратко',
			],
	        'count' => [
				'Количество приобретенных лицензий',
			],
	        'comment' => [
				'Комментарий',
				'hint' => 'Вся прочая информация по этой закупке. Если есть сомнения записать или нет чтото полезное в комментарии - лучше записать. Объем не ограничен.',
			],
	        'arms_ids' => [
				'АРМы, куда распределять лицензии',
				'hint' => 'На эти АРМы будут распределяться лицензии не распределенные через лиц. ключи',
			],
			'comps_ids' => [
				'ОС, куда распределять лицензии',
				'hint' => 'На эти ОС будут распределяться лицензии не распределенные через лиц. ключи',
			],
			'users_ids' => [
				'Пользователи, на которых распределять лицензии',
				'hint' => 'На этих пользователей будут распределяться лицензии не распределенные через лиц. ключи',
			],
	        'contracts_ids' => [
				'Документы',
				'hint' => 'Желательно привязать документы на основании которых лицензии были приобретены (заявки, счета, акты)',
			],
	        'active_from' => [
				'Дата / Начало периода действия',
				'hint' => 'С какого момента лицензия считается действительной',
			],
	        'active_to' => [
				'Окончание периода действия',
				'hint' => 'Если не указано, то считается бессрочной',
			],
			'linkComment' => [
				'Пояснение к добавляемым привязкам',
				'hint' => 'На каком основании эти лицензии закрепляются за добавленными выше объектами. Чтобы спустя время не было вопросов, а кто и зачем эту лицензию туда выделил (уже существующие привязки не меняются, только новые)',
			],
	        'status' => [
				'Состояние',
			],
        ];
    }

    
	public function getDatePart()
	{
		if (strlen($this->active_from)) {
			if (strlen($this->active_to))
				return Yii::$app->formatter->asDate($this->active_from).' - '.Yii::$app->formatter->asDate($this->active_to);
			else
				return Yii::$app->formatter->asDate($this->active_from);
		} else
			return 'бессрочная';
	}

	public function getFullDescr() {
    	return $this->descr.' ('.$this->datePart.')';
	}
	
	/**
	 * Search name
	 * @return string
	 */
	public function getSname()
	{
		return $this->licGroup->descr.' /'.$this->fullDescr;
	}
	
	/**
	 * Display name
	 * @return string
	 */
	public function getDname()
	{
		return $this->licGroup->descr.' /'.$this->descr;
	}
	
	public function getSoftIds()
	{
		return $this->licGroup->softIds;
	}
	
	/**
     * @return \yii\db\ActiveQuery
     */
    public function getLicGroup()
    {
        return $this->hasOne(LicGroups::className(), ['id' => 'lic_group_id']);
    }


    /**
	 * Возвращает набор документов
	 * @return \yii\db\ActiveQuery
	 */
	public function getContracts()
	{
		return $this->hasMany(Contracts::className(), ['id' => 'contracts_id'])
			->viaTable('{{%contracts_in_lics}}', ['lics_id' => 'id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getArms()
	{
		return $this->hasMany(Techs::className(), ['id' => 'arms_id'])
			->viaTable('{{%lic_items_in_arms}}', ['lic_items_id' => 'id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getUsers()
	{
		return $this->hasMany(Users::className(), ['id' => 'users_id'])
			->viaTable('{{%lic_items_in_users}}', ['lic_items_id' => 'id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getComps()
	{
		return $this->hasMany(Comps::className(), ['id' => 'comps_id'])
			->viaTable('{{%lic_items_in_comps}}', ['lic_items_id' => 'id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getKeys()
	{
		return $this->hasMany(LicKeys::className(), ['lic_items_id' => 'id']);
	}

	/**
	 * @return array
	 */
	public function getUsedKeys()
	{
		$keys=[];
		foreach ($this->keys as $key)
			if (count($key->arms)) $keys[]=$key;
		return $keys;
	}
	
	/**
	 * Возвращает АРМы из ключей
	 * @return array
	 */
	public function getKeyArms()
	{
		$arms=[];
		foreach ($this->keys as $key) if (count($key->arms)) {
			$arms=array_merge($arms,$key->arms);
		}
		return $arms;
	}

	/**
	 * Возвращает ИДы АРМов из ключей
	 * @return array
	 */
	public function getKeyArmsIds()
	{
		$arms=[];
		foreach ($this->keys as $key) if (count($key->arms)) {
			$arms=array_merge($arms,$key->arms_ids);
		}
		return $arms;
	}

	public function getActive() {
		if (strlen($this->active_from)) {
			if (time()<strtotime($this->active_from)) return false;
		}
		if (strlen($this->active_to)) {
			if (time()>strtotime($this->active_to)) return false;
		}
		return true;
	}

	public function getStatus() {
		$message='Ошибка!';
		if ($this->active) {
			if (strlen($this->active_to)) {
				$message='действ. до '.$this->active_to;
			} else $message='бессрочная';
		} else {
			if (time()>strtotime($this->active_to)) $message='просроч. с '.$this->active_to;
			if (time()<strtotime($this->active_from)) $message='начнется '.$this->active_to;
		}
		return $message.
			' ['.$this->usages.'/'.$this->count.']';
	}

	public function getUsages() {
		return count($this->arms_ids)+count($this->comps_ids)+count($this->users_ids)+count($this->usedKeys);
	}

	public function getUtilization() {
		return $this->count?($this->usages/$this->count):0;
	}

	public static function fetchNames(){
		$list= static::find()->with('licGroup')
			//->select(['id','descr'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
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
				//то сначала выкидываем из группы все армы привязанные к закупке
				if (count($groupArms=array_intersect($this->arms_ids,$this->licGroup->arms_ids))) {
					$this->licGroup->arms_ids=array_diff($this->licGroup->arms_ids,$groupArms);
					$this->licGroup->save();
				}
				//а потом из закупки выкидываем все армы распределенные через ключи
				if (count($keyArms=array_intersect($this->keyArmsIds,$this->arms_ids))) {
					$this->arms_ids=array_diff($this->arms_ids,$keyArms);
				}
			}
			
			return true;
		}
		return false;
	}
	
	public function reverseLinks()
	{
		return [
			$this->keys,
			$this->users,
			$this->arms,
			$this->comps,
			$this->contracts
		];
	}
}
