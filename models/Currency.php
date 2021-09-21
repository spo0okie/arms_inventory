<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "currency".
 *
 * @property int $id
 * @property string|null $symbol
 * @property string|null $code
 * @property string|null $name
 * @property string|null $comment
 * @property string|null $notepad
 * @property string sname
 */
class Currency extends \yii\db\ActiveRecord
{
	
	public static $title='Валюта';
	public static $titles='Валюты';
	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'currency';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['notepad'], 'string'],
            [['symbol'], 'string', 'max' => 12],
            [['code'], 'string', 'max' => 64],
            [['name'], 'string', 'max' => 128],
            [['comment'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'symbol' => 'Символ',
            'code' => 'Код',
            'name' => 'Название',
            'comment' => 'Комментарий',
            'notepad' => 'Записная книжка',
        ];
    }
	
	
	/**
	 * Name for search
	 * @return string
	 */
	public function getSname()
	{
		return $this->name;
	}
	
	
	/**
	 * Возвращает список всех элементов
	 * @return array|mixed|null
	 */
	public static function fetchNames(){
		$list= static::find()
			//->joinWith('some_join')
			//->select(['id','name'])
			->orderBy(['code'=>SORT_ASC])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'code');
	}
}
