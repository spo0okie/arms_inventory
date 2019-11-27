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
 * @property Contracts $contract
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
            [['country_code', 'city_code', 'local_code', 'prov_tel_id', 'comment'], 'required'],
	        [['cost','charge'], 'number'],
            [['prov_tel_id','places_id','contracts_id'], 'integer'],
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
	        'account' => 'Аккаунт, л/с',
	        'sname' => 'Полный номер для поиска',
	        'fullNum' => 'Полный номер',
	        'cost' => 'Стоимость',
	        'charge' => 'НДС',
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
			'places_id' => 'Где оказывается услуга связи',
			'prov_tel_id' => 'Поставщик услуги',
			'contracts_id' => 'Документ на основании которого подключена эта услуга',
			'cost' => 'Стоимость услуги в месяц (планируемая стоимость, если величина плавает)',
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
	public function getContract()
	{
		return $this->hasOne(Contracts::className(), ['id' => 'contracts_id']);
	}
}
