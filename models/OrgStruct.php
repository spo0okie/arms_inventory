<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "org_struct".
 *
 * @property string $id Идентификатор
 * @property string $pup Вышестоящий отдел
 * @property string $name Название подразделения
 * @property int $org_id Организация
 *
 * @property Partners $partner Организация
 * @property OrgStruct $parent Родительское подразделение
 * @property OrgStruct[] $chain Цепочка от корня до текущего подразделения
 */
class OrgStruct extends \yii\db\ActiveRecord
{
	public static $title='Орг. структура';
	public static $titles='Орг. структура';
	
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'org_struct';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
			[['id', 'org_id'], 'required'],
			[['org_id'], 'integer'],
			[['id', 'pup'], 'string', 'max' => 16],
            [['name'], 'string', 'max' => 255],
			[['id', 'org_id'], 'unique', 'targetAttribute' => ['id', 'org_id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pup' => 'Pup',
            'name' => 'Name',
        ];
    }
	
	/**
	 * @return OrgStruct|null
	 */
    public function getParent() {
    	if (is_null($this->pup)) return null;
		return static::findOne($this->pup);
	}
	
	public function getChain() {
    	if (is_null($this->parent)) return [$this];
    	$chain = $this->parent->chain;
    	$chain[]=$this;
    	return $chain;
	}
	
	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getPartner()
	{
		return $this->hasOne(Partners::class, ['id' => 'org_id']);
	}
	
	public static function fetchNames(){
		$list= static::find()
			->select(['id','name'])
			->all();
		return yii\helpers\ArrayHelper::map($list, 'id', 'name');
	}

}
