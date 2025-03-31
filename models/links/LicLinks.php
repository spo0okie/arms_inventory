<?php

namespace app\models\links;

use app\models\Users;
use Closure;
use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\helpers\Inflector;

/**
 * This is the model class for table "lic_groups_in_comps".
 *
 * @property int $id
 * @property string|null $comment
 * @property int|null $updated_by
 * @property string|null $updated_at
 * @property int|null $created_by
 * @property string|null $created_at
 * @property string $licType
 * @property string $objType
 * @property ActiveRecord $object
 * @property ActiveRecord $lic
 * @property Users $creator
 * @property Users $updater
 */
class LicLinks extends ActiveRecord
{
	public $obj_name;
	//public $object;
	/**
	* @inheritdoc
	*/
	public function rules()
	{
		return [
			[['comment'], 'safe'],
		];
	}
	public function extraFields()
	{
		return [
			'licComment',
		];
	}
	
	
	public static $licTypes=['groups','items','keys'];
	public static $objTypes=['arms','users','comps'];

	protected static $lic=null; //тип объектов лицензий
	protected static $obj=null; //тип привязанных к лицензиям объектов
	
	public static function linksTableName ($lic,$obj) {	return 'lic_'.$lic.'_in_'.$obj;}
	public static function linksCtrlName ($lic,$obj) {	return 'update-lic-'.$lic.'-in-'.$obj;}
	public static function linksClassName ($lic,$obj) {	return Inflector::camelize(self::linksTableName($lic,$obj));}
	public static function linksLicTableName ($lic) {	return 'lic_'.$lic;}
	public static function linksObjTableName ($obj) {	return str_replace('arms','techs',$obj);} //с тех пор как вместо армов у нас оборудование
	public static function linksLicIdField ($lic) {		return 'lic_'.$lic.'_id';}
	public static function linksObjIdField ($obj) {		return $obj.'_id';}
	
