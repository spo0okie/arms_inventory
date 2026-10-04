<?php

namespace app\models;

use app\helpers\QueryHelper;
use app\models\base\ArmsModel;
use app\types\DatetimeType;
use DateTime;
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
 * @property string $session_uid Ключ сессии (GUID службы инвентаризации); пусто у записей старых скриптов
 * @property string $end_time Время окончания сессии (скорректированное)
 * @property int $end_type Тип окончания сессии (END_*)
 * @property int $flags Флаги достоверности времени (битовая маска FLAG_*)
 * @property string $closed_by Кто закрыл сессию вручную
 * @property bool $isOpen Сессия открыта (есть ключ сессии и нет окончания)
 * @property bool $isStale Сессия открыта, но ОС давно не выходит на связь: состояние сессии неизвестно
 * @property string $endTypeName Тип окончания текстом
 * @property string[] $flagsDescr Отметки о неточном времени текстом
 *
 * @property Users $user
 * @property Comps $comp
 */
class LoginJournal extends ArmsModel
{

	public static $title='Входы в ПК';
	public static $titles='Журнал входов в ПК';

	public static function modelDescription(): string
	{
		return 'Журнал входов в ПК: события входа пользователей в операционные системы, '
			.'присылаемые скриптами инвентаризации, с учётом расхождения часов клиента и сервера.';
	}
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
	 * Расхождение часов клиента и сервера (сек), начиная с которого отметки клиента
	 * сдвигаются на это расхождение. Меньшее расхождение не трогаем: в него входит
	 * время доставки запроса, от запроса к запросу оно плавает.
	 */
	public static $maxClockSkew=90;

	//типы окончания сессии: 1..99 сообщает служба, 100+ ставит сервер
	const END_LOGOFF=1;				//штатный выход
	const END_SHUTDOWN=2;			//штатное завершение работы ОС
	const END_SERVICE_STOPPED=3;	//сессия исчезла, пока служба была штатно остановлена
	const END_SERVICE_CRASH=4;		//сессия исчезла, пока служба лежала после сбоя
	const END_SYSTEM_STOP=5;		//ОС остановилась нештатно (питание, BSOD)
	const END_LOST=100;				//закрыта сервером: сессии нет в слепке хоста
	const END_MANUAL=101;			//закрыта вручную из интерфейса

	/** типы окончания, которые ставит сервер: это догадка, данные службы их перезаписывают */
	const SERVER_END_TYPES=[self::END_LOST,self::END_MANUAL];

	public static $endTypes=[
		self::END_LOGOFF=>'LOGOFF',
		self::END_SHUTDOWN=>'SHUTDOWN',
		self::END_SERVICE_STOPPED=>'SERVICE_STOPPED',
		self::END_SERVICE_CRASH=>'SERVICE_CRASH',
		self::END_SYSTEM_STOP=>'SYSTEM_STOP',
		self::END_LOST=>'LOST',
		self::END_MANUAL=>'MANUAL',
	];

	public static $types=[0=>'CON',1=>'RDP'];

	/**
	 * Сколько секунд ОС может молчать (не обновлять свою запись), прежде чем её открытые
	 * сессии считаются неизвестными. Служба шлёт heartbeat раз в 5 минут — три пропущенных подряд.
	 */
	public static $silentAfter=900;

	//флаги достоверности времени (биты поля flags)
	const FLAG_START_ESTIMATED=1;	//начало — оценка (служба не видела вход)
	const FLAG_END_ESTIMATED=2;		//окончание — оценка (последний heartbeat службы)
	const FLAG_TIME_UNCONFIRMED=4;	//часы клиента в момент событий не подтверждены

	public static $flagNames=[
		self::FLAG_START_ESTIMATED=>'начало — оценка',
		self::FLAG_END_ESTIMATED=>'окончание — оценка',
		self::FLAG_TIME_UNCONFIRMED=>'время не подтверждено',
	];

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
			[['time','local_time','created_at','end_time'],'safe'],
            [['comp_name', 'user_login'], 'required'],
            [['users_id','comps_id','type','local_time','end_type','flags'], 'integer'],
            [['comp_name', 'user_login', 'closed_by'], 'string', 'max' => 128],
			[['session_uid'], 'string', 'max' => 36],
			[['session_uid'], 'unique'],
            [['users_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['users_id' => 'id']],
            [['comps_id'], 'exist', 'skipOnError' => true, 'targetClass' => Comps::class, 'targetAttribute' => ['comps_id' => 'id']],
        ];
    }

