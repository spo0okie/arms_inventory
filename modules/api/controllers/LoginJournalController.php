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
	
	public function accessMap()
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
	 * Ищет запись в бд по компу, логину и времени.
	 * Это не для пользовательских запросов, т.к. время надо передать с точностью для секунды
	 * Это для скриптов, чтобы можно было понять есть уже нужная запись в журнале или нет.
	 * @param string|null $user_login
	 * @param string|null $comp_name
	 * @param string|null $time
	 * @param int    $type
	 * @param int|null   $local_time
	 * @return LoginJournal|null|ActiveRecord
	 */
    public function actionSearch(string $user_login=null, string $comp_name=null, string $time=null, int $type=0, $local_time=null){
    	//если вместе с отметкой времени входа в ПК передана текущая отметка времени
		// - корректируем ее на сдвиг текущего времени ПК относительно текущего времени сервера
		//(случай сбитых часов на ПК)
    	if ($local_time) $time+=(time()-$local_time);
	    return \app\models\LoginJournal::find()
		    ->andFilterWhere(['LOWER(comp_name)' => mb_strtolower($comp_name)])
		    ->andFilterWhere(['LOWER(user_login)' => mb_strtolower($user_login)])
			->andFilterWhere(['>','time',gmdate('Y-m-d H:i:s',$time-LoginJournal::$maxTimeShift)])
			->andFilterWhere(['<','time',gmdate('Y-m-d H:i:s',$time+LoginJournal::$maxTimeShift)])
			->andFilterWhere(['type' => $type])
			->orderBy(['id'=>SORT_DESC])
			->one();
    }
    
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
			$loader->local_time
		);
		if (is_object($exist)) throw new ConflictHttpException("Record already exist {$exist->id}");
	

		return $this->runAction('create');
	}
}
