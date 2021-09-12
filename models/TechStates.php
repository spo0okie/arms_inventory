<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tech_states".
 *
 * @property int $id id
 * @property string $code Служебное имя
 * @property string $name Состояние
 * @property string $descr Описание
 */
class TechStates extends \yii\db\ActiveRecord
{

	public static $title='Состояния';
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
            [['code'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'code' => 'Служебное имя',
            'name' => 'Состояние',
            'descr' => 'Описание',
        ];
    }


	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}

}
