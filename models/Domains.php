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
		$name=mb_strtolower($name);
		$list = static::find()->select(['id','name'])->asArray(true)->all();
		foreach ($list as $item) {
			if (!strcmp(mb_strtolower($item['name']),$name)) return $item['id'];
		}
		return null;
	}

	public static function findByFQDN($name){
		$name=mb_strtolower($name);
		$list = static::find()->select(['id','fqdn'])->asArray(true)->all();
		foreach ($list as $item) {
			if (!strcmp(mb_strtolower($item['fqdn']),$name)) return $item['id'];
		}
		return null;
	}
	
	/**
	 * Должно вытащить ID домена и вернуть
	 * вернет номер домена, Null если не нашлось или False если ошибка в формате имени
	 * @param $name
	 * @return array|false|null
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
		return [null,$name,''];
	}

}
