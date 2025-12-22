<?php

namespace app\models;

use app\helpers\ArrayHelper;
use app\models\links\LicLinks;
use voskobovich\linker\updaters\ManyToManySmartUpdater;
use Yii;
use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "lic_groups".
 *
 * @property int $id Идентификатор
 * @property array $soft_ids Ссылка на софт
 * @property array $softIds Ссылка на софт
 * @property array $arms_ids Ссылка на АРМы
 * @property array $comps_ids Ссылка на ОСи
 * @property array $users_ids Ссылка на пользователей
 * @property array $keyArmsIds Ссылка на АРМы через ключи
 * @property array $itemArmsIds Ссылка на АРМы через закупки
 * @property string $descr Описание
 * @property string $comment Комментарий
 * @property string     $created_at Время создания
 * @property int        $totalCount общее количество лицензий
 * @property int        $activeCount общее количество активных лицензий (не просроченных)
 * @property int        $directUsedCount количество лицензий привязанных прямо к группе (не через закупки)
 * @property int        $usedCount общее количество используемых лицензий (активных занятых)
 * @property int        $freeCount общее количество доступных лицензий (активных не занятых)
 *
 * @property Soft $soft
 * @property Services $service
 * @property LicItems[] $licItems
 * @property LicTypes   $licType
 * @property Techs[] 	 $arms
 * @property Comps[]    $comps
 * @property Users[]    $users
 */
class LicGroups extends ArmsModel
{
	use traits\LicGroupsModelCalcFieldsTrait;
	
