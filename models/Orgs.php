<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "orgs".
 *
 * @property int $id id
 * @property string $name Наименование
 * @property string $short Короткое имя
 * @property string $comment Комментарии
 */
class Orgs extends \yii\db\ActiveRecord
{

	public static $title="Юр.лица";

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'orgs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'short'], 'required'],
            [['comment'], 'string'],
            [['name'], 'string', 'max' => 128],
            [['short'], 'string', 'max' => 16],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'name' => 'Наименование',
            'short' => 'Короткое имя',
            'comment' => 'Комментарии',
        ];
    }

	public static function fetchNames(){
		$list= static::find()
			//->joinWith('place')
			//->select(['id','name'])
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}

}
