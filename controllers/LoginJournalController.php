<?php

namespace app\controllers;

use app\models\LoginJournal;
use Yii;
use yii\web\ConflictHttpException;

/**
 * OrgPhonesController implements the CRUD actions for OrgPhones model.
 */
class LoginJournalController extends ArmsBaseController
{
	public function disabledActions()
	{
		//все CRUD операции делаются через REST API
		return ['item-by-name','item','create','update','delete','view','ttip'];
	}
	
	public $modelClass=LoginJournal::class;

	/**
	 * @inheritdoc
	 */
	public function accessMap()
	{
		return array_merge_recursive(parent::accessMap(),[
			self::PERM_EDIT=>['close'],
		]);
	}

	/**
	 * Закрывает сессию вручную — для сессий, которые числятся открытыми на ОС, давно не
	 * выходящей на связь (списанной, переустановленной, сломанной): сама она о конце
	 * сессии уже не сообщит. Запоминается, кто закрыл. Если ОС вернётся и сообщит
	 * реальное окончание, оно перезапишет ручное.
	 *
	 * Сессию ОС, которая на связи, закрыть нельзя: служба тут же открыла бы её заново.
	 *
	 * GET-параметры:
	 * @param int $id Идентификатор записи журнала входов
	 *
	 * @return \yii\web\Response возврат на страницу, с которой пришли
	 * @throws \yii\web\NotFoundHttpException если записи нет
	 * @throws ConflictHttpException если сессия не открыта или её ОС на связи
	 */
	public function actionClose(int $id)
	{
		/** @var LoginJournal $model */
		$model=$this->findModel($id);

		$identity=Yii::$app->user->identity ?? null;
		$login=is_object($identity) ? (string)$identity->Login : '';

		if (!$model->closeManually($login)) {
			throw new ConflictHttpException(
				'Сессию закрыть нельзя: она уже закрыта либо компьютер на связи и сообщает о ней сам'
			);
		}
		return $this->redirect(Yii::$app->request->referrer ?: ['index']);
	}

	/**
	 * Сценарии close: открытая сессия на ОС без связи закрывается, закрытая — конфликт.
	 */
	public function testClose(): array
	{
		$session=static function (array $attrs): LoginJournal {
			$rec=new LoginJournal();
			$rec->setAttributes(array_merge([
				'session_uid'=>sprintf('%08x-c105-4000-8000-%012x',time(),mt_rand()),
				//ОС с таким именем в базе нет: подтвердить открытость сессии некому
				'comp_name'=>'close-test-unknown-host',
				'user_login'=>'DOMAIN\\close_tester',
				'time'=>time()-300,
				'local_time'=>time(),
			],$attrs));
			$rec->save();
			return $rec;
		};
		$stale=$session([]);
		$closed=$session(['end_time'=>time()-60,'end_type'=>LoginJournal::END_LOGOFF]);

		return [
			[
				'name'=>'stale session',
				'GET'=>['id'=>$stale->id],
				'response'=>302,
			],
			[
				'name'=>'closed session',
				'GET'=>['id'=>$closed->id],
				'response'=>409,
			],
		];
	}
}
