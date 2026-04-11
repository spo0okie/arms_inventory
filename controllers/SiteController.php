<?php

namespace app\controllers;

use app\helpers\WikiHelper;
use app\models\ui\LoginForm;
use app\models\ui\PasswordForm;
use app\models\Users;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

require_once Yii::getAlias('@app/swagger/swagger.php');

class SiteController extends ArmsBaseController
{
	
	public function getTestData(): array {return [];}
    /**
     * @inheritdoc
     */
    public function accessMap()
	{
		return [
			ArmsBaseController::PERM_AUTHENTICATED => ['logout'],
			ArmsBaseController::PERM_EVERYONE => ['login','error'],
			'view' => ['index','wiki','rack-test'],
			'admin' => ['api-doc','api-json','app-info','password-set'],
		];
	}

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'app\components\actions\ArmsErrorAction',
            ],
			'api-doc' => [
				'class' => 'light\swagger\SwaggerAction',
				'restUrl' => \yii\helpers\Url::to(['/site/api-json'], true),
			],
			'api-json' => [
				'class' => 'app\swagger\action\ArmsSwaggerApiAction',
				'scanDir' => [
					Yii::getAlias('@app/swagger'),
					Yii::getAlias('@app/modules/api/controllers'),
				],
				'scanOptions'=>[
					'exclude'=>[
						Yii::getAlias('@app/swagger/action'),
						Yii::getAlias('@app/swagger/pipeline'),
					]
				],
			],
        ];
    }
	
	public function disabledActions()
	{
		return ['create','update','delete','item','item-by-name','ttip','validate'];
	}

    /**
     * Показывает главную страницу приложения.
     *
     * Данные для вызова: GET без параметров.
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

	/**
	 * Тестовые данные для actionIndex.
	 *
	 * Нужен простой GET без параметров, ожидается 200.
	 */
	public function testIndex(): array
	{
		return [[]];
	}

	/**
	 * Отдаёт HTML-страницу из Wiki (DokuWiki/Confluence) по имени страницы.
	 *
	 * Для вызова нужен GET-параметр pageName. Дополнительно можно передать api.
	 *
	 * @param string $pageName
	 * @param string $api
	 * @return string
	 */
	public function actionWiki($pageName,$api=WikiHelper::DOKUWIKI)
	{
		$page=[];
		if ($api=='doku') {
			$page=WikiHelper::fetchJsonRpc('wiki.getPageHTML',['id'=>$pageName]);
		}

		if ($api=='confluence') {
			$page = WikiHelper::fetchConfluence($pageName);
		}

		if ($page===false) {
			return 'Ошибка получения детального описания из Wiki';
		}

		return WikiHelper::parseWikiHtml($page, WikiHelper::wikiUrl($api));
	}

	/**
	 * Тестовые данные для actionWiki.
	 *
	 * Нужен GET-параметр pageName (например, start), ожидается 200.
	 */
	public function testWiki(): array
	{
		return [[
			'name' => 'default',
			'GET' => ['pageName' => 'start'],
			'response' => 200,
		]];
	}

	/**
     * Страница входа и обработка авторизации.
     *
     * Для GET открывает форму.
     * Для POST ожидает поля LoginForm и пытается авторизовать пользователя.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post())) {
			if ($model->login()) {
				$return = Yii::$app->request->get('return');
				if ($return) {
					return $this->redirect($return);
				}
				return $this->goBack();
			}
			Yii::$app->response->statusCode = 401;
        }

        return $this->render('login', [
            'model' => $model,
        ]);
    }

	/**
	 * Тестовые данные для actionLogin.
	 *
	 * Нужен GET без параметров, ожидается 200.
	 */
	public function testLogin(): array
	{
		return [[
			'name' => 'default',
			'GET' => [],
			'response' => 200,
		]];
	}

	/**
	 * Завершает пользовательскую сессию и перенаправляет на главную.
	 *
	 * Для вызова достаточно GET без параметров; ожидается redirect (302).
	 *
	 * @return Response
	 */
	public function actionLogout()
	{
		Yii::$app->user->logout();
		return $this->goHome();
	}

	/**
	 * Тестовые данные для actionLogout.
	 *
	 * Нужен GET без параметров; ожидается 302.
	 */
	public function testLogout(): array
	{
		return [[
			'name' => 'default',
			'GET' => [],
			'response' => 302,
		]];
	}

	/**
	 * Рендерит страницу тестовой стойки.
	 *
	 * Для вызова нужен GET без параметров.
	 *
	 * @return string|Response
	 */
	public function actionRackTest()
	{
		return $this->render('/places/rack');
	}

	/**
	 * Тестовые данные для actionRackTest.
	 *
	 * Сейчас тест пропущен, так как для стабильной проверки нужны подготовленные rack/place fixtures
	 * и предсказуемая конфигурация шаблонов/данных стойки.
	 */
	public function testRackTest(): array
	{
		return self::skipScenario('default', 'requires rack fixtures and configuration');
	}

	/**
	 * Finds the Users model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 */
	protected function findUser(int $id)
	{
		if (($model = Users::findOne($id)) !== null) {
			return $model;
		}

		throw new NotFoundHttpException('The requested page does not exist.');
	}

	/**
	 * Форма установки пароля пользователя.
	 *
	 * Для GET нужен параметр id пользователя.
	 * Для POST требуются поля PasswordForm для выбранного user_id.
	 *
	 * @param int $id
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

	/**
	 * Тестовые данные для actionPasswordSet.
	 *
	 * Нужно передать существующий id пользователя и выполнить запрос от пользователя с правами.
	 * Сейчас тест пропущен, так как в текущем acceptance-контексте нет гарантии стабильной
	 * admin-сессии и фиксированного user-id, безопасного для изменения пароля.
	 */
	public function testPasswordSet(): array
	{
		return self::skipScenario('default', 'requires admin session and valid user context');
	}

	/**
	 * Рендерит страницу с информацией о приложении.
	 *
	 * Для вызова нужен GET без параметров.
	 */
	public function actionAppInfo()
	{
		return $this->render('app-info');
	}

	/**
	 * Тестовые данные для actionAppInfo.
	 *
	 * Нужен GET без параметров, ожидается 200.
	 */
	public function testAppInfo(): array
	{
		return [[]];
	}

	/**
	 * Тестовые данные для actionError (external action из actions()).
	 *
	 * Сейчас тест пропущен, потому что actionError корректно проверяется только в контексте
	 * реально выброшенного исключения и обработчика ошибок Yii.
	 */
	public function testError(): array
	{
		return self::skipScenario('default', 'error action depends on exception handler context');
	}

	/**
	 * Тестовые данные для actionApiDoc (external action из actions()).
	 *
	 * Нужен GET без параметров, ожидается 200.
	 */
	public function testApiDoc(): array
	{
		return [[]];
	}

	/**
	 * Тестовые данные для actionApiJson (external action из actions()).
	 *
	 * Сейчас тест пропущен: для стабильной проверки нужен предсказуемый runtime swagger scan
	 * (пути сканирования, окружение и итоговый набор аннотаций).
	 */
	public function testApiJson(): array
	{
		return self::skipScenario('default', 'requires swagger scan runtime configuration');
	}

	/**
	 * SiteController не является AR-контроллером и не реализует item/ttip/view/create/update/delete actions.
	 *
	 * Эти тест-методы определены явно, чтобы генератор тестов имел документированный skip
	 * вместо неявной попытки автогенерации test-data.
	 */
	public function testItem(): array
	{
		return self::skipScenario('default', 'site controller is non-AR and has no item action');
	}

	public function testTtip(): array
	{
		return self::skipScenario('default', 'site controller is non-AR and has no ttip action');
	}

	public function testView(): array
	{
		return self::skipScenario('default', 'site controller is non-AR and has no view action');
	}

	public function testCreate(): array
	{
		return self::skipScenario('default', 'site controller is non-AR and has no create action');
	}

	public function testUpdate(): array
	{
		return self::skipScenario('default', 'site controller is non-AR and has no update action');
	}

	public function testDelete(): array
	{
		return self::skipScenario('default', 'site controller is non-AR and has no delete action');
	}
}
