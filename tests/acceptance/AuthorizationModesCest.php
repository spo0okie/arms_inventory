<?php

use app\models\Comps;
use app\models\Techs;
use app\models\Users;
use yii\rbac\DbManager;

/**
 * Acceptance тесты для проверки различных режимов авторизации
 * 
 * Согласно документации: https://wiki.reviakin.net/инвентаризация:настройка#авторизация
 * 
 * Проверяемые параметры:
 * - useRBAC: включает систему RBAC (Role-Based Access Control)
 * - localAuth: включает локальную авторизацию через БД
 * - authorizedView: запрещает доступ без авторизации
 * 
 * Тестируется доступ к CRUD операциям над моделью Comps в различных режимах
 */
class AuthorizationModesCest
{
    protected $testUser;
	protected $testPasswd;
    protected $testComp;
	protected $testTech;
	
	protected $testParams='/config/params-test.php';
 
	private function addRole($roleName)
	{
		// получаем роль
		$role = Yii::$app->authManager->getRole($roleName); // или 'user', 'manager', etc
		
		if ($role === null) {
			throw new \RuntimeException("Роль $roleName не существует");
		}
		
		// назначаем роль пользователю
		$assignedRoles = Yii::$app->authManager->getRolesByUser($this->testUser->id);
		
		if (!isset($assignedRoles[$roleName])) {
			Yii::$app->authManager->assign($role, $this->testUser->id);
			Yii::$app->authManager->invalidateCache();
		}
	}
	
	private function revokeRoles()
	{
		Yii::$app->authManager->revokeAll($this->testUser->id);
		Yii::$app->authManager->invalidateCache();
		
	}
    public function _before(AcceptanceTester $I)
    {
        // Создаем тестового пользователя
        $this->testUser = Users::findByLogin('test_user');
		if ($this->testUser === null) {
			$this->testUser = new Users([
				'Login' => 'test_user',
				'Ename' => 'Тестовый Пользователь',
				'Email' => 'test@example.com',
				'Persg' => 1,
				'Uvolen' => 0,
			]);
		}
		$this->testPasswd = 'test123';
        $this->testUser->setPassword($this->testPasswd);
        $this->testUser->save(false);
		$this->testUser->refresh();
		
		$I->wantTo("Использовать пользователя {$this->testUser->Login} ({$this->testUser->id})");
		
		/** @var DbManager $auth */
		$auth = Yii::$app->authManager;
		
		// 1. permission
		$permission = $auth->getPermission('edit-techs');
		if ($permission === null) {
			$permission = $auth->createPermission('edit-techs');
			$permission->description = 'Techs edit access';
			$auth->add($permission);
		}
		
		// 2. role
		$role = $auth->getRole('techs-editor');
		if ($role === null) {
			$role = $auth->createRole('techs-editor');
			$auth->add($role);
		}
		
		// 3. role → permission
		if (!$auth->hasChild($role, $permission)) {
			$auth->addChild($role, $permission);
		}
		
        // Получаем тестовый комп для проверки
        $this->testComp = Comps::find()->one();
		$this->testTech = Techs::find()->one();
    }
	
	private function overrideParams($params=[])
	{
		file_put_contents(Yii::getAlias('@app').$this->testParams, '<?php return '.var_export($params, true).';');
	}
    
    public function _after(AcceptanceTester $I)
    {
        // Восстанавливаем оригинальные параметры
        $this->overrideParams();
        
        // Удаляем тестового пользователя
        if ($this->testUser && !$this->testUser->isNewRecord) {
            //$this->testUser->delete();
        }
    }
    
	
	private function login(AcceptanceTester $I) {
		$I->amOnPage('/site/login');
		$I->submitForm('#login-form', [
			'LoginForm[username]' => $this->testUser->Login,
			'LoginForm[password]' => $this->testPasswd,
		]);
	}
	
	private function logout(AcceptanceTester $I) {
		$I->amOnPage('/site/logout');
		$I->resetCookie('PHPSESSID');
		$I->resetCookie('_csrf');
	}
		
		/**
     * Тест режима: без RBAC, без требования авторизации
     * Ожидается: все страницы доступны (200)
     */
    public function testModeNoRbacNoAuthRequired(AcceptanceTester $I)
    {
        $I->wantTo('проверить доступ без RBAC и без требования авторизации');
		
		$this->overrideParams([
			'useRBAC' => false,
        	'authorizedView' => false,
		]);
  
		$this->logout($I);
		$this->revokeRoles();
		
        // Проверяем доступ к страницам без авторизации
        $I->amOnPage('/comps/index');
        $I->seeResponseCodeIs(200);
        
        $I->amOnPage("/comps/view?id={$this->testComp->id}");
        $I->seeResponseCodeIs(200);
        
        $I->amOnPage('/comps/create');
        $I->seeResponseCodeIs(200);
        
        $I->amOnPage("/comps/update?id={$this->testComp->id}");
        $I->seeResponseCodeIs(200);
    }
    
