<?php

namespace app\models;

use app\models\links\LicLinks;
use voskobovich\linker\updaters\ManyToManySmartUpdater;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

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
 * @property string $datePart часть описания - дата
 * @property array $arms_ids
 * @property array $comps_ids Ссылка на ОСи
 * @property array $users_ids Ссылка на пользователей
 * @property array $soft_ids Ссылка на софт
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
	public function getLinksSchema()
	{
		$model=$this;
		return [
			'contracts_ids' => [Contracts::class,'lics_ids'],
			'lic_group_id' => [LicGroups::class,'lic_items_ids'],
			'arms_ids' => [Techs::class,'lic_items_ids', 'updater' => [
				'class' => ManyToManySmartUpdater::class,
				'viaTableAttributesValue' => LicLinks::fieldsBehaviour($model),
			]],
			'comps_ids' => [Comps::class,'lic_items_ids', 'updater' => [
				'class' => ManyToManySmartUpdater::class,
				'viaTableAttributesValue' => LicLinks::fieldsBehaviour($model),
			]],
			'users_ids' => [Users::class,'lic_items_ids', 'updater' => [
				'class' => ManyToManySmartUpdater::class,
				'viaTableAttributesValue' => LicLinks::fieldsBehaviour($model),
			]],
			'licKeys_ids' => [LicKeys::class,'lic_items_id', 'loader'=>'keys'],
		];
	}

    /**
     * {@inheritdoc}
	 * @noinspection PhpUnusedParameterInspection
     */
	public function rules()
    {
        return [
            [['descr', 'count','lic_group_id'], 'required'],
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
            [['lic_group_id'], 'exist', 'skipOnError' => true, 'targetClass' => LicGroups::class, 'targetAttribute' => ['lic_group_id' => 'id']],
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
				'placeholder' => 'Выберите тип лицензий',
			],
	        'descr' => [
				'Описание закупки',
				'hint' => 'Куда/Кому/С какой целью производится конкретно эта закупка.<br>'
					.'Например, на какой АРМ, группу АРМ, проект и т.п.<br>'
					.'Описание должно идентифицировать эту закупку в группе от остальных. Желательно кратко',
			],
	        'count' => [
				'Количество',
				'hint'=>'Количество лицензий приобретенных в этой закупке',
			],
	        'comment' => [
				'Комментарий',
				'hint' => 'Вся прочая информация по этой закупке. Если есть сомнения записать или нет что-то полезное в комментарии - лучше записать. Объем не ограничен.',
				'type' => 'text',
			],
	        'arms_ids' => [
				'АРМы, куда распределять лицензии',
				'hint' => 'На эти АРМы будут распределяться свободные (не распределенные через лицензионные ключи) лицензии из этой закупки',
				'placeholder' => 'Не закреплено за АРМ\'ами',
			],
			'comps_ids' => [
				'ОС, куда распределять лицензии',
				'hint' => 'На эти ОС будут распределяться свободные (не распределенные через лицензионные ключи) лицензии из этой закупки',
				'placeholder' => 'Не  закреплено за ОС/ВМ',
			],
			'users_ids' => [
				'Пользователи, на которых распределять лицензии',
				'hint' => 'На этих пользователей будут распределяться свободные (не распределенные через лицензионные ключи) лицензии из этой закупки',
				'placeholder' => 'Не  закреплено за пользователями',
			],
	        'contracts_ids' => [
				'Документы',
				'hint' => 'Желательно привязать документы на основании которых лицензии были приобретены (заявки, счета, акты)',
				'placeholder' => 'Выберите документ, который подтверждает закупку лицензий',
			],
			'contracts' => ['alias' => 'contracts_ids'],
	        'active_from' => [
				'Дата / Начало периода действия',
				'hint' => 'С какого момента лицензия считается действительной<br>'
					.'Если бессрочная, то все равно нужно указать дату приобретения'
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
     * @return ActiveQuery
     */
    public function getLicGroup()
    {
        return $this->hasOne(LicGroups::class, ['id' => 'lic_group_id']);
    }
	
	public function getName(){return $this->descr;}
	/**
	 * Возвращает набор документов
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getContracts()
	{
		return $this->hasMany(Contracts::class, ['id' => 'contracts_id'])
			->viaTable('{{%contracts_in_lics}}', ['lics_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getArms()
	{
		return $this->hasMany(Techs::class, ['id' => 'arms_id'])
			->viaTable('{{%lic_items_in_arms}}', ['lic_items_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getUsers()
	{
		return $this->hasMany(Users::class, ['id' => 'users_id'])
			->viaTable('{{%lic_items_in_users}}', ['lic_items_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getComps()
	{
		return $this->hasMany(Comps::class, ['id' => 'comps_id'])
			->viaTable('{{%lic_items_in_comps}}', ['lic_items_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getKeys()
	{
		return $this->hasMany(LicKeys::class, ['lic_items_id' => 'id']);
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
		return ArrayHelper::map($list, 'id', 'sname');
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
