<?php

namespace app\modules\api\controllers;



use app\models\LoginJournal;
use Yii;
use yii\db\ActiveRecord;
use yii\web\BadRequestHttpException;
use yii\web\ConflictHttpException;

class LoginJournalController extends BaseRestController
{
    
    public $modelClass='app\models\LoginJournal';
	
	public function accessMap(): array
	{
		return array_merge_recursive(parent::accessMap(),[
			'update-login-journal'=>['push']
		]);
	}
	
	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		$behaviors=parent::behaviors();
		$behaviors['verbFilter']['actions']['push']=['POST'];
		return $behaviors;
	}
	
	/**
	 * Ищет запись журнала входов по компьютеру, логину и времени события.
	 * Предназначен для использования скриптами сбора данных, а не для UI:
	 * требует точного указания времени (до секунды) — допустимый сдвиг задан LoginJournal::$maxTimeShift.
	 * Если передан `local_time` (текущее время клиента) — корректирует `time` на разницу
	 * с серверным временем (компенсация рассинхронизации часов).
	 *
	 * GET-параметры:
	 * @param string|null $user_login  Логин пользователя (нечувствителен к регистру)
	 * @param string|null $comp_name   Имя компьютера (нечувствительно к регистру)
	 * @param string|null $time        Unix-timestamp события входа
	 * @param int         $type        Тип события (0 — вход, прочие — по конфигурации LoginJournal)
	 * @param int|null    $local_time  Текущий Unix-timestamp на клиентской машине (для коррекции часов)
	 *
	 * @return LoginJournal|ActiveRecord|null
	 */
    public function actionSearch(?string $user_login=null, ?string $comp_name=null, ?string $time=null, int $type=0, $local_time=null):ActiveRecord|null
	{
    	//если вместе с отметкой времени входа в ПК передана текущая отметка времени
		// - корректируем ее на сдвиг текущего времени ПК относительно текущего времени сервера
		//(случай сбитых часов на ПК)
    	if ($local_time) $time+=(time()-$local_time);
	    return LoginJournal::find()
		    ->andFilterWhere(['LOWER(comp_name)' => mb_strtolower($comp_name)])
		    ->andFilterWhere(['LOWER(user_login)' => mb_strtolower($user_login)])
			->andFilterWhere(['>','time',gmdate('Y-m-d H:i:s',$time-LoginJournal::$maxTimeShift)])
			->andFilterWhere(['<','time',gmdate('Y-m-d H:i:s',$time+LoginJournal::$maxTimeShift)])
			->andFilterWhere(['type' => $type])
			->orderBy(['id'=>SORT_DESC])
			->one();
    }
    
    /**
     * Создаёт запись в журнале входов (LoginJournal), если аналогичная запись ещё не существует.
     * Проверяет наличие дубликата через actionSearch() по user_login, comp_name, time и type.
     * Если дубликат найден — возвращает 409 Conflict с ID существующей записи.
     * Иначе делегирует создание в actionCreate().
     *
     * POST body: поля модели LoginJournal в формате JSON (user_login, comp_name, time, type)
     *
     * Если в теле передан `session_uid` (служба инвентаризации Windows) — это не событие входа,
     * а состояние сессии: запись создаётся или обновляется по этому ключу (см. pushSession()),
     * повторная доставка безвредна, 409 не возвращается. Дополнительные поля:
     * end_time (unix, часы клиента), end_type (LoginJournal::END_*), flags (LoginJournal::FLAG_*).
     *
     * @return mixed
     * @throws BadRequestHttpException если тело запроса не удалось загрузить в модель
     * @throws ConflictHttpException   если запись с такими данными уже существует
     */
    public function actionPush() {
		/** @var LoginJournal $loader */
		$loader = new $this->modelClass();
		$body = Yii::$app->getRequest()->getBodyParams();

		//грузим переданные данные
		if (!$loader->load($body,'')) {
			throw new BadRequestHttpException("Error loading posted data");
		}

		if (!empty($loader->session_uid)) return $this->pushSession($loader->session_uid, $body);

		$exist=$this->actionSearch(
			$loader->user_login,
			$loader->comp_name,
			$loader->time,
			$loader->type,
		);
		if (is_object($exist)) throw new ConflictHttpException("Record already exist {$exist->id}");
	

		return $this->runAction('create');
	}

	/**
	 * Upsert сессии по ключу. Поиск дубликата по окну времени здесь не нужен: ключ точный.
	 * Правила обновления существующей записи — в {@see LoginJournal::applySessionPush()}.
	 *
	 * @param string $uid  ключ сессии
	 * @param array  $body тело запроса
	 * @return mixed запись сессии (при ошибке валидации — с ошибками, сериализатор отдаст 422)
	 */
	protected function pushSession(string $uid, array $body)
	{
		$exist = LoginJournal::findBySessionUid($uid);

		if (!is_object($exist)) {
			$created = $this->runAction('create');
			//гонка двух доставок одной сессии: вторая упирается в уникальность ключа —
			//тогда это уже обновление существующей записи
			if (!($created instanceof LoginJournal) || !$created->hasErrors('session_uid')) return $created;
			$exist = LoginJournal::findBySessionUid($uid);
			if (!is_object($exist)) return $created;
			//create выставил 422 — дальше отвечаем по результату обновления
			Yii::$app->getResponse()->setStatusCode(200);
		}

		if ($exist->applySessionPush($body)) $exist->save();
		return $exist;
	}

	/**
	 * Сценарии push: состояние сессии по ключу (служба инвентаризации Windows)
	 * и прежнее поведение для записей без ключа (старые скрипты).
	 * Сценарии идут по порядку и опираются друг на друга: одна и та же сессия
	 * открывается, присылается повторно, закрывается и снова присылается закрытой.
	 */
	public function testPush(): array
	{
		$now = time();
		$uid = sprintf('%08x-7e57-4000-8000-%012x', $now, mt_rand());
		$uid2 = sprintf('%08x-7e58-4000-8000-%012x', $now, mt_rand());
		$open = [
			'session_uid' => $uid,
			'comp_name' => 'rest-session-test.domain.local',
			'user_login' => 'DOMAIN\\rest_session_tester',
			'type' => 1,
			'time' => $now - 600,
			'local_time' => $now,
		];
		$legacy = [
			'comp_name' => 'rest-legacy-test.domain.local',
			'user_login' => 'DOMAIN\\rest_legacy_tester',
			'type' => 0,
			'time' => $now - 900,
			'local_time' => $now,
		];
		$record = static function (string $key) : LoginJournal {
			$rec = LoginJournal::findBySessionUid($key);
			\PHPUnit\Framework\Assert::assertNotNull($rec, 'запись сессии не найдена');
			return $rec;
		};
		$start = null;

		return [
			[
				'name' => 'session open',
				'method' => 'POST', 'route' => '{controller}/push', 'body' => $open,
				'response' => [200, 201],
				'assert' => static function () use ($record, $uid, &$start) {
					$rec = $record($uid);
					\PHPUnit\Framework\Assert::assertTrue($rec->isOpen);
					$start = $rec->calc_time;
				},
			],
			[
				'name' => 'session open repeated keeps start',
				'method' => 'POST', 'route' => '{controller}/push',
				'body' => array_merge($open, ['time' => $now - 500]),
				'response' => 200,
				'assert' => static function () use ($record, $uid, &$start) {
					\PHPUnit\Framework\Assert::assertSame($start, $record($uid)->calc_time, 'начало пишется один раз');
					\PHPUnit\Framework\Assert::assertSame(1, (int)LoginJournal::find()->where(['session_uid' => $uid])->count());
				},
			],
			[
				'name' => 'session close',
				'method' => 'POST', 'route' => '{controller}/push',
				'body' => array_merge($open, [
					'end_time' => $now - 30,
					'end_type' => LoginJournal::END_LOGOFF,
					'flags' => LoginJournal::FLAG_END_ESTIMATED,
				]),
				'response' => 200,
				'assert' => static function () use ($record, $uid, &$start) {
					$rec = $record($uid);
					\PHPUnit\Framework\Assert::assertFalse($rec->isOpen);
					\PHPUnit\Framework\Assert::assertSame(LoginJournal::END_LOGOFF, (int)$rec->end_type);
					\PHPUnit\Framework\Assert::assertSame(LoginJournal::FLAG_END_ESTIMATED, (int)$rec->flags);
					\PHPUnit\Framework\Assert::assertSame($start, $rec->calc_time);
				},
			],
			[
				'name' => 'session close repeated changes nothing',
				'method' => 'POST', 'route' => '{controller}/push',
				'body' => array_merge($open, ['end_time' => $now - 5, 'end_type' => LoginJournal::END_SHUTDOWN]),
				'response' => 200,
				'assert' => static function () use ($record, $uid) {
					\PHPUnit\Framework\Assert::assertSame(LoginJournal::END_LOGOFF, (int)$record($uid)->end_type);
				},
			],
			[
				'name' => 'session closed at once creates full record',
				'method' => 'POST', 'route' => '{controller}/push',
				'body' => array_merge($open, [
					'session_uid' => $uid2,
					'end_time' => $now - 60,
					'end_type' => LoginJournal::END_SYSTEM_STOP,
				]),
				'response' => [200, 201],
				'assert' => static function () use ($record, $uid2) {
					$rec = $record($uid2);
					\PHPUnit\Framework\Assert::assertFalse($rec->isOpen);
					\PHPUnit\Framework\Assert::assertNotEmpty($rec->calc_time);
				},
			],
			[
				'name' => 'legacy push creates record',
				'method' => 'POST', 'route' => '{controller}/push', 'body' => $legacy,
				'response' => [200, 201],
			],
			[
				'name' => 'legacy push duplicate is conflict',
				'method' => 'POST', 'route' => '{controller}/push', 'body' => $legacy,
				'response' => 409,
			],
		];
	}
}
