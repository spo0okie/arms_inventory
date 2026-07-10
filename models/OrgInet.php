<?php

namespace app\models;

use app\models\base\ArmsModel;
use app\types\TextType;
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

	public static function modelDescription(): string
	{
		return 'Вводы интернета: подключения от операторов связи по площадкам — '
			.'технические условия, настройки IP, стоимость, договор и оператор.';
	}
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
				'class' => \voskobovich\linker\LinkerBehavior::class,
				'relations' => [
					'networks_ids' => 'networks',
				]
			]
		];
	}
	
	public $linksSchema=[
		'networks_ids'=>	[Networks::class,'org_inets_ids'],
		'services_id'=>	[Services::class,'org_inets_ids'],
		'places_id'=>	[Places::class,'org_inets_ids'],
		'networks_id'=>	Networks::class,	//deprecated одиночная ссылка (read-only)
	];
    
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
			[['networks_ids'], 'each','rule'=>['integer']],
			[['static', 'services_id', 'places_id','archived'], 'integer'],
			[['services_id'], 'required'],
	        [['cost','charge'], 'number'],
            [['name'], 'required'],
            [['ip_addr', 'ip_mask', 'ip_gw', 'ip_dns1', 'ip_dns2'], 'string', 'max' => 15],
	        [['comment','history'], 'safe'],
	        [['name','account'], 'string', 'max' => 64],
	        [['type'], 'string', 'max' => 32],
	        [['places_id'], 'exist', 'skipOnError' => true, 'targetClass' => Places::class, 'targetAttribute' => ['places_id' => 'id']],
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return [
			'account' => [
				'Аккаунт / л/с',
				'hint' => 'Номер лицеового счета, аккаунта иного идентификатора услуги у оператора',
			],
			'archived' => [
				'Архивирован',
				'hint'=>'Если этот ввод интернета уже не используется, лучше его заархивировать.<br /> Он останется в БД для истории, но не будет попадаться на глаза, если явно не попросить'
			],
			'charge' => [
				'в т.ч. НДС',
				'hint' => 'Сумма НДС в составе стоимости',
				'typeClass'=>\app\types\MoneyType::class, 'decimals'=>0, 'currencyPath'=>'service.currency',
			],
			'contracts_id' => [
				'Договор',
				'hint' => 'Договор - основание дял подключения услуги интернет',
			],
			'cost' => [
				'Стоимость',
				'hint' => 'Стоимость услуги в месяц (планируемая стоимость, если величина плавает)',
				'typeClass'=>\app\types\MoneyType::class, 'decimals'=>0, 'currencyPath'=>'service.currency',
			],
			'history' => [
				'Заметки',
				'hint' => 'Записная книжка этого подключения',
				'type' => 'text',
				'typeClass' => TextType::class,
			],
			'comment' => [
				'Тех.условия',
				'hint' => 'Технические условия подключения<br>'
					.'Тип подключения: Ethernet/оптика/радиоканал/спутник/и т.п.<br>'
					.'Туннелирование PPTP PPPoE?<br>'
					.'Настройки IP (шлюз, маска, ДНС сервера, прочие сервера)<br>'
					.'Нужно ли настраивать статикой или выдается по DHCP<br>'
					.'Прочие детали',
				'type' => 'text',
				'typeClass' => \app\types\TextType::class,
			
			],
			'ip_addr' => [
				'IP Адрес',
				'hint' => 'IP адрес, выданный провайдером (для статической настройки)',
			],
			'ip_mask' => ['Маска подсети','hint' => 'Маска подсети, выданная провайдером'],
			'ip_gw' => ['Шлюз по умолчанию','hint' => 'Шлюз по умолчанию, выданный провайдером'],
			'ip_dns1' => ['1й DNS сервер','hint' => 'Первый DNS сервер провайдера'],
			'ip_dns2' => ['2й DNS сервер','hint' => 'Второй DNS сервер провайдера'],
			'type' => ['Тип подключения','hint' => 'Тип подключения (Ethernet, PPPoE, оптика и т.п.)'],
			'static' => ['Статический','hint' => 'Настройки задаются статически (не выдаются по DHCP)'],
			'name' => [
				'Имя',
				'hint' => 'Короткое название ввода. Площадка - провайдер',
			],
			'networks_ids' => [
				'Подсети',
				'hint' => 'Какие IP подсети предоставляется этим вводом интернет со стороны провайдера',
				'placeholder'=>'Укажите предоставляемые подсети'
			],
			'places_id' => [
				'Помещение',
				'hint' => 'Помещение в которое заводится интернет',
				'placeholder'=>'Укажите площадку/офис, для которого предоставляется интернет'
			],
			'prov_tel_id' => [
				'Оператор связи',
				'hint' => 'Услуга связи к которой привязан этот ввод',
			],
			'services_id' => [
				'Услуга',
				'hint' => 'На основании договора должна быть зарегистрирована услуга связи (которая может быть комплексной),<br>'
					.'а на ее основании уже заводится ввод интернет',
				'placeholder' => 'Выберите услугу связи',
			],
			'totalUnpaid' => [
				'К оплате',
				'typeClass' => \app\types\FloatType::class,
			],
		];
	}

	
	
	/**
	 * @return \yii\db\ActiveQuery
	
	public function getProvTel()
	{
		return $this->hasOne(ProvTel::class, ['id' => 'prov_tel_id']);
	} */
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getService()
	{
		return $this->hasOne(Services::class, ['id' => 'services_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getNetwork()
	{
		return $this->hasOne(Networks::class, ['id' => 'networks_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getNetworks()
	{
		//return $this->hasOne(Networks::class, ['id' => 'networks_id']);
		return $this->hasMany(Networks::class, ['id' => 'networks_id'])
			->viaTable('{{%org_inets_in_networks}}', ['org_inets_id' => 'id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getContract()
	{
		return $this->hasOne(Contracts::class, ['id' => 'contracts_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getPlace()
	{
		return $this->hasOne(Places::class, ['id' => 'places_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getContracts()
	{
		return $this->hasMany(Contracts::class, ['id' => 'contracts_id'])
			->viaTable('{{%contracts_in_services}}', ['services_id' => 'services_id']);
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getPartner()
	{
		return $this->hasOne(Partners::class, ['id' => 'partners_id'])
			->viaTable(\app\models\Services::tableName(), ['id' => 'services_id']);
	}
}
