<?php

namespace app\models;

use yii\base\InvalidConfigException;
use yii\db\ActiveQuery;

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
 * @property int $places_id Помещение
 * @property int $services_id Услуга
 * @property string $comment Комментарий
 * @property float $cost
 * @property float $charge
 * @property bool $archived
 * @property bool $providesNumber
 * @property string $title
 * @property string $untitledComment
 
 * @property Services $service
 * @property Places $place
 * @property Services $provTel старое поле используется только в миграциях до новой версии
 */
class OrgPhones extends ArmsModel
{
	public static $title='Телефонный номер';
	public static $titles='Телефонные номера';
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
			[['services_id'],'required'],
	        [['cost','charge'], 'number'],
            [['services_id','places_id','archived'], 'integer'],
            [['comment','account'], 'string'],
            [['country_code', 'city_code', 'local_code'], 'string', 'max' => 10],
	        //[['prov_tel_id'], 'exist', 'skipOnError' => true, 'targetClass' => ProvTel::class, 'targetAttribute' => ['prov_tel_id' => 'id']],
	        [['places_id'], 'exist', 'skipOnError' => true, 'targetClass' => Places::class, 'targetAttribute' => ['places_id' => 'id']],
        ];
    }
	
	public $linksSchema=[
		'services_id' => [Services::class,'org_phones_ids'],
		'places_id' => [Places::class,'org_phones_ids'],
	];

    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return [
            'id' => 'ID',
            'country_code' => 'Код страны',
            'city_code' => 'Код города',
            'local_code' => 'Местный номер',
	        'places_id' => [
	        	'Помещение',
				'hint' => 'Куда подключен телефонный номер.<br />'
					.'(С точки зрения телефонии, т.е. где он будет звонить?)',
				'placeholder'=>'Укажите площадку/офис, для которого предоставляется тел. связь'
			
			],
			'services_id' => [
				'Услуга связи',
				'hint' => 'Услуга связи в рамках которой предоставляется номер.<br />'.
					'Контрагент, договор, счета, график предоставления и прочее все берется из услуги',
				'placeholder'=>'Укажите услугу связи, в рамках которой тел. связь'
			],
	        'account' => 'Аккаунт, л/с',
	        'sname' => 'Полный номер для поиска',
	        'fullNum' => 'Номер телефона',
	        'cost' => [
	        	'Стоимость',
				'hint' => 'Стоимость номера в месяц (планируемая стоимость, если величина плавает)',
			],
	        'charge' => 'В т.ч. НДС',
            'comment' => [
				'Описание',
				'hint'=>'Любые пояснения о задачах этого номера<br>'
					.'<b>Первая строка</b> будет вынесена в заголовок, который будет виден в списке номеров',
				'type'=>'text'
			],
			'archived' => [
				'Архивирован',
				'hint'=>'Если номер уже не используется, лучше его заархивировать.<br /> Он останется в БД для истории, но не будет попадаться на глаза, если явно не попросить'
			]
        ];
    }


	/**
	 * Отдает строку номеров телефонов
	 * @param array $phones
	 * @return string
	 */
    public static function concatSnames(array $phones) {
	    $arPhones=[];
	    if (is_array($phones)&&count($phones)) {
		    foreach ($phones as $phone) {
		    	$arPhones[]=$phone->sname;
		    }
		}
		return implode(',',$arPhones);
    }

    public function getProvidesNumber() {
    	return !(
    		empty($this->country_code)
		||
			empty($this->city_code)
		||
			empty($this->local_code)
		);
	}
	
	/**
	 * Комментарий без первой строки заголовка (если услуга не предоставляет номер)
	 * @return string
	 */
    public function getUntitledComment() {
    	//если номер есть, то он и заголовок
		if ($this->providesNumber) return $this->comment;
		//иначе разбиваем на строки
		$strings=explode("\n",$this->comment);
		//если их одна, то это и есть заголовок, а комментария нет
		if (count($strings)<2) return '';
		//выкидываем из строк заголовок
		unset ($strings[0]);
		return implode("\n",$strings);
	}
	
	public function getTitle(){
    	if ($this->providesNumber) return $this->fullNum;
		return explode("\n",$this->comment)[0];
	}
 
	public function getSname(){
		return $this->title.
			(
				empty($this->untitledComment)?'':(' - '.$this->untitledComment)
			);
	}

	public function getFullNum(){
		return '+'.$this->country_code.'('.$this->city_code.')'.$this->local_code;
	}

	/**
	 * @return ActiveQuery
	 */
	public function getPlace()
	{
		return $this->hasOne(Places::class, ['id' => 'places_id']);
	}
	
	
	/**
	 * @return ActiveQuery
	 * @throws InvalidConfigException
	 */
	public function getPartner()
	{
		return $this->hasOne(Partners::class, ['id' => 'partners_id'])
			->viaTable(Services::tableName(), ['id' => 'services_id']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getService()
	{
		return $this->hasOne(Services::class, ['id' => 'services_id']);
	}
}
