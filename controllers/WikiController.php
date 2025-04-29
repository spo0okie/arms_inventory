<?php

namespace app\controllers;

use app\helpers\WikiHelper;
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
				'render-field'
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
		
		return WikiHelper::parseWikiHtml($page, WikiHelper::wikiUrl($api));
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
		
		$page = WikiHelper::dokuwikiRender($text);
		if ($page===false) return "Error rendering via dokuwiki";
		
		return WikiHelper::parseWikiHtml($page, Yii::$app->params['wikiUrl']);
	}
}
