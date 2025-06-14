<?php

namespace app\models;

use Yii;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "net_domains".
 *
 * @property int $id
 * @property string $name
 * @property string $comment
 * @property int $tech_id
 * @property Places $place
 */
class NetDomains extends ArmsModel
{
	public static $title='L2 Домен';
	public static $titles='L2 Домены';
	
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
			[['places_id'],'integer'],
			[['comment'], 'safe'],
        ];
    }
	
	public $linksSchema=[
		'places_id'=>[Places::class,'net_domains_ids'],
		'net_vlans_ids'=>[NetVlans::class,'domain_id'],
	];

    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return [
            'id' => 'ID',
			'name' => [
				'Название домена',
				'hint' => 'Скорее всего пояснение по территориальному расположению сети',
			],
			'places_id' => [
				'Помещение',
				'hint' => 'Помещение/площадка где локализован этот L2 Домен',
			],
			'comment' => [
				'Пояснение',
				'hint' => 'Все что нужно знать об этом домене, но что не ясно из названия',
				'type' => 'text',
			],
        ];
    }
	
	/**
	 * Place
	 * @return Networks|ActiveQuery
	 */
	public function getNetVlans()
	{
		return $this->hasMany(NetVlans::class, ['domain_id' => 'id']);
	}
	
	public function getPlace()
	{
		return $this->hasOne(Places::class, ['id' => 'places_id']);
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
		return ArrayHelper::map($list, 'id', 'name');
	}
}
