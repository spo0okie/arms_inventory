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
 * @property string $account
 * @property float $cost
 * @property float $charge
 * @property int $prov_tel_id Услуга связи
 * @property int $places_id Помещение
 * @property int $contracts_id Договор
 * @property int $services_id Услуга связи
 * @property int $networks_id Подсеть	//Deprecated: заменено множественным полем ниже
 * @property int[] $networks_ids Подсети
 *
 * @property ProvTel $provTel
 * @property Contracts $contract
 * @property Places $place
 * @property Services $service
 * @property Networks $network	//Deprecated: заменено множественным полем ниже
 * @property Networks[] $networks
 * @property Partners $partner
 */
class OrgInet extends ArmsModel
{
	
	public static $title="Ввод интернет";
	public static $titles="Вводы интернет";
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'org_inet';
    }
	
	/**
	 * В списке поведений прикручиваем many-to-many contracts
	 * @return array
	 */
	public function behaviors()
	{
		return [
			[
				'class' => \voskobovich\linker\LinkerBehavior::className(),
				'relations' => [
					'networks_ids' => 'networks',
				]
			]
		];
	}
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
			[['networks_ids'], 'each','rule'=>['integer']],
			[['static', 'services_id', 'networks_id', 'places_id','archived'], 'integer'],
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
	public function attributeData()
	{
		return [
			'id' => 'id',
			'name' => [
				'Имя',
				'hint' => 'Короткое название ввода. Площадка - провайдер',
			],
			'ip_addr' => [
				'IP Адрес',
			],
			'comment' => [
				'Тех.условия',
				'hint' => 'Технические условия подключения<br>'
					.'Тип подключения: Ethernet/оптика/радиоканал/спутник/и т.п.<br>'
					.'Туннелирование PPTP PPPoE?<br>'
					.'Настройки IP (шлюз, маска, ДНС сервера, прочие сервера)<br>'
					.'Нужно ли настраивать статикой или выдается по DHCP<br>'
					.'Прочие детали'
				,
			],
			'places_id' => [
				'Помещение',
				'hint' => 'Помещение в которое заводится интернет',
			],
			'networks_ids' => [
				'Подсети',
				'hint' => 'Какие IP подсети предоставляется этим вводом интернет со стороны провайдера'
			],
			'services_id' => [
				'Услуга',
				'hint' => 'На основании договора должна быть зарегистрирована услуга связи (которая может быть комплексной),<br> а на ее основании уже заводится ввод интернет'
			],
			'prov_tel_id' => [
				'Оператор связи',
				'hint' => 'Услуга связи к которой привязан этот ввод',
			],
			'contracts_id' => [
				'Договор',
				'hint' => 'Договор - основание дял подключения услуги интернет',
			],
			'cost' => [
				'Стоимость',
				'hint' => 'Стоимость услуги в месяц (планируемая стоимость, если величина плавает)',
			],
			'charge' => [
				'в т.ч. НДС',
			],
			'totalUnpaid' => [
				'К оплате',
			],
			'account' => [
				'Аккаунт / л/с',
				'hint' => 'Номер лицеового счета, аккаунта иного идентификатора услуги у оператора',
			],
			'history' => [
				'Заметки',
				'hint' => 'Записная книжка этого подключения',
			],
			'archived' => [
				'Архивирован',
				'hint'=>'Если этот ввод интернета уже не используется, лучше его заархивировать.<br /> Он останется в БД для истории, но не будет попадаться на глаза, если явно не попросить'
			]
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
	public function getNetworks()
	{
		//return $this->hasOne(Networks::className(), ['id' => 'networks_id']);
		return $this->hasMany(Networks::className(), ['id' => 'networks_id'])
			->viaTable('{{%org_inets_in_networks}}', ['org_inets_id' => 'id']);
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
