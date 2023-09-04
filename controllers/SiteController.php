<?php

namespace app\controllers;

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

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
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
	 * @return string
	 */
	public function actionWiki($pageName,$api='doku')
	{
		$wikiUrl='';
		if ($api=='doku') {
			$wikiUrl=\Yii::$app->params['wikiUrl'];
			$arrContextOptions = [
				"http" => [
					"header" => "Authorization: Basic " . base64_encode(\Yii::$app->params['wikiUser'] . ":" . \Yii::$app->params['wikiPass']),
					'method' => 'POST',
					'content' => xmlrpc_encode_request(
						'wiki.getPageHTML',
						urldecode($pageName),
						['encoding'=>'utf-8','escaping'=>[]]
					),
				],"ssl" => ["verify_peer" => false,"verify_peer_name" => false,],
			];
			$page = @file_get_contents($wikiUrl.'lib/exe/xmlrpc.php',
				false,
				stream_context_create($arrContextOptions)
			);
			if ($page===false) return "Ошибка получения детального описания из Wiki";
			$page=xmlrpc_decode($page);
		}
		
		if ($api=='confluence') {
			$wikiUrl=\Yii::$app->params['confluenceUrl'];
			$arrContextOptions = [
				"http" => [
					"header" => "Authorization: Basic " . base64_encode(\Yii::$app->params['confluenceUser'] . ":" . \Yii::$app->params['confluencePass']),
				],"ssl" => ["verify_peer" => false,	"verify_peer_name" => false,],
			];
			$page = @file_get_contents($wikiUrl.'/rest/api/content/'.$pageName.'?expand=body.storage',
				false,
				stream_context_create($arrContextOptions)
			);
			if ($page===false) return "Ошибка получения детального описания из Wiki";
			
			$page=json_decode($page);
			if (
				!is_object($page)
				||
				!property_exists($page,'body')
				||
				!property_exists($page->body,'storage')
				||
				!property_exists($page->body->storage,'value')
			) return "Ошибка расшифровки JSON детального описания из Wiki";
			$page=$page->body->storage->value;
		}
		
		if (is_array($page)) return print_r($page,true);
		
		$page = str_replace('href="/', 'href="' . $wikiUrl , $page);
		$page = str_replace('href=\'/','href=\'' . $wikiUrl , $page);
		$page = str_replace('src="/',  'src="' . $wikiUrl , $page);
		$page = str_replace('src=\'/', 'src=\'' . $wikiUrl , $page);
		return $page;
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
            return $this->goBack();
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
	protected function findUser($id)
	{
		if (($model = Users::findOne($id)) !== null) {
			return $model;
		}
		
		throw new NotFoundHttpException('The requested page does not exist.');
	}
	
	/**
	 * Login action.
	 *
	 * @return Response|string
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
}
