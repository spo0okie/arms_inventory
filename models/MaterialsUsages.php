<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "materials_usages".
 *
 * @property int $id id
 * @property int $materials_id Материал
 * @property int $count Количество
 * @property string $date Дата расхода
 * @property int $arms_id АРМ
 * @property int $techs_id Оборудование
 * @property string $comment Коментарий
 * @property string $sname Поисковая строка
 * @property string $to Куда потрачено
 *
 * @property Materials $material
 * @property Arms $arm
 * @property Techs $tech
 */
class MaterialsUsages extends \yii\db\ActiveRecord
{

	public static $title='Расход материалов';


	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'materials_usages';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['materials_id', 'count', 'date', 'comment'], 'required'],
            [['materials_id', 'count', 'arms_id', 'techs_id'], 'integer'],
            [['date','history'], 'safe'],
            [['comment','history'], 'string'],
            [['materials_id'], 'exist', 'skipOnError' => true, 'targetClass' => Materials::className(), 'targetAttribute' => ['materials_id' => 'id']],
            [['arms_id'], 'exist', 'skipOnError' => true, 'targetClass' => Arms::className(), 'targetAttribute' => ['arms_id' => 'id']],
            [['techs_id'], 'exist', 'skipOnError' => true, 'targetClass' => Techs::className(), 'targetAttribute' => ['techs_id' => 'id']],
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'id',
			'materials_id' => 'Материал',
			'count' => 'Количество',
			'date' => 'Дата расхода',
			'arms_id' => 'АРМ',
			'techs_id' => 'Оборудование',
			'comment' => 'Пояснение',
			'history' => 'Зап. книжка',
			'sname' => 'Описание',
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'id',
			'materials_id' => 'Что использовано для работы',
			'count' => 'Сколько исзрасходовано в ед. изм. материала',
			'date' => 'Дата проведения работ связанных с расходом',
			'arms_id' => 'АРМ на который был потрачен материал или установлен ЗиП',
			'techs_id' => 'Оборудование на которое был потрачен материал или установлен ЗиП',
			'comment' => 'Что за работы производились',
			'history' => 'Подробности по списанию. Кто инициатор, номер заявки, какие-то нюансы',
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
    public function getArm()
    {
        return $this->hasOne(Arms::className(), ['id' => 'arms_id']);
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTech()
	{
		return $this->hasOne(Techs::className(), ['id' => 'techs_id']);
	}

	/**
	 * @return string
	 */
	public function getTo()
	{
		$tokens=[];
		if(!empty($this->arm)) $tokens[]=$this->arm->num;
		if(!empty($this->tech)) $tokens[]=$this->tech->num;
		if(strlen($this->comment)) $tokens[]=$this->comment;
		return	implode(' ',$tokens).' //'.
			$this->date;
	}

	/**
	 * @return string
	 */
	public function getSname()
	{
		return $this->material->sname.':'.
			$this->count.
			$this->material->type->units.
			' -> '.
			$this->to;
	}
}
