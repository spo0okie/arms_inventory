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
    public function actionSearch(string $user_login=null, string $comp_name=null, string $time=null, int $type=0, $local_time=null):ActiveRecord|null
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
     * @return mixed
     * @throws BadRequestHttpException если тело запроса не удалось загрузить в модель
     * @throws ConflictHttpException   если запись с такими данными уже существует
     */
    public function actionPush() {
		/** @var LoginJournal $loader */
		$loader = new $this->modelClass();
	
		//грузим переданные данные
		if (!$loader->load(Yii::$app->getRequest()->getBodyParams(),'')) {
			throw new BadRequestHttpException("Error loading posted data");
		}
		
		$exist=$this->actionSearch(
			$loader->user_login,
			$loader->comp_name,
			$loader->time,
			$loader->type,
		);
		if (is_object($exist)) throw new ConflictHttpException("Record already exist {$exist->id}");
	

		return $this->runAction('create');
	}
}
