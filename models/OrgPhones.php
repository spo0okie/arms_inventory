<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "org_phones".
 *
 * @property int $id
 * @property string $country_code Код страны
 * @property string $city_code Код города
 * @property string $account Лицевой счет
 * @property string $local_code Местный номер
 * @property string $sname Строка для поиска
 * @property string $fullNum Полный номер
 * @property int $prov_tel_id Услуга телефонии
 * @property string $comment Комментарий
 * @property float $cost
 * @property float $charge
 
 * @property ProvTel $provTel
 * @property Contracts $contracts
 * @property Services $service
 * @property Partners $partner
 * @property Places $place
 */
class OrgPhones extends \yii\db\ActiveRecord
{
	public static $title='Услуги телефонии';
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'org_phones';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['country_code', 'city_code', 'local_code', 'comment'], 'required'],
	        [['cost','charge'], 'number'],
            [['services_id','places_id','contracts_id'], 'integer'],
            [['comment','account'], 'string'],
            [['country_code', 'city_code', 'local_code'], 'string', 'max' => 10],
	        [['prov_tel_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProvTel::className(), 'targetAttribute' => ['prov_tel_id' => 'id']],
	        [['places_id'], 'exist', 'skipOnError' => true, 'targetClass' => Places::className(), 'targetAttribute' => ['places_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'country_code' => 'Код страны',
            'city_code' => 'Код города',
            'local_code' => 'Местный номер',
	        'places_id' => 'Помещение',
	        'prov_tel_id' => 'Поставщик услуги',
			'contracts_id' => 'Договор',
			'services_id' => 'Услуга связи',
	        'account' => 'Аккаунт, л/с',
	        'sname' => 'Полный номер для поиска',
	        'fullNum' => 'Полный номер',
	        'cost' => 'Стоимость',
	        'charge' => 'В т.ч. НДС',
            'comment' => 'Комментарий',
        ];
    }

	/**
	 * {@inheritdoc}
	 */
	public function attributeHints()
	{
		return [
			//'country_code' => '7 для РФ',
			//'city_code' => 'Код города',
			//'local_code' => '',
			'places_id' => 'Где подключен телефонный номер',
			'prov_tel_id' => 'Поставщик услуги',
			'contracts_id' => 'Документ на основании которого подключена эта услуга',
			'cost' => 'Стоимость номера в месяц (планируемая стоимость, если величина плавает)',
			//'account' => 'Аккаунт, л/с',
			//'sname' => 'Полный номер для поиска',
			//'fullNum' => 'Полный номер',
			//'comment' => 'Комментарий',
		];
	}

	/**
	 * Отдает строку номеров телефонов
	 * @param array $phones
	 * @return string
	 */
    public static function concatSnames($phones) {
	    $arPhones=[];
	    if (is_array($phones)&&count($phones)) {
		    foreach ($phones as $phone) {
		    	$arPhones[]=$phone->sname;
		    }
		}
		return implode(',',$arPhones);
    }

	public function getSname(){
		return '+'.$this->country_code.' ('.$this->city_code.') '.$this->local_code.' - '.$this->comment;
	}

	public function getFullNum(){
		return '+'.$this->country_code.'('.$this->city_code.')'.$this->local_code;
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
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getService()
	{
		return $this->hasOne(Services::className(), ['id' => 'services_id']);
	}
}
