<?php

namespace app\controllers;

use app\helpers\WikiHelper;
use app\models\ui\LoginForm;
use app\models\ui\PasswordForm;
use app\models\Users;
use PHPUnit\Framework\Assert;
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
			'view' => ['index','wiki'],
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
		return ['create','update','delete','item','item-by-name','ttip','validate','view','rack-test'];
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
	 * Что делает тестируемый action `password-set`:
	 * - Загружает пользователя по `id` через `findUser()`.
	 * - Проверяет право смены пароля (админ или владелец учётной записи при включённом RBAC).
	 * - При GET рендерит форму смены пароля.
	 * - При валидном POST (`password` + `passwordRepeat`) обновляет пароль
	 *   через `PasswordForm::update()` и делает redirect на `/users/view`.
	 *
	 * Что именно проверяем:
	 * 1) Форма смены пароля открывается (HTTP 200).
	 * 2) POST с валидной парой паролей завершает action редиректом (HTTP 302).
	 * 3) После POST пароль пользователя действительно изменён в БД
	 *    (проверка `Users::validatePassword()` с новым значением).
	 *
	 * Техническая деталь:
	 * - Используем динамический user_id из дампа, чтобы не хардкодить конкретного пользователя.
	 */
	public function testPasswordSet(): array
	{
		$userId=(int)Users::find()->select('id')->orderBy(['id'=>SORT_ASC])->scalar();
		if (!$userId) {
			return self::skipScenario('default', 'no users available in acceptance db dump');
		}
		$newPassword='Test1234';
		
		return [[
			'name' => 'form open',
			'GET' => ['id' => $userId],
			'response' => 200,
		],[
			'name' => 'password update',
			'GET' => ['id' => $userId],
			'POST' => [
				'PasswordForm' => [
					'user_id' => $userId,
					'password' => $newPassword,
					'passwordRepeat' => $newPassword,
				],
			],
			'response' => 302,
			'assert' => static function () use ($userId, $newPassword) {
				$user=Users::findOne($userId);
				Assert::assertNotNull($user, 'user must exist after password-set');
				Assert::assertTrue(
					$user->validatePassword($newPassword),
					'password-set must persist new password hash in users table'
				);
			},
		]];
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

}