    public static function tableName(){		return static::linksTableName(static::$lic,static::$obj);}
	public static function licTableName(){	return static::linksLicTableName(static::$lic);}
	public static function objTableName(){	return static::linksObjTableName (static::$obj);}
	public static function licIdField(){	return static::linksLicIdField(static::$lic);}
	public static function objIdField(){	return static::linksObjIdField(static::$obj);}
	public static function objClass(){		return Inflector::camelize(static::objTableName());}
	public static function licClass(){		return Inflector::camelize(static::licTableName());}
	public static function linksClass(){	return Inflector::camelize(static::tableName());}
	
	
	public function getObjType() {return static::$obj;}
	public function getLicType() {return static::$lic;}
	public function getObjName() {
		switch (static::$obj){
			case 'arms':
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				return $this->object->num;
			case 'comps':
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				return $this->object->name;
			case 'users':
				/** @noinspection PhpPossiblePolymorphicInvocationInspection */
				return $this->object->Ename;
		}
		return null;
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getObject()
	{
		return $this->hasOne('\\app\\models\\'.static::objClass(), ['id' => static::objIdField()]);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getLic()
	{
		return $this->hasOne('\\app\\models\\'.static::licClass(), ['id' => static::licIdField()]);
	}
	
	public function getLicComment()
	{
		/** @noinspection PhpPossiblePolymorphicInvocationInspection */
		return $this->lic->comment;
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getCreator()
	{
		return $this->hasOne(Users::class, ['id' => 'created_by']);
	}
	
	/**
	 * @return ActiveQuery
	 */
	public function getUpdater()
	{
		return $this->hasOne(Users::class, ['id' => 'updated_by']);
	}
	
	
	public static function findLinks($licId=null,$objId=null) {
		$records=static::find()
			->joinWith(['lic','object']);
			
		if (!is_null($objId))
			$records->andWhere([static::objIdField()=>$objId]);

		if (!is_null($licId))
			$records->andWhere([static::licIdField()=>$licId]);
			
		
		return $records->all();
	}
	
	/**
	 * Возвращает правила обновления полей в промежуточных таблицах many-to-many связей
	 * https://github.com/voskobovich/yii2-linker-behavior
	 * @param $model
	 * @return Closure[]
	 */
	public static function fieldsBehaviour($model) {
		return [
			//Записываем комментарий для новых записей (старые уже прокомментированы)
			'comment' => function($updater, $relatedPk, $rowCondition) use ($model) {
				if ($rowCondition->isNewRecord)
					return $model->linkComment;
				else
					return $rowCondition->oldValue;
			},
			//Обновляем пользователя и время создания обновления для новых записей
			//изменение записей возможно только для обновления комментария,
			//но это нужно делать вне алгоритма many-to-many поведения.
			//так что тут такое не рассматриваем
			'created_at' => function($updater, $relatedPk, $rowCondition){
				if ($rowCondition->isNewRecord)
					return new Expression('NOW()');
				else
					return $rowCondition->oldValue;
			},
			'updated_at' => function($updater, $relatedPk, $rowCondition){
			if ($rowCondition->isNewRecord)
				return new Expression('NOW()');
			else
				return $rowCondition->oldValue;
			},
			//аналогично с пользователями
			'created_by' => function($updater, $relatedPk, $rowCondition){
			if ($rowCondition->isNewRecord)
				return Yii::$app->user->id;
			else
				return $rowCondition->oldValue;
			},
			//аналогично с пользователями
			'updated_by' => function($updater, $relatedPk, $rowCondition){
				if ($rowCondition->isNewRecord)
					return Yii::$app->user->id;
				else
					return $rowCondition->oldValue;
			},
		];
	}
	
	/**
	 * Найти все связанные с лицензией объекты
	 * @param $lic string тип лицензии (groups/items/keys)
	 * @param $licId integer идентификатор объекта лицензии
	 * @return array
	 */
	public static function findForLic(string $lic, int $licId) {
		$objs=[];
		foreach (self::$objTypes as $objType) {
			$objClass='\app\\models\\links\\'.static::linksClassName($lic,$objType);
			/** @noinspection PhpUndefinedMethodInspection */
			$objs=array_merge($objs,$objClass::findLinks($licId));
		}
		return $objs;
	}
	
	public static function findProductLicenses(
		int $productId=null,
		string $objectType=null,
		string $licenseType=null,
		int $objId=null,
		int $licId=null
	) {
		//среди каких типов объектов будем искать (переданный тип или все)
		//пользователь, АРМ, ОС
		if (!is_null($objectType))
			$objTypes=[$objectType];
		else {
			$objTypes=self::$objTypes;
			$objId=null; //нельзя искать по номеру объекта если их много типов
		}
		
		//среди каких групп лицензий будем искать (переданный или все)
		if (!is_null($licenseType))
			$licTypes=[$licenseType];
		else {
			$licTypes=self::$licTypes;
			$licId=null;  //нельзя искать по номеру лицензии если их много типов
		}
		
		$items=[];
		//var_dump($objTypes);
		//var_dump($licTypes);
		foreach ($objTypes as $objType) {
			foreach ($licTypes as $licType) {
				$objClass = '\app\\models\\links\\'.static::linksClassName($licType, $objType);
				/** @noinspection PhpUndefinedMethodInspection */
				foreach ($objClass::findLinks($licId,$objId) as $item) {
					if (!is_null($productId)) {
						if (array_search($productId,$item->lic->soft_ids)!==false) {
							$items[]=$item;
						}
					} else {
						$items[]=$item;
					}
				}
			}
		}
		return $items;
	}
	
	/*public static function updateLink(string $lic, string $obj, int $id, string $comment) {
		$link=new LicLinks($lic,$obj);
		Yii::$app->db->createCommand()->update(
			$link->tableName(),
			[
				'comment'=>$comment,
				'updated_by'=> Yii::$app->user->id,
				'updated_at'=>new Expression('NOW()')
			],
			['id'=>$id]
		)->execute();
	}*/
	
	public function beforeSave($insert)
	{
		if (is_null($this->created_at))
			$this->created_at=new Expression('NOW()');
		
		if (is_null($this->created_by))
			$this->created_by= Yii::$app->user->id;
		
		$this->updated_at=new Expression('NOW()');
		$this->updated_by= Yii::$app->user->id;

		return parent::beforeSave($insert);
		
	}
}