	public function getLinksSchema()
	{
		return [
			'users_id' => [Users::class,'logons_ids',],
			'comps_id' => Comps::class,
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
				//searchHint типа (DatetimeType) сборщик тултипа добавит сам — руками не дописывать
				'indexHint'=>'Скорректированное время входа: если в момент получения записи <br>'
					.'было обнаружено значительное расхождение часов клиента с часами сервера, <br>'
					.'то время было скорректировано на сдвиг времени между клиентом и сервером',
				'type'=>'datetime',
				'typeClass' => \app\types\DatetimeType::class,
			],
			'comp_name' => ['Имя ОС','hint'=>'Имя компьютера, как оно пришло от клиента в событии входа'],
			'comp' => 'Компьютер',
			'comps_id' => ['Компьютер','hint'=>'Распознанная ОС в базе (если имя удалось сопоставить)'],
			'created_at'=> [
				'Время регистрации',
				'indexHint'=>'Отметка времени, когда событие было зарегистрировано на сервере.<br>'
					.'Вместе со <b>временем отправки</b> дает понимание о расхождении часов сервера и клиента',
				'type'=>'datetime',
			],
			'local_time' => [
				'Время отправки',
				'hint'=>'Отметка времени в часах клиента на момент отправки события',
				'indexHint'=>'Отметка времени в часах клиента, когда событие было отправлено на сервер.<br>'
					.'Вместе со <b>временем регистрации</b> дает понимание о расхождении часов сервера и клиента',
				'type'=>'datetime',
				'typeClass' => \app\types\IntegerType::class,
			],
			'time' => [
				'Время входа (ориг.)',
				//searchHint типа (DatetimeType) сборщик тултипа добавит сам — руками не дописывать
				'indexHint'=>'Исходное время входа: если в момент получения записи <br>'
					.'было обнаружено значительное расхождение часов клиента с часами сервера, <br>'
					.'то в этом поле сохраняется оригинальное значение в часах отправителя',
				'type'=>'datetime',
				'typeClass'=>DatetimeType::class,
			],
			'type' => [
				'Тип входа',
				'hint'=>'Как выполнен вход: CON — локальный вход с консоли (за самим компьютером), '
					.'RDP — подключение к удалённому рабочему столу',
				'searchHint' => 'Выберите нужный вариант из списка чтобы отфильтровать',
			],
			'session_uid' => [
				'Ключ сессии',
				'hint'=>'Идентификатор сессии, присвоенный службой инвентаризации Windows.<br>'
					.'У записей старых скриптов пуст: о них известен только факт входа',
				'type'=>'string',
			],
			'end_time' => [
				'Время выхода',
				'hint'=>'Когда сессия завершилась (в часах сервера). Пусто — сессия ещё открыта '
					.'либо запись пришла от старого скрипта, который окончание не сообщает',
				'type'=>'datetime',
				'typeClass'=>DatetimeType::class,
			],
			'end_type' => [
				'Тип выхода',
				'hint'=>'Как завершилась сессия:<br>'
					.'LOGOFF — пользователь вышел;<br>'
					.'SHUTDOWN — ОС завершила работу штатно;<br>'
					.'SERVICE_STOPPED / SERVICE_CRASH — сессия закончилась, пока служба инвентаризации '
					.'была остановлена / лежала после сбоя;<br>'
					.'SYSTEM_STOP — ОС остановилась нештатно (питание, сбой);<br>'
					.'LOST — закрыта сервером: ОС сообщила список сессий, и этой в нём нет;<br>'
					.'MANUAL — закрыта вручную',
				'searchHint' => 'Выберите нужный вариант из списка чтобы отфильтровать',
			],
			'flags' => [
				'Достоверность времени',
				'hint'=>'Отметки о том, что время в записи неточное:<br>'
					.'начало — оценка (служба не видела сам вход);<br>'
					.'окончание — оценка (взято время последней отметки службы);<br>'
					.'время не подтверждено (часы компьютера могли быть сбиты)',
				'typeClass' => \app\types\IntegerType::class,
			],
			'closed_by' => [
				'Закрыл вручную',
				'hint'=>'Логин сотрудника, закрывшего сессию вручную',
				'type'=>'string',
			],
            'user_login' => [
				'Логин',
				'hint'=>'Логин пользователя, выполнившего вход (как пришёл от клиента)',
				'type'=>'string',
			],
			'users_id' => ['Пользователь','hint'=>'Распознанный сотрудник (если логин удалось сопоставить)'],
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
		return static::ageText(time()-static::utcToTime($this->calc_time ?: $this->time));
	}

	/**
	 * Отметка времени из БД в unix-время. Время в БД — UTC (соединение открывается с
	 * time_zone='+00:00'), а strtotime() без указания зоны читал бы строку в часовом поясе
	 * приложения и ошибался на его смещение.
	 */
	public static function utcToTime($dbTime): int
	{
		return (int)strtotime($dbTime.' UTC');
	}

