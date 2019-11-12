<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "departments".
 *
 * @property int $id id
 * @property string $name Подразделение
 * @property string $comment Комментарии
 *
 * @property Arms[] $arms
 */
class Departments extends \yii\db\ActiveRecord
{
	public static $title='Подразделения';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'departments';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['comment'], 'string'],
            [['name'], 'string', 'max' => 64],
            [['name'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'id',
            'name' => 'Подразделение',
            'comment' => 'Комментарии',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArms()
    {
        return $this->hasMany(Arms::className(), ['departments_id' => 'id']);
    }


    public static function fetchNames(){
        $list= static::find()
            //->joinWith('some_join')
            //->select(['id','name'])
            ->orderBy(['name'=>SORT_ASC])
            ->all();
        return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
    }
}