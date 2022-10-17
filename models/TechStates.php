<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tech_states".
 *
 * @property int $id id
 * @property bool $archived статус архивации
 * @property string $code Служебное имя
 * @property string $name Состояние
 * @property string $descr Описание
 *
 * @property Arms[] $arms
 * @property Techs[] $techs
 */
class TechStates extends ArmsModel
{
	
	public static $title='Состояния';
	public static $titles='Состояния';
	public static $description='Состояния жизненного цикла оборудования и иных сущностей в предприятии';
	
	public static $unknown_code='state_unknown';
	//public static $unknown_name='';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tech_states';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['code', 'name', 'descr'], 'required'],
            [['descr'], 'string'],
            [['code'], 'string', 'max' => 64],
            [['name'], 'string', 'max' => 128],
			[['archived'],'integer'],
            [['code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return [
            'id' => 'id',
            'code' => 'Служебное имя',
			'name' => 'Состояние',
			'archived' => [
				'Архивный',
				'hint'=>'Признак того, что оборудование с этим статусом перенесено в архив',
				'indexLabel'=>'арх.',
				
			],
            'descr' => 'Описание',
        ];
    }


	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getTechs()
	{
		return $this->hasMany(Techs::className(), ['state_id' => 'id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getArms()
	{
		return $this->hasMany(Arms::className(), ['state_id' => 'id']);
	}
	
	public function reverseLinks()
	{
		return [$this->arms,$this->techs];
	}
	
}
