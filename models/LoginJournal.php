<?php

namespace app\models;

use app\helpers\QueryHelper;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * This is the model class for table "login_journal".
 *
 * @property int $id id
 * @property string $time Дата и время
 * @property string $calc_time Дата и время (скорректированное, если обнаружены сбитые часы у клиента)
 * @property string $created_at время внесения записи
 * @property string $age Возраст события
 * @property string $comp_name Компьютер
 * @property int $comps_id ID Компьютера
 * @property string $user_login Пользователь
 * @property string $users_id ID Пользователя
 * @property string $compName имя компа
 * @property string $userDescr имя компа
 * @property string $compFqdn FQDN компа
 * @property int $local_time Время компьютера на момент обновления
 * @property int $type Тип входа
 *
 * @property Users $user
 * @property Comps $comp
 */
class LoginJournal extends ArmsModel
{

	public static $title='Входы в ПК';
	//public $local_time;
	
	/**
	 * Максимальный сдвиг во времени, который все еще квалифицируется как та-же запись
	 * сдвиг во времени может формироваться из-за коррекции времени события за счет сравнения
	 * timestamp отправки сообщения (фиксируется на клиенте) и получения (фиксируется на сервере)
	 * За счет этого нивелируется ошибка заложенная в клиентских отметках времени при сбитых часах,
	 * но накладывается ошибка времени доставки. Поэтому необходим небольшой "люфт"
	 */
	public static $maxTimeShift=5;

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
			[['time','local_time','created_at'],'safe'],
            [['comp_name', 'user_login'], 'required'],
            [['users_id','comps_id','type','local_time'], 'integer'],
            [['comp_name', 'user_login'], 'string', 'max' => 128],
            [['users_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['users_id' => 'id']],
            [['comps_id'], 'exist', 'skipOnError' => true, 'targetClass' => Comps::class, 'targetAttribute' => ['comps_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return [
            'id' => 'ID',
			'calc_time' => [
				'Время входа (корр.)',
				'indexHint'=>'Скорректированное время входа: если в момент получения записи <br>'
				.'было обнаружено значительное расхождение часов клиента с часами сервера, <br>'
				.'то время было скорректировано на сдвиг времени между клиентом и сервером <br>'
				.QueryHelper::$dateSearchHint
			],
	        'comp_name' => 'Имя ОС',
	        'comp' => 'Компьютер',
            'comps_id' => 'Компьютер',
			'created_at'=> [
				'Время регистрации',
				'indexHint'=>'Отметка времени, когда событие было зарегистрировано на сервере.<br>'
				.'Вместе со <b>временем отправки</b> дает понимание о расхождении часов сервера и клиента'
			],
			'local_time' => [
				'Время отправки',
				'indexHint'=>'Отметка времени в часах клиента, когда событие было отправлено на сервер.<br>'
					.'Вместе со <b>временем регистрации</b> дает понимание о расхождении часов сервера и клиента'
			],
			'time' => [
				'Время входа (ориг.)',
				'indexHint'=>'Исходное время входа: если в момент получения записи <br>'
					.'было обнаружено значительное расхождение часов клиента с часами сервера, <br>'
					.'то в этом поле сохраняется оригинальное значение в часах отправителя<br>'
					.QueryHelper::$dateSearchHint
			],
			'type' => 'Тип входа',
            'user_login' => 'Логин',
	        'users_id' => 'Пользователь',
			'user' => 'Пользователь',
        ];
    }

    /**
     * @return ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(Users::class, ['id' => 'users_id']);
    }

	/**
	 * @return ActiveQuery
	 */
	public function getComp()
	{
		return $this->hasOne(Comps::class, ['id' => 'comps_id']);
	}

	/**
	 * Возвращает имя машины
	 */
	public function getCompName()
	{
		if (is_object($comp=$this->comp)) {
			return mb_strtolower($comp->name);
		} else {
			$tokens=Domains::fetchFromCompName($this->comp_name);
			if ($tokens===false) return 'Incorrect hostname';
			return mb_strtolower($tokens[0]);
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
	
	public function getName()
	{
		return $this->userDescr.'->'.$this->compName;
	}


	/**
	 * Возвращает имя логона
	 */
	public function getAge()
	{
		$age=time()-strtotime($this->time);
		if ($age<60) return $age.'сек';
		if ($age/60<60) return floor($age/60).'мин';
		if ($age/3600<24) return floor($age/3600).'ч';
		if ($age/86400<50) return floor($age/86400).'д';
		return floor($age/86400/30).'мес';
	}


	public function beforeSave($insert)
	{
		if (!parent::beforeSave($insert)) {
			return false;
		}
		
		if ($insert && empty($this->created_at)) {
			$this->created_at = gmdate('Y-m-d H:i:s');
		}
		
		// Корректировка calc_time (кроме silentSave)
		if (!$this->doNotChangeAuthor && is_numeric($this->time)) {
			if ($this->local_time && abs(time() - $this->local_time) > 90) {
				// У клиента сильно сбито время: добавляем смещение времени на разницу между сервером и клиентом
				$this->calc_time = gmdate('Y-m-d H:i:s',$this->time + (time()-$this->local_time));
			} else  {
				$this->calc_time = gmdate('Y-m-d H:i:s', $this->time);
			}

			if (strtotime($this->calc_time) > time() + static::$maxTimeShift) {
				$this->addError('calc_time', 'Unable to add logon event in future');
				return false;
			}
			
			// В любом случае преобразуем time → datetime
			$this->time = gmdate('Y-m-d H:i:s', $this->time);
		}
		
		if (!isset($this->comps_id)) {
			echo "searching comp\n";
			if (is_object($comp= Comps::findByAnyName($this->comp_name))) {
				/** @var Comps $comp */
				$this->comps_id = $comp->id;
			}
		}
		
		if (!isset($this->users_id)) {
			$user_tokens=explode('\\',$this->user_login);
			if (count($user_tokens)==2) {
				//$domain_id = \app\models\Domains::findByName($user_tokens[0]);
				$user = Users::findByLogin($user_tokens[1]);
				if (is_object($user))
					/** @var Users $user */
					$this->users_id = $user->id;
			}
		}
		return true;
	}
	
	/**
	 * Запрашивает последние уникальные входы пользователя на машины
	 * @param int $user_id
	 * @param int $limit
	 * @return array|ActiveRecord[]
	 */
	public static function fetchUniqComps(int $user_id, int $limit=3) {
		$query= new Query();
		$recs=$query->select(['comps_id','users_id','max(id) as id'])
			//->distinct()
			->from(static::tableName())
			->where(['users_id'=>$user_id])
			->andWhere(['not',['comps_id'=>NULL]])
			->groupBy('comps_id')
			->orderBy(['MAX(time)'=>SORT_DESC])
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
	 * Запрашивает последние уникальные входы пользователей на машину
	 * @param int $comp_id
	 * @param int $limit
	 * @return array|ActiveRecord[]
	 */
	public static function fetchUniqUsers(int $comp_id,int $limit=3) {
		$query= new Query();
		$recs=$query->select(['comps_id','users_id','max(id) as id'])
			//->distinct()
			->from(static::tableName())
			->where(['comps_id'=>$comp_id])
			->andWhere(['not',['comps_id'=>NULL]])
			->groupBy('comps_id')
			->orderBy(['time'=>SORT_DESC])
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
