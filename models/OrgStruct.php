<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "org_struct".
 *
 * @property string $id Идентификатор
 * @property string $pup Вышестоящий отдел
 * @property string $name Название подразделения
 */
class OrgStruct extends \yii\db\ActiveRecord
{
	public static $title='Орг. структура';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'org_struct';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'pup', 'name'], 'required'],
            [['id', 'pup'], 'string', 'max' => 16],
            [['name'], 'string', 'max' => 255],
            [['id'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pup' => 'Pup',
            'name' => 'Name',
        ];
    }

	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
			->all();
		return yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}

}
