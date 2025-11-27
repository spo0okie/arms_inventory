<?php

namespace app\models;


use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "domains".
 *
 * @property int $id Идентификатор
 * @property string $name Имя
 * @property string $fqdn FQDN
 * @property string $comment Комментарий
 *
 */
class Domains extends ArmsModel
{
	
	public static $title='Домен';
	public static $titles='Домены';
	
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'domains';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'fqdn', 'comment'], 'required'],
            [['name'], 'string', 'max' => 16],
            [['fqdn'], 'string', 'max' => 128],
            [['comment'], 'string', 'max' => 255],
            [['name', 'fqdn'], 'unique', 'targetAttribute' => ['name', 'fqdn']],
        ];
    }
	
	public $linksSchema=[
		'comps_ids'=>[Comps::class,'domain_id'],
		'techs_ids'=>[Techs::class,'domain_id'],
	];

    /**
     * @inheritdoc
     */
    public function attributeData()
    {
        return [
            'id' => 'Идентификатор',
            'name' => 'Имя домена',
            'fqdn' => 'FQDN',
            'comment' => 'Комментарий',
        ];
    }
	
	public function getComps()
	{
		return $this->hasMany(Comps::class,['domain_id'=>'id']);
	}
	public function getTechs()
	{
		return $this->hasMany(Techs::class,['domain_id'=>'id']);
	}
	
	
	
	public static function fetchNames(){
        $list= static::find()
            ->select(['id','name'])
            ->all();
        return ArrayHelper::map($list, 'id', 'name');
    }
	
	/**
	 * ID домена по имени
	 * @param $name
	 * @return int|null
	 */
	public static function findByName($name){
		$domain=static::find()->where(['LOWER(name)'=>mb_strtolower($name)])->one();
		if (!is_object($domain)) return null;
		/** @var Domains $domain */
		return $domain->id;
	}
	
	/**
	 * ID домена по FQDN
	 * @param $name
	 * @return int|null
	 */
	public static function findByFQDN($name){
		$domain=static::find()->where(['LOWER(fqdn)'=>mb_strtolower($name)])->one();
		if (!is_object($domain)) return null;
		/** @var Domains $domain */
		return $domain->id;
	}
	
	/**
	 * ID домена по имени или FQDN
	 * @param $name
	 * @return int|null
	 */
	public static function findByAnyName($name) {
		if (mb_strpos($name,'.')) { //ищем точно по FQDN!
			return static::findByFQDN($name);
		}
		//имя без точек, пробуем найти его по имени
    	if (!is_null($domain_id=static::findByName($name))) return $domain_id;
    	//ну либо это домен первого уровня
    	return static::findByFQDN($name);
			
	}
	
	/**
	 * Должно вытащить ID домена и вернуть [id домена, имя компа, имя домена]
	 * false в случае ошибки формата имени компа
	 * вместо имени домена false если имя домена не найдено в имени компа
	 * вместо имени домена null если имя домена есть, но он не найден
	 * @param      $name
	 * @param string $defaultDomain	можно передать домен по умолчанию (если его нет в имени явно)
	 * @return array|false
	 */
	public static function fetchFromCompName($name,$defaultDomain='',$enableEmptyDomain=false) {
		$slashPos=mb_strpos($name,'\\');
		$dotPos=mb_strpos($name,'.');
		if (!$slashPos && !$dotPos && !$defaultDomain) {
			if ($enableEmptyDomain)	return [false,$name,''];//no domain
			
			return false;
		}

		//DOMAIN\comp
		if ($slashPos) {
			$tokens=explode('\\',$name);
			if (count($tokens)>2) return false;
			$domain_id=(mb_strpos($tokens[0],'.')!==false)?
				(static::findByFQDN($tokens[0])):	//FQDN\comp ¯\_(ツ)_/¯
				(static::findByName($tokens[0]));	//DOMAIN\comp
			return [$domain_id,$tokens[1],$tokens[0]];
		}
		
		//FQDN
		if ($dotPos) {
			$tokens=explode('.',$name);
			$compName=$tokens[0];
			unset ($tokens[0]);
			$domainFqdn=implode('.',$tokens);
			return [static::findByFQDN($domainFqdn),$compName,$domainFqdn];
		}
		
		//nor any of above -> act as MS WORKGROUP PC
		return [static::findByName($defaultDomain),$name,$defaultDomain];
		
	}
	
	
	/**
	 * Проверка hostname для разных форматов ввода
	 * @param string      $hostname для валидации
	 * @param Comps|Techs $object объект, в который пишем ошибки
	 * @param string      $attr атрибут для записи ошибки
	 * @return string
	 */
	public static function validateHostname(
		string $hostname,
		Techs|Comps $object,
		string $attr='name'
	): string
	{
		$defaultDomain=$object->domainName??($object->isNewRecord?\Yii::$app->params['domains.default']:'');
		/* убираем посторонние символы из MAC*/
		$parseName=Domains::fetchFromCompName($hostname,$defaultDomain);
		if ($parseName===false) $object->addError($attr,'Некорректный формат hostname или нет домена');
		if (is_array($parseName)) {
			$domain_id=$parseName[0];
			if (!is_null($domain_id) && ($domain_id!==false)){
				$object->domain_id = $domain_id;
				return $parseName[1];
			}
		}
		return $hostname;
	}
	
}
