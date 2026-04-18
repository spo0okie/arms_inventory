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
	
	/**
	 * Returns acceptance test data map.
	 */
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
		return ['create','update','delete','item','item-by-name','ttip','validate','view'];
	}

    /**
     * Отображает главную страницу приложения.
     *
     * Требует права 'view'. GET: нет параметров.
     *
     * @return string HTML главной страницы
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

	/**
	 * Acceptance test data for Index.
	 *
	 * Проверяет отображение главной страницы без параметров.
	 * GET: нет. Ожидается HTTP 200.
	 */
	public function testIndex(): array
	{
		return [[]];
	}

	/**
	 * Отдаёт HTML-контент страницы Wiki по имени.
	 *
	 * Поддерживает DokuWiki (JSON-RPC) и Confluence (REST API).
	 * Результат парсится через WikiHelper::parseWikiHtml для корректного отображения ссылок.
	 *
	 * GET:
	 *   pageName (string) — имя страницы в Wiki.
	 *   api (string, опционально) — тип Wiki: 'doku' (по умолчанию) или 'confluence'.
	 *
	 * @param string $pageName Имя страницы Wiki
	 * @param string $api      Тип Wiki-API: WikiHelper::DOKUWIKI или 'confluence'
	 * @return string HTML-контент страницы или сообщение об ошибке
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
	 * Acceptance test data for Wiki.
	 *
	 * Тест зависит от доступности внешней Wiki-системы.
	 * В dev-окружении Wiki может быть недоступна, и action вернёт сообщение об ошибке —
	 * это допустимо, HTTP-статус 200 считается успехом (тело ответа не проверяется).
	 * GET: pageName='start'.
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
     * Отображает форму входа и обрабатывает авторизацию.
     *
     * GET:
     *   return (string, опционально) — URL для редиректа после успешного входа.
     * POST (поля LoginForm):
     *   username (string) — имя пользователя.
     *   password (string) — пароль.
     *   rememberMe (bool, опционально) — сохранить сессию.
     * При успешной авторизации редиректит на return или goBack().
     * При неверных данных возвращает форму со статусом 401.
     *
     * @return Response|string Форма входа или редирект после авторизации
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
	 * Acceptance test data for Login.
	 *
	 * Проверяет отображение формы входа без параметров (GET-запрос).
	 * Ожидается HTTP 200 с HTML формы.
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
	 * Завершает пользовательскую сессию и перенаправляет на главную страницу.
	 *
	 * Требует авторизованного пользователя (PERM_AUTHENTICATED).
	 * GET: нет параметров. Ответ: редирект на главную (HTTP 302).
	 *
	 * @return Response Редирект на главную страницу
	 */
	public function actionLogout()
	{
		Yii::$app->user->logout();
		return $this->goHome();
	}

	/**
	 * Acceptance test data for Logout.
	 *
	 * Проверяет завершение сессии и редирект на главную.
	 * GET: нет параметров. Ожидается HTTP 302.
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
	 * Отображает тестовую страницу стойки (rack).
	 *
	 * Рендерит view /places/rack без параметров. Используется для отладки
	 * компонента отображения стоечного оборудования.
	 * GET: нет параметров.
	 *
	 * @return string|Response HTML страницы тестовой стойки
	 */
	public function actionRackTest()
	{
		return $this->render('/places/rack');
	}

	/**
	 * Acceptance test data for RackTest.
	 *
	 * Тест пропущен: для стабильного рендера /places/rack требуются подготовленные
	 * fixtures с данными стоек (Rack/Place моделей) и предсказуемая конфигурация.
	 * При наличии rack-fixtures тест можно заменить на GET без параметров с ожидаемым 200.
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
	 * Отображает форму смены пароля и обрабатывает её отправку.
	 *
	 * Доступно администратору или самому пользователю (при включённом RBAC).
	 * GET: id (int) — идентификатор пользователя.
	 * POST (поля PasswordForm):
	 *   password (string) — новый пароль.
	 *   password_repeat (string) — подтверждение пароля.
	 * При успехе редиректит на страницу пользователя /users/view.
	 *
	 * @param int $id Идентификатор пользователя
	 * @return Response|string Форма смены пароля или редирект после успеха
	 * @throws ForbiddenHttpException если нет прав на смену пароля
	 * @throws NotFoundHttpException  если пользователь не найден
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
	 * Acceptance test data for PasswordSet.
	 *
	 * Тест пропущен: для проверки требуется admin-сессия и существующий user_id,
	 * безопасный для изменения пароля в тестовой среде. В acceptance-контексте
	 * эти условия не гарантированы без специальных fixtures.
	 */
	public function testPasswordSet(): array
	{
		return self::skipScenario('default', 'requires admin session and valid user context');
	}

	/**
	 * Отображает информационную страницу о приложении (версия, окружение, компоненты).
	 *
	 * Требует права 'admin'. GET: нет параметров.
	 *
	 * @return string HTML страницы app-info
	 */
	public function actionAppInfo()
	{
		return $this->render('app-info');
	}

	/**
	 * Acceptance test data for AppInfo.
	 *
	 * Проверяет отображение информационной страницы приложения.
	 * GET: нет параметров. Ожидается HTTP 200.
	 */
	public function testAppInfo(): array
	{
		return [[]];
	}

	/**
	 * Acceptance test data for Error.
	 *
	 * Тест пропущен: action 'error' является внешним (ArmsErrorAction) и корректно
	 * срабатывает только в контексте реально выброшенного исключения через
	 * обработчик ошибок Yii (errorHandler). Прямой GET-запрос не воспроизводит
	 * реальный сценарий ошибки.
	 */
	public function testError(): array
	{
		return self::skipScenario('default', 'error action depends on exception handler context');
	}

	/**
	 * Acceptance test data for ApiDoc.
	 *
	 * Проверяет отображение страницы Swagger UI (light/swagger SwaggerAction).
	 * GET: нет параметров. Ожидается HTTP 200.
	 */
	public function testApiDoc(): array
	{
		return [[]];
	}

	/**
	 * Acceptance test data for ApiJson.
	 *
	 * Что делает action `api-json`:
	 * - Выполняет runtime-сканирование OpenAPI-аннотаций в `@app/swagger`
	 *   и `@app/modules/api/controllers`.
	 * - Возвращает JSON-документ спецификации Swagger/OpenAPI.
	 *
	 * Что именно проверяем в acceptance:
	 * 1) Маршрут доступен авторизованному пользователю с правом `admin`.
	 * 2) GET-запрос без параметров отрабатывает без исключений на этапе сканирования.
	 * 3) Action возвращает успешный HTTP-код 200.
	 *
	 * Важно:
	 * - Здесь намеренно НЕ валидируем полный JSON-контракт OpenAPI (он объёмный и
	 *   может легитимно меняться при изменении аннотаций).
	 * - Тест валидирует именно доступность и корректное выполнение action как
	 *   источника спецификации для `/site/api-doc`.
	 */
	public function testApiJson(): array
	{
		return [[
			// Базовый сценарий: целевой action вызывается GET без параметров.
			// Ожидаем успешную генерацию swagger-спецификации (HTTP 200).
			'name' => 'default',
			'GET' => [],
			'response' => 200,
		]];
	}

	/**
	 * Acceptance test data for Item.
	 *
	 * SiteController — не AR-контроллер, action 'item' отключён через disabledActions().
	 * Skip задокументирован явно, чтобы генератор тестов не пытался автогенерировать данные.
	 */
	public function testItem(): array
	{
		return self::skipScenario('default', 'site controller is non-AR and has no item action');
	}

	/**
	 * Acceptance test data for Ttip.
	 *
	 * SiteController — не AR-контроллер, action 'ttip' отключён через disabledActions().
	 * Skip задокументирован явно, чтобы генератор тестов не пытался автогенерировать данные.
	 */
	public function testTtip(): array
	{
		return self::skipScenario('default', 'site controller is non-AR and has no ttip action');
	}

	/**
	 * Acceptance test data for Create.
	 *
	 * SiteController — не AR-контроллер, action 'create' отключён через disabledActions().
	 * Skip задокументирован явно, чтобы генератор тестов не пытался автогенерировать данные.
	 */
	public function testCreate(): array
	{
		return self::skipScenario('default', 'site controller is non-AR and has no create action');
	}

	/**
	 * Acceptance test data for Update.
	 *
	 * SiteController — не AR-контроллер, action 'update' отключён через disabledActions().
	 * Skip задокументирован явно, чтобы генератор тестов не пытался автогенерировать данные.
	 */
	public function testUpdate(): array
	{
		return self::skipScenario('default', 'site controller is non-AR and has no update action');
	}

	/**
	 * Acceptance test data for Delete.
	 *
	 * SiteController — не AR-контроллер, action 'delete' отключён через disabledActions().
	 * Skip задокументирован явно, чтобы генератор тестов не пытался автогенерировать данные.
	 */
	public function testDelete(): array
	{
		return self::skipScenario('default', 'site controller is non-AR and has no delete action');
	}
}
