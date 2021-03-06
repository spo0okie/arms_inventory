<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "net_domains".
 *
 * @property int $id
 * @property string $name
 * @property string $comment
 * @property int $tech_id
 */
class NetDomains extends \yii\db\ActiveRecord
{
	public static $title='L2 Домены';
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'net_domains';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
			[['name'], 'string', 'max' => 255],
			[['comment'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
			'name' => 'Название домена',
			'comment' => 'Пояснение',
        ];
    }
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeHits()
	{
		return [
			'id' => 'ID',
			'name' => 'Пояснение чем объединены сети входящие в этот домен',
			'comment' => 'Все что нужно знать об этом домене, но что не ясно из названия',
		];
	}
	
	
	/**
	 * Возвращает список всех доменов
	 * @return array|mixed|null
	 */
	public static function fetchNames()
	{
		$list = static::find()
			->orderBy('name')
			->all();
		return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}
}
