<?php

namespace app\models;

use Yii;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "org_struct".
 *
 * @property string $hr_id Идентификатор HR
 * @property string $parent_hr_id Вышестоящий отдел (идентификатор HR)
 * @property string $name Название подразделения
 * @property int $id свой ID
 * @property int $org_id Организация
 * @property int $parent_id Родитель
 *
 * @property Partners $partner Организация
 * @property OrgStruct $parent Родительское подразделение (на основе связи id->parent_id)
 * @property OrgStruct $hrParent Родительское подразделение (на основе связи hr_id->hr_parent_id)
 * @property OrgStruct[] $chain Цепочка от корня до текущего подразделения
 * @property OrgStruct[] $children Дочерние подразделения (на основе связи id->parent_id)
 * @property OrgStruct[] $nrChildren Дочерние подразделения (на основе связи hr_id->hr_parent_id)
 * @property Users[] $users Сотрудники этого подразделения
 */
class OrgStruct extends ArmsModel
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
	 * @noinspection PhpUnusedParameterInspection
     */
    public function rules()
    {
        return [
			['hr_id', 'filter', 'filter' => function ($value) {
				/* генерируем ID для новой записи если он пустой*/
				if (strlen($this->hr_id) || !$this->isNewRecord) return $this->hr_id;
				
				return (string)static::fetchNextValue('hr_id');
			}],
			[['org_id','name'], 'required'],
			[['org_id','id','parent_id'], 'integer'],
			[['hr_id', 'parent_hr_id'], 'string', 'max' => 16],
            [['name'], 'string', 'max' => 255],
			[['hr_id', 'org_id'], 'unique', 'targetAttribute' => ['hr_id', 'org_id']],
			[['parent_hr_id'],	'validateRecursiveLink', 'params'=>['getLink' => 'hrParent']],
		];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return [
			'hr_id' => [
				'ID подразделения в HR БД',
			],
			'org_id' => [
				'Организация',
			],
            'parent_hr_id' => [
            	'Родительское подразделение',
			],
            'name' => 'Наименование подразделения',
        ];
    }
	
	/**
	 * Найти родителя по hr_id (для перестроение связей согласно HR DB)
	 * @return OrgStruct|ActiveQuery|null
	 */
	public function getHrParent() {
		return $this->hasOne(OrgStruct::class, ['hr_id' => 'parent_hr_id','org_id'=>'org_id']);
	}
	
	/**
	 * Найти родителя по нашим ID (уже перестроенным после импорта на основе HR ID)
	 * @return OrgStruct|ActiveQuery|null
	 */
	public function getParent() {
		return $this->hasOne(OrgStruct::class, ['id' => 'parent_id']);
	}
	
	/**
	 * Найти потомков по связям через HR ID
	 * @return OrgStruct|ActiveQuery|null
	 */
	public function getHrChildren() {
		return $this->hasMany(OrgStruct::class, ['parent_hr_id' => 'hr_id','org_id'=>'org_id']);
	}
	
	/**
	 * найти потомков по ID
	 * @return OrgStruct|ActiveQuery|null
	 */
	public function getChildren() {
		return $this->hasMany(OrgStruct::class, ['parent_id' => 'id']);
	}
	
	/**
	 * @return Users|ActiveQuery|null
	 */
	public function getUsers() {
		return $this->hasMany(Users::class, ['Orgeh' => 'hr_id', 'org_id'=>'org_id']);
	}
	
	
	public function getChain() {
    	if (is_null($this->parent)) return [$this];
    	$chain = $this->parent->chain;
    	$chain[]=$this;
    	return $chain;
	}
	
	/**
	 * @return Partners|ActiveQuery|null
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
	
	public static function fetchOrgNames($org_id){
		$list= static::find()
			->select(['hr_id','name'])
			->where(['org_id'=>$org_id])
			->all();
		return yii\helpers\ArrayHelper::map($list, 'hr_id', 'name');
	}
	
	public function reverseLinks()
	{
		return [
			'Дочерние подразделения' => $this->children,
			'Сотрудники' => $this->users,
		];
	}
	
	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			if ($this->parent_hr_id) {
				if (is_object($parent=$this->hrParent))
					$this->parent_id=$parent->id;
			}
			return true;
		}
		return false;
	}
	
}
