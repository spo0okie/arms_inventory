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
 * @property int $cost
 * @property int $prov_tel_id Услуга связи
 * @property int $places_id Помещение
 *
 * @property ProvTel $provTel
 * @property Contracts $contract
 * @property Places $places
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
            [['static', 'prov_tel_id', 'places_id','contracts_id','cost'], 'integer'],
            [['prov_tel_id','name'], 'required'],
            [['ip_addr', 'ip_mask', 'ip_gw', 'ip_dns1', 'ip_dns2'], 'string', 'max' => 15],
	        [['comment','history'], 'safe'],
	        [['name','account'], 'string', 'max' => 64],
	        [['type'], 'string', 'max' => 32],
            [['prov_tel_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProvTel::className(), 'targetAttribute' => ['prov_tel_id' => 'id']],
	        [['places_id'], 'exist', 'skipOnError' => true, 'targetClass' => Places::className(), 'targetAttribute' => ['places_id' => 'id']],
	        [['contracts_id'], 'exist', 'skipOnError' => true, 'targetClass' => Places::className(), 'targetAttribute' => ['places_id' => 'id']],
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
			'ip_mask' => 'Маска подсети',
			'ip_gw' => 'Шлюз по умолчанию',
			'ip_dns1' => '1й DNS сервер',
			'ip_dns2' => '2й DNS сервер',
			'type' => 'Тип подключения',
			'static' => 'Статический?',
			'comment' => 'Пояснение',
			'places_id' => 'Помещение',
			'prov_tel_id' => 'Оператор связи',
			'contracts_id' => 'Договор',
			'cost' => 'Стоимость',
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
			'comment' => 'Короткий комментарий',
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
	public function getContract()
	{
		return $this->hasOne(Contracts::className(), ['id' => 'contracts_id']);
	}

	/**
     * @return \yii\db\ActiveQuery
     */
    public function getPlaces()
    {
        return $this->hasOne(Places::className(), ['id' => 'places_id']);
    }
}