    /**
     * Тест режима: RBAC включен, авторизация не требуется
     * Специальный режим - просмотр доступен всем, редактирование требует прав
     */
    public function testModeRbacEnabledNoAuthRequired(AcceptanceTester $I)
    {
        $I->wantTo('проверить режим RBAC без требования авторизации для ReadOnly доступа');
		
		$this->overrideParams([
			'useRBAC' => true,
			'authorizedView' => false,
		]);
        
        // Согласно логике в ArmsBaseController:236
        // !authorizedView && useRBAC дает права просмотра анонимным пользователям
		$this->logout($I);
		$this->revokeRoles();
		
		$I->amOnPage('/comps/index');
        $I->seeResponseCodeIs(200);
        
        $I->amOnPage("/comps/view?id={$this->testComp->id}");
        $I->seeResponseCodeIs(200);
        
        // Редактирование должно требовать авторизации (401)
        $I->amOnPage('/techs/create');
        $I->seeResponseCodeIs(401);
        
        $I->amOnPage("/techs/update?id={$this->testTech->id}");
        $I->seeResponseCodeIs(401);
		
		$this->login($I);
		
		$I->amOnPage('/techs/create');
		$I->seeResponseCodeIs(403);
		
		$I->amOnPage("/techs/update?id={$this->testTech->id}");
		$I->seeResponseCodeIs(403);

		$this->addRole('techs-editor');
		// Редактирование оборудования должно стать доступно
		$I->amOnPage('/techs/create');
		$I->seeResponseCodeIs(200);
		
		$I->amOnPage("/techs/update?id={$this->testTech->id}");
		$I->seeResponseCodeIs(200);
	}
    
    /**
     * Тест режима: RBAC включен, авторизация требуется
     * Строгий режим - все требует авторизации и прав
     */
    public function testModeRbacEnabledAuthRequired(AcceptanceTester $I)
    {
        $I->wantTo('проверить строгий режим с RBAC и требованием авторизации');
		
		$this->overrideParams([
			'useRBAC' => true,
			'authorizedView' => true,
		]);
		
		$this->logout($I);
		$this->revokeRoles();
        // Без авторизации все должно возвращать 401
        $I->amOnPage('/comps/index');
        $I->seeResponseCodeIs(401);
        
        $I->amOnPage("/comps/view?id={$this->testComp->id}");
        $I->seeResponseCodeIs(401);
        
        $I->amOnPage('/comps/create');
        $I->seeResponseCodeIs(401);
        
        $I->amOnPage("/comps/update?id={$this->testComp->id}");
        $I->seeResponseCodeIs(401);

		//после входа все еще ничего не доступно
		$this->login($I);
		$I->amOnPage('/comps/index');
		$I->seeResponseCodeIs(403);
		
		//после предоставления роли viewer доступно чтение
		$this->addRole('viewer');
		$this->logout($I);
		$this->login($I);
		
		$I->amOnPage('/comps/index');
		$I->seeResponseCodeIs(200);
		
		
		$this->addRole('techs-editor');

		$I->amOnPage('/comps/create');
		$I->seeResponseCodeIs(403);
		
		// Редактирование оборудования должно стать доступно
		$I->amOnPage('/techs/create');
		$I->seeResponseCodeIs(200);
	}
    
    /**
     * Тест режима: авторизация требуется, RBAC выключен
     * Авторизованные пользователи имеют полный доступ
     */
    public function testModeNoRbacAuthRequired(AcceptanceTester $I)
    {
        $I->wantTo('проверить режим с требованием авторизации без RBAC');
		
		$this->overrideParams([
			'useRBAC' => false,
			//'localAuth' => true,
			'authorizedView' => true,
		]);
		
		$this->revokeRoles();
		$this->logout($I);
		
		// Без авторизации ничего не доступно
		$I->amOnPage('/comps/index');
		$I->seeResponseCodeIs(401);
					
		$this->login($I);
		
        // После авторизации все доступно
        $I->amOnPage('/comps/index');
        $I->seeResponseCodeIs(200);
        
        $I->amOnPage("/comps/view?id={$this->testComp->id}");
        $I->seeResponseCodeIs(200);
        
        $I->amOnPage('/comps/create');
        $I->seeResponseCodeIs(200);
        
        $I->amOnPage("/comps/update?id={$this->testComp->id}");
        $I->seeResponseCodeIs(200);
		
    }
	
}