	public static $titles='Типы лицензий';
	public static $title='Тип лицензий';
	public $linkComment=null; //комментарий, добавляемый при привязке лицензий

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lic_groups';
    }
	
	public static $syncableFields=['descr','comment','updated_at','updated_by'];
    public static $syncableDirectLinks=['lic_types_id'=>'LicTypes'];
    public static $syncableMany2ManyLinks=['soft_ids'=>'Soft,licGroups_ids'];
    public static $syncKey='descr';
	public static $nameAttr='descr';
	
	
	/**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['lic_types_id', 'descr'], 'required'],
	        [['soft_ids','arms_ids','comps_ids','users_ids'], 'each', 'rule'=>['integer']],
            [['lic_types_id','services_id'], 'integer'],
            [['created_at','comment','linkComment'], 'safe'],
            [['descr',], 'string', 'max' => 255],
	        [['lic_types_id'], 'exist', 'skipOnError' => true, 'targetClass' => LicTypes::class, 'targetAttribute' => ['lic_types_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeData()
    {
        return [
			'arms_ids' => [
				'АРМы, куда распределять лицензии',
				'hint' => 'Свободные (не назначенные через закупки или ключи) лицензии этого типа будут распределяться на АРМ из списка',
				'placeholder' => 'Не закреплено за АРМ\'ами',
			],
			'comment' => [
				'Комментарий',
				'hint' => 'Комментарий. Все что нужно знать об этой группе лицензий. Длина не ограничена.',
				'type' => 'text',
			],
			'comps_ids' => [
				'ОС/ВМ, куда распределять лицензии',
				'hint' => 'Свободные (не назначенные через закупки или ключи) лицензии этого типа будут распределяться на ОС из списка',
				'placeholder' => 'Не  закреплено за ОС/ВМ',
			],
            'created_at' => [
            	'Время создания',
			],
			'descr' => [
				'Описание',
				'hint' => 'Описание группы лицензий. Не слишком длинное, должно в полной мере отражать что это за группа лицензий. Например все коробочные MS Office 2016 H&B / Все VL Microsoft Windows 10 pro',
			],
			'lic_types_id' => [
				'Схема лицензирования',
				'hint' => 'Какая схема лицензирования используется во всех закупках лицензий этого типа',
				'placeholder' => 'Выберите схему',
			],
			'linkComment' => [
				'Пояснение к добавляемым привязкам',
				'hint' => 'На каком основании эти лицензии закрепляются за добавленными выше объектами. Чтобы спустя время не было вопросов, а кто и зачем эту лицензию туда выделил (уже существующие привязки не меняются, только новые)',
			],
			'responsible' => ['Ответственный','Кто отвечает за эту лицензию'],
			'services_id' => [
				'Относится к сервису',
				'hint'=>'В рамках какого сервиса/услуги производится/сопровождается лицензирование.<br>'
					.'Нужно для определения ответственного: кто должен следить за актуальностью лицензии<br>'
					.'Если подходящего сервиса/услуги нет, то желательно завести',
				'placeholder' => 'Не относится ни к какому сервису',
			],
			'soft_ids' => [
				'Лицензируемое ПО',
				'hint' => 'Какие программные продукты затрагиваются лицензией. Не менее одного продукта. Если лицензия дает право на несколько продуктов (правило даунгрейда или иные) нужно перечислить их все.',
				'placeholder' => 'Набирайте название для поиска',
			],
			'soft'=>['alias'=>'soft_ids'],
			'support' => ['Поддержка','Команда замещающая ответственного на время его отсутствия'],
			'users_ids' => [
				'Пользователи, на которых распределять лицензии',
				'hint' => 'Свободные (не назначенные через закупки или ключи) лицензии этого типа будут распределяться на Пользователей из списка',
				'placeholder' => 'Не  закреплено за пользователями',
			],
        ];
    }


	/**
	 * Описание связей с другими классами
	 * @return array
	 */
	public function getLinksSchema()
	{
		$model=$this;
		return [
			'soft_ids' => [Soft::class,'lic_groups_ids','loader'=>'soft'],
			'arms_ids' => [Techs::class,'lic_groups_ids', 'updater' => [
				'class' => ManyToManySmartUpdater::class,
				'viaTableAttributesValue' => LicLinks::fieldsBehaviour($model),
			]],
			'comps_ids' => [Comps::class,'lic_groups_ids','updater' => [
				'class' => ManyToManySmartUpdater::class,
				'viaTableAttributesValue' => LicLinks::fieldsBehaviour($model),
			]],
			'users_ids' => [Users::class,'lic_groups_ids','updater' => [
				'class' => ManyToManySmartUpdater::class,
				'viaTableAttributesValue' => LicLinks::fieldsBehaviour($model),
			]],
			'services_id' => [Services::class,'lic_groups_ids'],
			'lic_types_id' => [LicTypes::class, 'lic_groups_ids'],
			'lic_items_ids' => [LicItems::class, 'lic_group_id'],
		];
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getSoft()
	{
		return $this->hasMany(Soft::class, ['id' => 'soft_id'])
			->viaTable('{{%soft_in_lics}}', ['lics_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getArms()
	{
		return $this->hasMany(Techs::class, ['id' => 'arms_id'])
			->viaTable('{{%lic_groups_in_arms}}', ['lic_groups_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getComps()
	{
		return $this->hasMany(Comps::class, ['id' => 'comps_id'])
			->viaTable('{{%lic_groups_in_comps}}', ['lic_groups_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getUsers()
	{
		return $this->hasMany(Users::class, ['id' => 'users_id'])
			->viaTable('{{%lic_groups_in_users}}', ['lic_groups_id' => 'id']);
	}
	
	/**
	 * Возвращает сервис, к которому относится лицензия
	 * @return ActiveQuery
	 */
	public function getService()
	{
		return $this->hasOne(Services::className(), ['id' => 'services_id']);
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
	 * @return ActiveQuery
	 */
	public function getLicType()
	{
		return $this->hasOne(LicTypes::class, ['id' => 'lic_types_id']);
	}

    /**
     * @return ActiveQuery
     */
    public function getLicItems()
    {
        return $this->hasMany(LicItems::class, ['lic_group_id' => 'id']);
    }

    public function getName() {return $this->descr; }
    
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
	
	public function getDirectUsedCount() {
		return count($this->arms) + count($this->comps) + count($this->users);
	}
	
	public function getUsedCount() {
		$total=$this->directUsedCount;
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
		return ArrayHelper::map($list, 'id', 'descr');
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
	
	public function reverseLinks()
	{
		return [
			$this->arms,
			$this->users,
			$this->comps,
			$this->licItems,
			$this->soft
		];
	}
}
