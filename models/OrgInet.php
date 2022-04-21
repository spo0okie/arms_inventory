<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "org_inet".
 *
 * @property int $id id
 * @property string $name Имя
 * @property string $ip_addr IP Адрес
 * @property string $ip_mask Маска подсети
 * @property string $ip_gw Шлюз по умолчанию
 * @property string $ip_dns1 1й DNS сервер
 * @property string $ip_dns2 2й DNS сервер
 * @property string $type Тип подключения
 * @property int $static Статический?
 * @property int $comment Дополнительно
 * @property string $history
 * @property float $cost
 * @property float $charge
 * @property int $prov_tel_id Услуга связи
 * @property int $places_id Помещение
 * @property int $contracts_id Договор
 * @property int $services_id Услуга связи
 * @property int $networks_id Подсеть
 *
 * @property ProvTel $provTel
 * @property Contracts $contract
 * @property Places $place
 * @property Services $service
 * @property Networks $network
 * @property Partners $partner
 */
class OrgInet extends \yii\db\ActiveRecord
{

	public static $title="Вводы интернет";
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'org_inet';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
	        [['static', 'services_id', 'networks_id', 'places_id','contracts_id'], 'integer'],
	        [['cost','charge'], 'number'],
            [['name'], 'required'],
            [['ip_addr', 'ip_mask', 'ip_gw', 'ip_dns1', 'ip_dns2'], 'string', 'max' => 15],
	        [['comment','history'], 'safe'],
	        [['name','account'], 'string', 'max' => 64],
	        [['type'], 'string', 'max' => 32],
	        [['places_id'], 'exist', 'skipOnError' => true, 'targetClass' => Places::className(), 'targetAttribute' => ['places_id' => 'id']],
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return [
			'id' => 'id',
			'name' => 'Имя',
			'ip_addr' => 'IP Адрес',
			'comment' => 'Тех.условия',
			'places_id' => 'Помещение',
			'networks_id' => 'Подсеть',
			'services_id' => 'Услуга',
			'prov_tel_id' => 'Оператор связи',
			'contracts_id' => 'Договор',
			'cost' => 'Стоимость',
			'charge' => 'в т.ч. НДС',
			'totalUnpaid' => 'К оплате',
			'account' => 'Аккаунт / л/с',
			'history' => 'Заметки',
		];
	}


	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			'name' => 'Короткое название ввода. Площадка - провайдер',
			'type' => 'Медь/ оптика/ радиоканал/ спутник/ и т.д.',
			'static' => 'Если галочка стоит - статический, иначе - динамический',
			'prov_tel_id' => 'Услуга связи к которой привязан этот ввод',
			'places_id' => 'Помещение в которое заводится интернет',
			'contracts_id' => 'Договор - основание дял подключения услуги интернет',
			'account' => 'Номер лицеового счета, аккаунта иного идентификатора услуги у оператора',
			'comment' => 'Технические условия подключения<br>'
				.'Тип подключения: Ethernet/оптика/радиоканал/спутник/и т.п.<br>'
				.'Туннелирование PPTP PPPoE?<br>'
				.'Настройки IP (шлюз, маска, ДНС сервера, прочие сервера)<br>'
				.'Нужно ли настраивать статикой или выдается по DHCP<br>'
				.'Прочие детали'
			,
			'history' => 'Записная книжка этого подключения',
			'cost' => 'Стоимость услуги в месяц (планируемая стоимость, если величина плавает)',
		];
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getProvTel()
	{
		return $this->hasOne(ProvTel::className(), ['id' => 'prov_tel_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getService()
	{
		return $this->hasOne(Services::className(), ['id' => 'services_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getNetwork()
	{
		return $this->hasOne(Networks::className(), ['id' => 'networks_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getContract()
	{
		return $this->hasOne(Contracts::className(), ['id' => 'contracts_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getPlace()
	{
		return $this->hasOne(Places::className(), ['id' => 'places_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getContracts()
	{
		return $this->hasMany(Contracts::className(), ['id' => 'contracts_id'])
			->viaTable('{{%contracts_in_services}}', ['services_id' => 'services_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getPartner()
	{
		return $this->hasOne(Partners::className(), ['id' => 'partners_id'])
			->viaTable(\app\models\Services::tableName(), ['id' => 'services_id']);
	}
}
