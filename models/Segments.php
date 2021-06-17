<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "segments".
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string $description
 * @property string $history
 */
class Segments extends \yii\db\ActiveRecord
{
	
	static $titles='Сегменты инфраструктуры';
	static $title='Сегмент инфраструктуры';
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
            [['code','description'], 'string', 'max' => 255],
			[['history'], 'safe'],
        ];
    }
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'ID',
			'code' => 'Код',
			'name' => 'Название',
			'description' => 'Коротко',
			'history' => 'Подробно',
		];
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'ID',
			'code' => 'Название класса CSS для раскраски. Нигде не видно в явном виде',
			'name' => 'Понятное человеку название',
			'description' => 'Короткое описание, выводится в общем списке',
			'history' => 'Подробное описание, чтобы увидеть надо будет открыть описание сегмента',
		];
	}
	
	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}
	
}
