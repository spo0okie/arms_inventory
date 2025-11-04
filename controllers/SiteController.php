<?php

namespace app\controllers;

use app\helpers\WikiHelper;
use app\models\ui\PasswordForm;
use app\models\Users;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\ui\LoginForm;

require_once Yii::getAlias('@app/swagger/swagger.php');

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
					[
						'actions' => ['api-doc','api-json'],
						'allow' => true,
						'roles' => 'admin',
					],
					[
						'actions' => ['app-info'],
						'allow' => true,
						'roles' => 'admin',
					],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
			'api-doc' => [
				'class' => 'light\swagger\SwaggerAction',
				'restUrl' => \yii\helpers\Url::to(['/site/api-json'], true),
			],
			'api-json' => [
				'class' => 'app\components\swagger\ArmsSwaggerApiAction',
				'scanDir' => [
					Yii::getAlias('@app/swagger'),
					Yii::getAlias('@app/modules/api/controllers'),
					//Yii::getAlias('@app/models'),
				],
				'scanOptions' => [
			
				],
			],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }
	
	/**
	 * Displays homepage.
	 *
	 * @param string $pageName
	 * @param string $api
	 * @return string
	 */
	public function actionWiki($pageName,$api=WikiHelper::DOKUWIKI)
	{
		$wikiUrl='';
		$page=[];
		if ($api=='doku')
			$page=WikiHelper::fetchXmlRpc('wiki.getPageHTML',urldecode($pageName));
		
		if ($api=='confluence')
			$page = WikiHelper::fetchConfluence($pageName);

		if ($page===false) return "Ошибка получения детального описания из Wiki";
		
		return WikiHelper::parseWikiHtml($page, WikiHelper::wikiUrl($api));
	}
	
	/**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
        	$return=Yii::$app->request->get('return');
			if ($return) {
				return $this->redirect($return);
			} else {
				return $this->goBack();
			}
        }
        return $this->render('login', [
            'model' => $model,
        ]);
    }
	
	/**
	 * Logout action.
	 *
	 * @return Response
	 */
	public function actionLogout()
	{
		Yii::$app->user->logout();
		
		return $this->goHome();
	}
	
	/**
	 * Test action.
	 *
	 * @return string|Response
	 */
	public function actionRackTest()
	{
		//return 'test';
		return $this->render('/places/rack');
	}
	
	/**
	 * Finds the Users model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 * @param integer $id
	 * @return Users the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findUser(int $id)
	{
		if (($model = Users::findOne($id)) !== null) {
			return $model;
		}
		
		throw new NotFoundHttpException('The requested page does not exist.');
	}
	
	/**
	 * Login action.
	 *
	 * @param $id
	 * @return Response|string
	 * @throws ForbiddenHttpException
	 * @throws NotFoundHttpException
	 */
	public function actionPasswordSet($id)
	{
		$user = $this->findUser($id);
		
		if (Yii::$app->params['useRBAC'] && !Yii::$app->user->identity->isAdmin() && !(Yii::$app->user->identity->id == $id)) {
			throw new ForbiddenHttpException('Access denied');
		}
		
		$model = new PasswordForm();
		$model->user_id=$user->id;
		
		if ($model->load(Yii::$app->request->post()) && $model->update()) {
			return $this->redirect(['/users/view','id'=>$id]);
		}
		
		return $this->render('password', [
			'model' => $model,
		]);
	}
	
	public function actionAppInfo()
	{
		phpinfo();
		
		echo "<h2>App params</h2>";
		echo "<pre>";
		var_dump(Yii::$app->params);
		echo "</pre>";
	}
}
