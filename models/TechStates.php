<?php

namespace app\models;

use app\models\base\ArmsModel;
use Yii;

/**
 * This is the model class for table "tech_states".
 *
 * @property int       $id id
 * @property bool      $archived статус архивации
 * @property string    $code Служебное имя
 * @property string    $name Состояние
 * @property string    $descr Описание
 *
 * @property Techs[]   $techs
 */
class TechStates extends ArmsModel
{
	
	public static $title='Состояния';
	public static $titles='Состояния';
	public static $description='Состояния жизненного цикла оборудования и иных сущностей в предприятии';

	public static function modelDescription(): string
	{
		return 'Справочник состояний оборудования (в ремонте, на хранении, списано и т.п.); '
			.'состояния с признаком «Архивный» переводят оборудование в архив.';
	}
	
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
            'id' => 'ID',
			'code' => ['Служебное имя','hint'=>'Машинное имя состояния (латиницей, уникальное)'],
			'name' => ['Состояние','hint'=>'Название состояния, отображаемое рядом с оборудованием'],
			'archived' => [
				'Архивный',
				'hint'=>'Признак того, что оборудование с этим статусом перенесено в архив',
				'indexLabel'=>'арх.',
				
			],
            'descr' => ['Описание','hint'=>'Пояснение, когда применяется это состояние'],
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
	
	
	public function reverseLinks()
	{
		return [$this->techs];
	}
	
}
