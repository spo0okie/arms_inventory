<?php

namespace app\models;

use app\models\traits\MaterialUsagesModelCalcFieldsTrait;

/**
 * This is the model class for table "materials_usages".
 *
 * @property int $id id
 * @property int $materials_id Материал
 * @property int $count Количество
 * @property string $date Дата расхода
 * @property int       $arms_id АРМ
 * @property int       $techs_id Оборудование
 * @property string    $comment Коментарий
 * @property string    $sname Поисковая строка
 * @property string    $to Куда потрачено
 * @property float     $cost Стоимость пачки материалов
 * @property float     $charge НДС
 *
 * @property Currency  $currency Валюта покупки
 * @property Materials $material
 * @property Techs     $tech
 */
class MaterialsUsages extends ArmsModel
{
	use MaterialUsagesModelCalcFieldsTrait;
	
	public static $title='Расход материалов';
	public static $titles='Расход материалов';
	public static $helptitle="ЗиП и Материалы:расход";


	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'materials_usages';
    }
	
	public $linksSchema=[
		'techs_id'=>[Techs::class,'materials_usages_ids'],
		'materials_id'=>[Materials::class,'usages_ids'],
	];
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['materials_id', 'count', 'date', 'comment'], 'required'],
            [['materials_id', 'count', 'techs_id'], 'integer'],
            [['date','history'], 'safe'],
            [['comment','history'], 'string'],
            [['materials_id'], 'exist', 'skipOnError' => true, 'targetClass' => Materials::className(), 'targetAttribute' => ['materials_id' => 'id']],
            [['techs_id'], 'exist', 'skipOnError' => true, 'targetClass' => Techs::className(), 'targetAttribute' => ['techs_id' => 'id']],
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return [
			'materials_id' => [
				'Материал',
				'hint' => 'Что использовано для работы',
				'placeholder' => 'Выберите расходуемый материал',
			],
			'material' => ['alias'=>'materials_id',],
			'count' => [
				'Количество',
				'indexLabel'=>'Кол-во',
				'hint' => 'Сколько израсходовано в ед. изм. материала',
			],
			'date' => [
				'Дата расхода',
				'hint' => 'Дата проведения работ связанных с расходом',
			],
			'techs_id' => [
				'Оборудование',
				'hint' => 'Оборудование на которое был потрачен материал или установлен ЗиП',
				'placeholder' => 'Выберите оборудование назначения'
			],
			'comment' => [
				'Пояснение',
				'hint' => 'Что за работы производились<br>'.
					'(коротко, для просмотра в списке)',
			],
			'history' => [
				'Подробно',
				'hint' => 'Подробности по списанию. Кто инициатор, номер заявки, какие-то нюансы',
				'type' => 'text',
			],
			'sname' => ['Описание',],
			'place' => ['Откуда',],
			'to' => ['Куда',],
			'cost' => 'Стоимость',
			'charge' => 'в т.ч. НДС',
		];
	}

	/**
     * @return \yii\db\ActiveQuery
     */
    public function getMaterial()
    {
        return $this->hasOne(Materials::className(), ['id' => 'materials_id']);
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTech()
	{
		return $this->hasOne(Techs::className(), ['id' => 'techs_id']);
	}
	
}
