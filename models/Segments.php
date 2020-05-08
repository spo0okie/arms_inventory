<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "segments".
 *
 * @property int $id
 * @property string $name
 * @property string $description
 */
class Segments extends \yii\db\ActiveRecord
{
	
	static $title='Сегменты инфраструктуры';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'segments';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'string', 'max' => 32],
            [['description'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'description' => 'Описание',
        ];
    }
	
	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}
	
}
