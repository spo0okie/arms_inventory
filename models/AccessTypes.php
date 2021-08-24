<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "access_types".
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $comment
 * @property string $notepad
 * @property string sname
 */
class AccessTypes extends \yii\db\ActiveRecord
{

	public static $title='Тип доступа';
	public static $titles='Типы доступа';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'access_types';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['notepad'], 'string'],
            [['code', 'name'], 'string', 'max' => 64],
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
            'code' => 'Code',
            'name' => 'Name',
            'comment' => 'Comment',
            'notepad' => 'Notepad',
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
			->orderBy(['name'=>SORT_ASC])
            ->all();
        return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
    }
}