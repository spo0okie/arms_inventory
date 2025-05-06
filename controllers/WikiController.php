<?php

namespace app\controllers;

use app\helpers\WikiHelper;
use app\models\ui\WikiCache;
use app\models\Users;
use Yii;
use yii\filters\auth\HttpBasicAuth;
use yii\web\Controller;
use yii\filters\VerbFilter;

class WikiController extends Controller
{
	/**
	 * Карта доступа с какими полномочиями, что можно делать
	 * @return array
	 */
	public function accessMap() {
		return [
			ArmsBaseController::PERM_VIEW=>[
				'page',
				'render-field',
				'invalidate-page',
			],
		];
	}


    /**
     * @inheritdoc
     */
	public function behaviors()
	{
		$behaviors=[
			/* Это была задумка на метод который отрендерит текст переданный в POST
			 * 'verbs' => [
				'class' => VerbFilter::class,
				'actions' => [
					'render' => ['POST'],
				],
			],*/
			'authenticator' => [
				'class' => HttpBasicAuth::class,
				'optional'=> ['*'],
				'auth' => function ($login, $password) {
					/** @var $user Users */
					$user = Users::find()->where(['Login' => $login])->one();
					if ($user && $user->validatePassword($password)) return $user;
					return null;
				},
			],
		];
		
		if (!empty(Yii::$app->params['useRBAC']))
			$behaviors['access']=ArmsBaseController::buildAccessRules($this->accessMap());
		
		return $behaviors;
	}


	/**
	 * Displays wiki page HTML.
	 *
	 * @param string $pageName
	 * @param string $api
	 * @return string
	 */
	public function actionPage($pageName,$api=WikiHelper::DOKUWIKI)
	{
		$page=[];
		if ($api=='doku')
			$page=WikiHelper::fetchXmlRpc('wiki.getPageHTML',urldecode($pageName));
		
		if ($api=='confluence')
			$page = WikiHelper::fetchConfluence($pageName);

		if ($page===false) return "Ошибка получения детального описания из Wiki";
		
		$parsed=WikiHelper::parseWikiHtml($page, WikiHelper::wikiUrl($api));
		
		$cache=WikiCache::fetchCache($pageName);
		$cache->data=$parsed;
		//$cache->dependencies ='|'.implode('|', WikiCache::extractDependencies($text)).'|';
		$cache->valid=0;	//по умолчанию кэш считаем не валидным.
		// т.е. он будет подгружен, но сразу запросит обновление данных из вики
		
		return $parsed;
	}
	

	/**
	 * Renders model field via dokuwiki
	 *
	 * @param string $pageName
	 * @param string $api
	 * @return string
	 */
	public function actionRenderField($class,$id,$field)
	{
		
		$model=ArmsBaseController::findClassModel($class,$id);
		$text=$model->$field;
		
		//отправляем данные на рендер
		$page = WikiHelper::dokuwikiRender($text);
		if ($page===false) return "Error rendering via dokuwiki";
		//правим ссылки
		$parsed=WikiHelper::parseWikiHtml($page, Yii::$app->params['wikiUrl']);
		
		$cache=WikiCache::fetchCache(
			WikiCache::internalPath($class, $id, $field)
		);
		
		$cache->data=$parsed;
		$cache->dependencies ='|'.implode('|', WikiCache::extractDependencies($text)).'|';
		$cache->valid=1;
		
		$cache->save();
		return $parsed;
	}
	
	/**
	 * Обозначает кэш обновленной страницы как устаревший
	 * @param string $pageName
	 * @return string
	 */
	public function actionInvalidatePage($pageName)
	{
		$count=0;

		//основная страничка
		$page=WikiCache::find()
			->where(['page'=>$pageName])
			->one();
		
		if ($page) {
			$page->valid=0;
			$page->save();
			$count++;
		}
		
		//странички ссылающиеся на нее (через {{page>...}})
		$dependants=WikiCache::find()
			->where(['like','dependencies','|'.$pageName.'|'])
			->all();

		foreach ($dependants as $dependant) {
			$dependant->valid=0;
			$dependant->save();
			$count++;
		}
		
		return $count.' pages invalidated';
	}
}