	/**
	 * Интервал в секундах — коротким текстом (сек/мин/ч/д/мес)
	 */
	public static function ageText(int $age): string
	{
		if ($age<0) $age=0;
		if ($age<60) return $age.'сек';
		if ($age/60<60) return floor($age/60).'мин';
		if ($age/3600<24) return floor($age/3600).'ч';
		if ($age/86400<50) return floor($age/86400).'д';
		return floor($age/86400/30).'мес';
	}

	/**
	 * Сессия числится открытой, но ОС давно не выходит на связь (выключена, вне сети,
	 * служба не работает): что с сессией на самом деле — неизвестно. Такую сессию
	 * можно закрыть вручную.
	 */
	public function getIsStale(): bool
	{
		if (!$this->isOpen) return false;
		$comp=$this->comp;
		//ОС не опознана — подтверждать открытость сессии некому
		if (!is_object($comp) || !strlen((string)$comp->updated_at)) return true;
		return $comp->secondsSinceUpdate > static::$silentAfter;
	}

	public function getEndTypeName(): string
	{
		if (empty($this->end_type)) return '';
		return static::$endTypes[$this->end_type] ?? (string)$this->end_type;
	}

	/**
	 * @return string[] выставленные флаги достоверности времени текстом
	 */
	public function getFlagsDescr(): array
	{
		$result=[];
		foreach (static::$flagNames as $bit=>$name) {
			if ((int)$this->flags & $bit) $result[]=$name;
		}
		return $result;
	}

	/**
	 * Закрывает сессию вручную. Только для сессий, о которых ОС больше не сообщает
	 * ({@see getIsStale()}): живую сессию служба закроет сама, а закрытую вручную — тут же
	 * открыла бы заново. Если ОС потом вернётся и сообщит реальное окончание, оно
	 * перезапишет ручное ({@see applySessionPush()}).
	 *
	 * @param string $login кто закрывает
	 * @return bool сессия закрыта
	 */
	public function closeManually(string $login): bool
	{
		if (!$this->isStale) return false;
		return (bool)$this->updateAttributes([
			'end_time'=>gmdate('Y-m-d H:i:s'),
			'end_type'=>static::END_MANUAL,
			'closed_by'=>$login,
		]);
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
			// У клиента сильно сбито время: добавляем смещение времени на разницу между сервером и клиентом
			$this->calc_time = gmdate('Y-m-d H:i:s', $this->correctClientTime((int)$this->time));

			if (strtotime($this->calc_time) > time() + static::$maxTimeShift) {
				$this->addError('calc_time', 'Unable to add logon event in future');
				return false;
			}

			// В любом случае преобразуем time → datetime
			$this->time = gmdate('Y-m-d H:i:s', $this->time);
		}

		// Окончание сессии приходит в часах клиента — та же корректировка, что у calc_time
		if (!$this->doNotChangeAuthor && is_numeric($this->end_time)) {
			$end = $this->correctClientTime((int)$this->end_time);
			if ($end > time() + static::$maxTimeShift) {
				$this->addError('end_time', 'Unable to end session in future');
				return false;
			}
			$this->end_time = gmdate('Y-m-d H:i:s', $end);
		}

