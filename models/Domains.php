<?php

namespace app\models;

use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "domains".
 *
 * @property int $id Идентификатор
 * @property string $name Имя
 * @property string $fqdn FQDN
 * @property string $comment Комментарий
 *
 * @property CompNames[] $compNames
 */
class Domains extends \yii\db\ActiveRecord
{
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

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'Идентификатор',
            'name' => 'Имя домена',
            'fqdn' => 'FQDN',
            'comment' => 'Комментарий',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCompNames()
    {
        return $this->hasMany(CompNames::className(), ['domain_id' => 'id']);
    }

    public static function fetchNames(){
        $list= static::find()
            ->select(['id','name'])
            ->all();
        return \yii\helpers\ArrayHelper::map($list, 'id', 'name');
    }

	public static function findByName($name){
		$domain=static::find()->where(['LOWER(name)'=>mb_strtolower($name)])->one();
		if (!is_object($domain)) return null;
		/** @var Domains $domain */
		return $domain->id;
	}

	public static function findByFQDN($name){
		$domain=static::find()->where(['LOWER(fqdn)'=>mb_strtolower($name)])->one();
		if (!is_object($domain)) return null;
		/** @var Domains $domain */
		return $domain->id;
	}
	
	/**
	 * Должно вытащить ID домена и вернуть [id домена, имя компа, имя домена]
	 * false в случае ошибки формата имени компа
	 * вместо имени домена false если имя домена не найдено в имени компа
	 * вместо имени домена null если имя домена есть, но он не найден
	 * @param $name
	 * @return array|false
	 */
	public static function fetchFromCompName($name) {
		$slashPos=mb_strpos($name,'\\');
		$dotPos=mb_strpos($name,'.');
		if ($slashPos && $dotPos) return false;

		//DOMAIN\comp
		if ($slashPos) {
			$tokens=explode('\\',$name);
			if (count($tokens)>2) return false;
			
			return [static::findByName($tokens[0]),$tokens[1],$tokens[0]];
		}
		
		//FQDN
		if ($dotPos) {
			$tokens=explode('.',$name);
			$compName=$tokens[0];
			unset ($tokens[0]);
			$domainFqdn=implode('.',$tokens);
			return [static::findByFQDN($domainFqdn),$compName,$domainFqdn];
		}
		
		//nor any of above
		return [false,$name,''];
	}

}
