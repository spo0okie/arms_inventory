<?php

namespace app\models;

use voskobovich\linker\LinkerBehavior;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "materials".
 *
 * @property int $id Идентификатор
 * @property int $parent_id Источник
 * @property string $date Дата поступления
 * @property int $count Количество
 * @property int $type_id Тип материалов
 * @property string $model Модель
 * @property string $typeName тип:Модель
 * @property int $places_id Помещение
 * @property string            $it_staff_id Сотрудник службы ИТ
 * @property string            $comment Комментарий
 * @property string            $history Записная книжка
 * @property float             $cost Стоимость пачки материалов
 * @property float             $charge НДС
 * @property array             $contracts_ids массив ссылок на документы
 * @property int               $used Израсходовано
 * @property int               $movedCount Израсходовано
 * @property int               $usedCount Израсходовано
 * @property int               $rest Остаток
 * @property Currency          $currency Валюта покупки
 * @property Places            $place Помещение
 * @property Users             $itStaff Ответственный
 * @property Materials         $parent Источник
 * @property MaterialsTypes    $type Категория
 * @property Materials[]       $children Источник
 * @property MaterialsUsages[] $usages Расходы
 * @property Contracts[]       $contracts Документы
 */
class Materials extends ArmsModel
{
	
	public static $title="ЗиП и Материалы";
	public static $titles="ЗиП и Материалы";

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'materials';
    }

	/**
	 * В списке поведений прикручиваем many-to-many ссылки
	 * @return array
	 */
	public function behaviors()
	{
		return [
			[
				'class' => LinkerBehavior::class,
				'relations' => [
					'contracts_ids' => 'contracts',
					'usages_ids' => 'usages',			//one-2-many
					'children_ids' => 'children',		//one-2-many
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
			[['cost','charge'], 'number'],
			[['currency_id'],'default','value'=>1],
            [['parent_id', 'it_staff_id', 'count', 'type_id', 'places_id'], 'integer'],
            [['date', 'count', 'places_id'  ], 'required'],
	        //подмена категории и модели, если установлен источник материалов
	        ['type_id', 'filter', 'filter' => function ($value) {return empty($this->parent_id)?$value:$this->parent->type_id;}],
	        ['model', 'filter', 'filter' => function ($value) {return empty($this->parent_id)?$value:$this->parent->model;}],

	        [['model','type_id'],'required'],
            [['date','contracts_ids'], 'safe'],
            [['comment'], 'string'],
	        [['model'], 'string', 'max' => 128],
	        //[['it_staff_id'], 'string', 'max' => 16],

        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return [
			'id' => [
				'Идентификатор',
			],
			'parent_id' => [
				'Взято из',
				'hint' => 'Если указать источник поступления материалов, то этот материал будет считаться частично перемещенным из источника (как деление армии в героях)',
			],
			'date' => [
				'Дата поступления',
				'hint' => 'Когда произошло поступление этого материала',
			],
			'count' => [
				'Количество',
				'hint' => 'Сколько материала поступило',
			],
			'used' => [
				'Использовано',
				//'type_id' => 'Тип материалов',
			],
			'rest' => [
				'Остаток',
				'indexHint'=>'Отображаются только материалы с остатком не меньше установленного в фильтр'
			],
			'type_id' => [
				'Тип материалов',
			],
			'type'=>['alias'=>'type_id'],
			'model' => [
				'Наименование',
				'hint' => 'Желательно использовать обобщающее наименование из уже использованных для возможности группировки и чтобы не плодить лишнюю номенклатуру. Точное наименование модели можно вписать в комментарий',
			],
			'places_id' => [
				'Помещение',
				'hint' => 'Где хранятся поступившие материалы',
			],
			'place'=>['alias'=>'places_id'],
			'it_staff_id' => [
				'Сотрудник службы ИТ',
				'hint' => 'Кто отвечает за хранение материалов',
			],
			'comment' => [
				'Комментарий',
				'hint' => 'Все что нужно знать, но не влезло в остальные поля',
			],
			'history' => [
				'Записная книжка',
			],
			'contracts_ids' => [
				'Документы',
				'hint' => 'Документы, привязанные к поступлению или хранению материала (расходные привязываются к расходам материала)',
			],
			'currency_id' => 'Валюта',
			'cost' => [
				'Стоимость',
				'hint' => 'Суммарная за все, не удельная'
			],
			'charge' => 'НДС',
			'materials_usages_ids' => 'Расход'
		];
	}





	/**
	 * @return ActiveQuery
	 */
	public function getItStaff()
	{
		return $this->hasOne(Users::class, ['id' => 'it_staff_id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getPlace()
	{
		return $this->hasOne(Places::class, ['id' => 'places_id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getParent()
	{
		return $this->hasOne(Materials::class, ['id' => 'parent_id']);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getChildren()
	{
		return $this->hasMany(Materials::class, ['parent_id' => 'id'])->from(['materials_children'=>Materials::tableName()]);
	}

	/**
	 * @return ActiveQuery
	 */
	public function getUsages()
	{
		return $this->hasMany(MaterialsUsages::class, ['materials_id' => 'id']);
	}

	/**
	 * Возвращает расход материала
	 * @return integer
	 */
	public function getUsed() {
		//на этом этапе еще не реализованы списания, поэтому учитываем только перемещения
		$sum=0;
		foreach ($this->children as $child) $sum+=$child->count;
		foreach ($this->usages as $usage) $sum+=$usage->count;
		return $sum;
	}
	
	/**
	 * Возвращает расход материала на перемещения
	 * @return integer
	 */
	public function getMovedCount() {
		//на этом этапе еще не реализованы списания, поэтому учитываем только перемещения
		return $this->hasMany(Materials::class, ['parent_id' => 'id'])->sum('count');
	}

	/**
	 * Возвращает расход материала на ремонты
	 * @return integer
	 */
	public function getUsedCount() {
		//на этом этапе еще не реализованы списания, поэтому учитываем только перемещения
		return $this->hasMany(MaterialsUsages::class, ['materials_id' => 'id'])->sum('count');
	}

	/**
	 * Возвращает остаток материала
	 * @return integer
	 */
	public function getRest() {
		return $this->count - $this->used;
	}

	/**
	 * @return ActiveQuery
	 */
	public function getType()
	{
		return $this->hasOne(MaterialsTypes::class, ['id' => 'type_id']);
	}

	/**
	 * Возвращает набор документов
	 */
	public function getContracts()
	{
		return $this->hasMany(Contracts::class, ['id' => 'contracts_id'])
			->viaTable('{{%contracts_in_materials}}', ['materials_id' => 'id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getCurrency()
	{
		return $this->hasOne(Currency::class, ['id' => 'currency_id']);
	}
	/**
	 * Имя для поиска материала
	 */
	public function getSname()
	{
		$tokens=[];
		$tokens[] = $this->model;
		$tokens[] = is_null($this->place)?'(Нет помещения!)':'('.$this->place->fullName.')';
		return implode(' ',$tokens);
	}
	
	public function getName(){return $this->typeName;}

	/**
	 * Имя для поиска материала
	 */
	public function getTypeName()
	{
		return $this->type->name.': '.$this->model;
	}


	public static function fetchNames(){
		$list= static::find()
			->joinWith('place')
			//->select(['id','name'])
			->all();
		return ArrayHelper::map($list, 'id', 'sname');
	}
	
	public function reverseLinks()
	{
		return [
			$this->usages,
			'Перемещения'=>$this->children,
		];
	}
	
}
