<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "departments".
 *
 * @property int       $id id
 * @property string    $name Подразделение
 * @property string    $comment Комментарии
 *
 */
class Departments extends \yii\db\ActiveRecord
{
	public static $title='Подразделение';
	public static $titles='Подразделения';
	public static $hint='Подразделение в отличие от отделов оргструктуры указывается вручную<br>'.
		'Используется в случае, когда отделы в оргструктуре неудобно использовать для группировки,<br>'.
		'или же они не отображают реального разделения на отделы в организации';
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



    public static function fetchNames(){
        $list= static::find()
            //->joinWith('some_join')
            //->select(['id','name'])
            ->orderBy(['name'=>SORT_ASC])
            ->all();
        return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
    }
}