		if (!isset($this->comps_id)) {
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
	 * Переводит отметку времени клиента (unix) в часы сервера: если часы клиента
	 * на момент отправки (local_time) расходятся с серверными больше допуска —
	 * сдвигает отметку на это расхождение.
	 */
	public function correctClientTime(int $time): int
	{
		if ($this->local_time && abs(time() - $this->local_time) > static::$maxClockSkew) {
			return $time + (time() - (int)$this->local_time);
		}
		return $time;
	}

	/**
	 * Сессия открыта: о ней сообщила служба (есть ключ) и окончания ещё нет.
	 * Записи старых скриптов (без ключа) открытыми не бывают: об их окончании ничего не известно.
	 */
	public function getIsOpen(): bool
	{
		return !empty($this->session_uid) && empty($this->end_time);
	}

	/**
	 * Запрос открытых сессий
	 * @return ActiveQuery
	 */
	public static function findOpen()
	{
		return static::find()
			->where(['not',['session_uid'=>null]])
			->andWhere(['end_time'=>null]);
	}

	/**
	 * @param string $uid
	 * @return static|null
	 */
	public static function findBySessionUid(string $uid)
	{
		return static::findOne(['session_uid'=>$uid]);
	}

	/**
	 * Применяет к уже существующей записи сессии её повторно присланное состояние.
	 * Правда о сессии — на хосте, запись — её реплика, но:
	 *  - начало пишется один раз (при создании): от запроса к запросу оно плавает
	 *    на погрешность доставки, поэтому time/calc_time здесь не трогаем;
	 *  - окончание, сообщённое службой, тоже пишется один раз;
	 *  - окончание, поставленное сервером (LOST/MANUAL), — догадка: данные службы
	 *    его перезаписывают (в т.ч. «сессия всё ещё открыта»).
	 * Модель не сохраняет.
	 *
	 * @param array $data тело запроса push (поля в формате API: end_time — unix)
	 * @return bool изменилась ли запись (нужно ли сохранять)
	 */
	public function applySessionPush(array $data): bool
	{
		$serverClosed = !empty($this->end_time) && in_array((int)$this->end_type, static::SERVER_END_TYPES);
		$incomingEnd = $data['end_time'] ?? null;

		if (empty($incomingEnd)) {
			//служба говорит, что сессия открыта
			if (!$serverClosed) return false;
			$this->end_time = null;
			$this->end_type = null;
			$this->closed_by = null;
			return true;
		}

		//служба сообщает окончание
		if (!empty($this->end_time) && !$serverClosed) return false;

		//для пересчета окончания в часы сервера нужны часы клиента на момент этой отправки
		if (isset($data['local_time'])) $this->local_time = $data['local_time'];
		$this->end_time = $incomingEnd;
		$this->end_type = $data['end_type'] ?? null;
		$this->closed_by = null;
		//флаг начала относится к уже записанному началу, остальные — к присланному окончанию
		$this->flags = ((int)$this->flags & static::FLAG_START_ESTIMATED)
			| ((int)($data['flags'] ?? 0) & ~static::FLAG_START_ESTIMATED);
		return true;
	}

	/**
	 * Закрывает открытые сессии ОС, которых нет в присланном ею полном списке открытых:
	 * служба о них не знает (потеряла локальное состояние, была переустановлена и т.п.),
	 * значит сами они уже не закроются.
	 *
	 * @param int $compId
	 * @param string[] $openUids ключи сессий, открытых на хосте
	 * @return int сколько сессий закрыто
	 */
	public static function closeLost(int $compId, array $openUids): int
	{
		$condition = ['and',
			['comps_id'=>$compId],
			['not',['session_uid'=>null]],
			['end_time'=>null],
		];
		if (count($openUids)) $condition[] = ['not in','session_uid',array_values($openUids)];

		return static::updateAll([
			'end_time'=>gmdate('Y-m-d H:i:s'),
			'end_type'=>static::END_LOST,
		],$condition);
	}

	/**
	 * Входы для карточки ОС: все открытые сессии на ней, а если их меньше $min —
	 * добавляются последние завершённые входы других пользователей (по одному на
	 * пользователя), пока не наберётся $min.
	 * Завершённые — это и закрытые сессии, и записи старых скриптов (без окончания).
	 * @param int $compId
	 * @param int $min
	 * @return static[]
	 */
	public static function fetchForComp(int $compId, int $min=3): array
	{
		return static::fetchOpenAndRecent('comps_id', $compId, 'users_id', $min);
	}

	/**
	 * Входы для карточки сотрудника: все его открытые сессии, а если их меньше $min —
	 * добавляются последние завершённые входы на другие ОС (по одному на ОС).
	 * @param int $userId
	 * @param int $min
	 * @return static[]
	 */
	public static function fetchForUser(int $userId, int $min=3): array
	{
		return static::fetchOpenAndRecent('users_id', $userId, 'comps_id', $min);
	}

	/**
	 * @param string $ownerAttr чьи входы показываем (comps_id | users_id)
	 * @param int    $ownerId
	 * @param string $otherAttr вторая сторона входа: по ней завершённые входы берутся без повторов
	 * @param int    $min
	 * @return static[]
	 */
	protected static function fetchOpenAndRecent(string $ownerAttr, int $ownerId, string $otherAttr, int $min): array
	{
		$open=static::findOpen()
			->andWhere([$ownerAttr=>$ownerId])
			->orderBy(['calc_time'=>SORT_DESC,'id'=>SORT_DESC])
			->all();
		if (count($open)>=$min) return $open;

		//с кем (где) сессия открыта сейчас — того в завершённых не повторяем
		$shown=[];
		foreach ($open as $rec) if ($rec->$otherAttr) $shown[]=$rec->$otherAttr;

		$ids=(new Query())
			->select(['id'=>'MAX(id)'])
			->from(static::tableName())
			->where([$ownerAttr=>$ownerId])
			->andWhere(['not',[$otherAttr=>null]])
			->andWhere(['or',['session_uid'=>null],['not',['end_time'=>null]]])
			->andFilterWhere(['not in',$otherAttr,$shown])
			->groupBy($otherAttr)
			->orderBy(['MAX(id)'=>SORT_DESC])
			->limit($min-count($open))
			->column();
		if (!count($ids)) return $open;

		return array_merge($open, static::find()->where(['id'=>$ids])->orderBy(['id'=>SORT_DESC])->all());
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
			//уникальность по пользователям: groupBy('comps_id') при where по comps_id
			//схлопывал выборку в одну строку вместо $limit последних пользователей
			->groupBy('users_id')
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
