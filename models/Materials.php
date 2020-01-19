<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "materials".
 *
 * @property int $id Идентификатор
 * @property int $parent_id Источник
 * @property string $date Дата поступления
 * @property int $count Количество
 * @property int $type_id Тип материалов
 * @property string $model Модель
 * @property int $places_id Помещение
 * @property string $it_staff_id Сотрудник службы ИТ
 * @property string $comment Комментарий
 * @property string $history Записная кинжка
 * @property array $contracts_ids массив ссылок на документы
 
 * @property int $used Израсходовано
 * @property int $movedCount Израсходовано
 * @property int $usedCount Израсходовано
 * @property int $rest Остаток
 * @property \app\models\Places $place Помещение
 * @property \app\models\Users $itStaff Ответственный
 * @property \app\models\Materials $parent Источник
 * @property \app\models\MaterialsTypes $type Категория
 * @property \app\models\Materials[] $childs Источник
 * @property \app\models\MaterialsUsages[] $usages Расходы
 * @property \app\models\Contracts[] $contracts Документы
 */
class Materials extends \yii\db\ActiveRecord
{

	public static $title="ЗиП и Материалы";

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'materials';
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
					'contracts_ids' => 'contracts',
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
            [['parent_id', 'count', 'type_id', 'places_id'], 'integer'],
            [['date', 'count', 'it_staff_id', 'places_id'  ], 'required'],
	        //подмена категории и модели, если установлен источник материалов
	        ['type_id', 'filter', 'filter' => function ($value) {return empty($this->parent_id)?$value:$this->parent->type_id;}],
	        ['model', 'filter', 'filter' => function ($value) {return empty($this->parent_id)?$value:$this->parent->model;}],

	        [['model','type_id'],'required'],
            [['date','contracts_ids'], 'safe'],
            [['comment'], 'string'],
	        [['model'], 'string', 'max' => 128],
	        [['it_staff_id'], 'string', 'max' => 16],

        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'Идентификатор',
			'parent_id' => 'Взято из',
			'date' => 'Дата поступления',
			'count' => 'Количество',
			'used' => 'Использовано',
			'rest' => 'Остаток',
			'type_id' => 'Тип материалов',
			'model' => 'Наименование',
			'places_id' => 'Помещение',
			'it_staff_id' => 'Сотрудник службы ИТ',
			'comment' => 'Комментарий',
			'history' => 'Записная книжка',
			'contracts_ids' => 'Документы',
		];
	}


	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'Идентификатор',
			'parent_id' => 'Если указать источник поступления материалов, то этот материал будет считаться частично перемещенным из источника (как деление армии в героях)',
			'date' => 'Когда произошло поступление этого материала',
			'count' => 'Сколько материала поступило',
			//'type_id' => 'Тип материалов',
			'model' => 'Желательно использовать обобщающее наименование из уже использованных для возможности группировки и чтобы не плодить лишнюю номенклатуру. Точное наименование модели можно вписать в коментарий',
			'places_id' => 'Где хранятся поступившие материалы',
			'it_staff_id' => 'Кто отвечает за хранение материалов',
			'comment' => 'Все что нужно знать, но не влезло в остальные поля',
			//'history' => 'Записная книжка',
			'contracts_ids' => 'Документы, привязанные к поступлению или хранению материала (расходные привязываются к расходам материала)',
		];
	}


	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getItStaff()
	{
		return $this->hasOne(Users::className(), ['id' => 'it_staff_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getPlace()
	{
		return $this->hasOne(Places::className(), ['id' => 'places_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getParent()
	{
		return $this->hasOne(Materials::className(), ['id' => 'parent_id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getChilds()
	{
		return $this->hasMany(Materials::className(), ['parent_id' => 'id']);
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getUsages()
	{
		return $this->hasMany(MaterialsUsages::className(), ['materials_id' => 'id']);
	}

	/**
	 * Возвращает расход материала
	 * @return integer
	 */
	public function getUsed() {
		//на этом этапе еще не реализованы списания, поэтому учитываем только перемещения
		$sum=0;
		foreach ($this->childs as $child) $sum+=$child->count;
		foreach ($this->usages as $usage) $sum+=$usage->count;
		return $sum;
	}
	
	/**
	 * Возвращает расход материала на перемещения
	 * @return integer
	 */
	public function getMovedCount() {
		//на этом этапе еще не реализованы списания, поэтому учитываем только перемещения
		return $this->hasMany(Materials::className(), ['parent_id' => 'id'])->sum('count');
	}

	/**
	 * Возвращает расход материала на ремонты
	 * @return integer
	 */
	public function getUsedCount() {
		//на этом этапе еще не реализованы списания, поэтому учитываем только перемещения
		return $this->hasMany(\app\models\MaterialsUsages::className(), ['materials_id' => 'id'])->sum('count');
	}

	/**
	 * Возвращает остаток материала
	 * @return integer
	 */
	public function getRest() {
		return $this->count - $this->used;
	}

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getType()
	{
		return $this->hasOne(MaterialsTypes::className(), ['id' => 'type_id']);
	}

	/**
	 * Возвращает набор документов
	 */
	public function getContracts()
	{
		return static::hasMany(Contracts::className(), ['id' => 'contracts_id'])
			->viaTable('{{%contracts_in_materials}}', ['materials_id' => 'id']);
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
		return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
	}

}
