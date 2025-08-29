<?php

namespace app\models;

use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "net_vlans".
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $sname
 * @property string $domainCode
 * @property int $vlan
 * @property int $domain_id
 * @property int $segment_id
 * @property string $comment
 * @property NetDomains $netDomain
 * @property Segments $segment
 * @property Networks $networks
 */
class NetVlans extends ArmsModel
{
	
	public static $title='Vlan';
	public static $titles='Vlan\'ы';
	
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
            [['domain_id'], 'integer'],
			[
				['vlan', 'domain_id'], 'unique',
				'targetAttribute' => ['vlan', 'domain_id'],
				'message'=>'Такая комбинация домена и Vlan уже есть',
				'when' => fn($model)=> !str_contains($model->vlan, '-') // Проверяем, что VLAN не является диапазоном
			],
			[['name'], 'string', 'max' => 255],
			[['comment'], 'safe'],
			[['vlan'], 'validateVlanRange'], // Добавляем валидацию диапазона
		];
    }
	
	public $linksSchema=[
		'domain_id'=>[NetDomains::class,'net_vlans_ids'],
		'networks_ids'=>[Networks::class,'vlan_id'],
	];

    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return [
            'id' => [
            	'ID',
			],
			'name' => [
				'Название',
				'hint' => 'Понятное обозначение<br>'
					.'Можно использовать макрос {VLAN} для подстановки номера Vlan<br>'
					.'(удобно при создании нескольких VLAN указанием диапазона в поле Vlan ID)<br>'
					.Networks::$latinNameHint,
			],
			'networks_ids' => [
				'Сети',
			],
            'vlan' => [
            	'Vlan ID',
				'hint' => 'Номер Vlan от 1 до 4094<br>'
					.'При указании диапазона (например 10-20) будут созданы все Vlan в этом диапазоне<br>'
					. '<i>(0, 1002-1005 и 4095 зарезервированы)</i>',
			],
			'domain_id' => [
				'Домен L2',
				'hint' => 'В каком L2 домене находится этот Vlan'
				. '<br><i>(Для разделения VLAN с одинаковыми номерами, но в разных доменах)</i>',
				'placeholder' => 'Выберите L2 Домен',
			],
			'comment' => [
				'Пояснение',
				'comment' => 'Все что нужно знать об этом Vlan, но что не ясно из названия',
				'type' => 'text',
			],
        ];
    }
	
	
	/**
	 * Валидация диапазона VLAN.
	 * @param string $attribute
	 * @param array $params
	 */
	public function validateVlanRange($attribute, $params)
	{
		if (strpos($this->$attribute, '-') !== false) {
			if (!$this->isNewRecord) {
				$this->addError($attribute, 'Диапазон VLAN можно задавать только при создании.');
				return;
			}
			
			[$start, $end] = explode('-', $this->$attribute);
			$start = (int)$start;
			$end = (int)$end;
			
			if ($start < 1 || $end > 4096 || $start > $end) {
				$this->addError($attribute, 'Диапазон VLAN должен быть в пределах 1-4096 и корректным.');
			}
		} elseif ($this->$attribute < 1 || $this->$attribute > 4096) {
			$this->addError($attribute, 'VLAN должен быть в пределах 1-4096.');
		}
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getNetDomain()
	{
		return $this->hasOne(NetDomains::class, ['id' => 'domain_id']);
	}
	
	
	
	/**
	 * CSS код сегмента к которому относится VLAN
	 * @return string
	 */
	public function getDomainCode()
	{
		if (is_object($domain=$this->netDomain)) return 'net-domain-'.$domain->name;
		return '';
	}
	
	/**
	 * @return ActiveQuery|Networks
	 */
	public function getNetworks()
	{
		return $this->hasMany(Networks::class, ['vlan_id' => 'id']);
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
		return ArrayHelper::map($list, 'id', 'sname');
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			if (str_contains($this->vlan, '-')) {
				[$start, $end] = explode('-', $this->vlan);
				$start = (int)$start;
				$end = (int)$end;
				
				for ($vlan = $start; $vlan <= $end-1; $vlan++) {
					$model = new self();
					$model->attributes = $this->attributes;
					$model->vlan = $vlan;
					$model->save(); // Сохраняем
				}
				
				//оставляем себе последний VLAN вместо диапазона
				$this->vlan = $vlan;
				
			}
			
			$this->name=str_replace('{VLAN}',$this->vlan,$this->name);
			
			
			return true;
		}
		
		return false;
	}
}
