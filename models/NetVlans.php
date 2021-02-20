<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "net_vlans".
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $sname
 * @property string $segmentCode
 * @property int $vlan
 * @property int $domain_id
 * @property int $segment_id
 * @property string $comment
 * @property NetDomains $netDomain
 * @property Segments $segment
 */
class NetVlans extends \yii\db\ActiveRecord
{
	
	public static $title='Vlan\'ы';
	
	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'net_vlans';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['vlan', 'domain_id', 'segment_id'], 'integer'],
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
            'name' => 'Название',
            'vlan' => 'Vlan ID',
			'domain_id' => 'Домен L2',
			'segment_id' => 'Сегмент ИТ',
			'comment' => 'Пояснение',
        ];
    }
	
	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'id' => 'ID',
			'name' => 'Понятное обозначение',
			'vlan' => 'Номер Vlan от 0 до 4096',
			'segment_id' => 'К какому сегменту ИТ инфраструктуры относится этот Vlan',
			'domain_id' => 'В каком L2 домене находится этот Vlan',
			'comment' => 'Все что нужно знать об этом Vlan, но что не ясно из названия',
		];
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getNetDomain()
	{
		return $this->hasOne(NetDomains::className(), ['id' => 'domain_id']);
	}
	
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getSegment()
	{
		return $this->hasOne(Segments::className(), ['id' => 'segment_id']);
	}
	
	/**
	 * CSS код сегмента к которому относится VLAN
	 * @return string
	 */
	public function getSegmentCode()
	{
		if (is_object($segment=$this->segment)) return $segment->code;
		return '';
	}
	
	
	/**
	 * Search name
	 * @return string
	 */
	public function getSname()
	{
		return $this->name.' ('.$this->vlan.')';
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
		return \yii\helpers\ArrayHelper::map($list, 'id', 'sname');
	}
}
