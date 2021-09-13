<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "login_journal".
 *
 * @property int $id id
 * @property string $time Дата и время
 * @property string $age Возраст события
 * @property string $comp_name Компьютер
 * @property int $comps_id ID Компьютера
 * @property string $user_login Пользователь
 * @property string $users_id ID Пользователя
 * @property string $compName имя компа
 * @property string $userDescr имя компа
 * @property string $compFqdn FQDN компа
 *
 * @property Users $user
 * @property Comps $comp
 */
class LoginJournal extends \yii\db\ActiveRecord
{

	public static $title='Входы в ПК';

	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'login_journal';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['time'], 'safe'],
            [['comp_name', 'user_login'], 'required'],
            [['comps_id'], 'integer'],
            [['comp_name', 'user_login'], 'string', 'max' => 128],
            [['users_id'], 'string', 'max' => 16],
            [['users_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::className(), 'targetAttribute' => ['users_id' => 'id']],
            [['comps_id'], 'exist', 'skipOnError' => true, 'targetClass' => Comps::className(), 'targetAttribute' => ['comps_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'time' => 'Время входа',
	        'comp_name' => 'Имя ОС',
	        'comp' => 'Компьютер',
            'comps_id' => 'Компьютер',
            'user_login' => 'Логин',
	        'users_id' => 'Пользователь',
	        'user' => 'Пользователь',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::className(), ['id' => 'users_id']);
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getComp()
	{
		return $this->hasOne(Comps::className(), ['id' => 'comps_id']);
	}

	/**
	 * Возвращает имя машины
	 */
	public function getCompName()
	{
		if (is_object($comp=$this->comp)) {
			return mb_strtolower($comp->name);
		} else {
			$tokens=explode('\\',$this->comp_name);
			return mb_strtolower($tokens[1]);
		}
	}

	/**
	 * Возвращает имя машины
	 */
	public function getUserDescr()
	{
		if (is_object($user=$this->user)) {
			return $user->Ename.' ('.$user->Login.')';
		} else {
			$tokens=explode('\\',$this->user_login);
			return mb_strtolower($tokens[1]).' (пользователь не найден в БД)';
		}
	}

	/**
	 * Возвращает имя логона
	 */
	public function getCompFqdn()
	{
		if (is_object($comp=$this->comp)) {
			return mb_strtolower($comp->fqdn);
		} else {
			$tokens=explode('\\',$this->comp_name);
			return mb_strtolower($tokens[1]);
		}
	}


	/**
	 * Возвращает имя логона
	 */
	public function getAge()
	{
		$age=time()-strtotime($this->time);
		if ($age<60) return $age;
		if ($age/60<60) return floor($age/60).'м';
		if ($age/3600<24) return floor($age/3600).'ч';
		if ($age/86400<50) return floor($age/86400).'д';
		return floor($age/86400/30).'мес';
	}


	public function beforeSave($insert)
	{
		//error_log("dataIncom: beforeSave");
		if (parent::beforeSave($insert)) {
			if (is_numeric($this->time))
				$this->time = date('Y-m-d H:i:s',$this->time);

			if (!isset($this->comps_id)) {
				$comp_tokens=explode('\\',$this->comp_name);
				if (count($comp_tokens)==2) {
					$domain_id = \app\models\Domains::findByFQDN($comp_tokens[0]);
					$this->comps_id = \app\models\Comps::findByDomainName($domain_id,$comp_tokens[1]);
				}
			}
			if (!isset($this->users_id)) {
				$user_tokens=explode('\\',$this->user_login);
				if (count($user_tokens)==2) {
					//$domain_id = \app\models\Domains::findByName($user_tokens[0]);
					$this->users_id = \app\models\Users::findByLogin($user_tokens[1]);
				}
			}
			return true;
		} else return false;
	}

	/**
	 * запрашивает последние уникальные входы пользователя на машины
	 */
	public static function fetchUniqComps($user_id,$limit=3) {
		$query= new \yii\db\Query();
		$recs=$query->select(['comps_id','users_id','max(id) as id'])
			//->distinct()
			->from(static::tableName())
			->where(['users_id'=>$user_id])
			->andWhere(['not',['comps_id'=>NULL]])
			->groupBy('comps_id')
			->orderBy(['id'=>SORT_DESC])
			->limit($limit)
			->all();

		if (!is_array($recs) || !count($recs)) return [];
		$items=[];
		foreach ($recs as $rec) $items[]=$rec['id'];
		$result=static::find()->where(['id'=>$items])->orderBy(['id'=>SORT_DESC])->all();
		if (!is_array($result)) $result=[];
		return $result;
	}

	/**
	 * запрашивает последние уникальные входы пользователей на машину
	 */
	public static function fetchUniqUsers($comp_id,$limit=3) {
		$query= new \yii\db\Query();
		$recs=$query->select(['comps_id','users_id','max(id) as id'])
			//->distinct()
			->from(static::tableName())
			->where(['comps_id'=>$comp_id])
			->andWhere(['not',['comps_id'=>NULL]])
			->groupBy('comps_id')
			->orderBy(['id'=>SORT_DESC])
			->limit($limit)
			->all();

		if (!is_array($recs) || !count($recs)) return [];
		$items=[];
		foreach ($recs as $rec) $items[]=$rec['id'];
		$result=static::find()->where(['id'=>$items])->orderBy(['id'=>SORT_DESC])->all();
		if (!is_array($result)) $result=[];
		return $result;
	}
}
