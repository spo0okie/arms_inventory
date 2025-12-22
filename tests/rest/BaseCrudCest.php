<?php

use app\models\ArmsModel;

class BaseCrudCest
{
    public function _before(ApiTester $I)
    {
    }

    /**
     * Базовый тест CRUD + search + filter для всех контроллеров API
     */
    public function testAllControllersCrud(ApiTester $I)
    {
        // Получаем список всех контроллеров в modules/api/controllers, кроме BaseRestController
        $controllerFiles = glob(\Codeception\Configuration::projectDir() . 'modules/api/controllers/*.php');

        foreach ($controllerFiles as $file) {
            $filename = basename($file, '.php');
            if (!in_array($filename,[
				'BaseRestController',
				'LicLinksController',
				'PhonesController',
				'ScansController'
			])) {
				$this->testControllerCrud($I, $filename);
			} else {
				$I->comment("Пропускаем базовый контроллер: {$filename}");
			}
        }

    }

    /**
     * Тестирование CRUD операций для конкретного контроллера
     */
    private function testControllerCrud(ApiTester $I, string $controllerName)
    {
        $controllerClass = "app\\modules\\api\\controllers\\{$controllerName}";
		$controllerId = lcfirst(str_replace('Controller', '', $controllerName));
		$controller=new $controllerClass($controllerId, Yii::$app);
        $modelClass = $controller->modelClass;

        // Проверяем, что модель существует
        if (!class_exists($modelClass)) {
            $I->comment("Модель {$modelClass} не найдена для контроллера {$controllerName}, пропускаем");
            return;
        }

        $I->wantTo("Тестируем контроллер: {$controllerName}");

        // Получаем disabled actions
        $disabledActions = $controller->disabledActions();

        // Получаем 2 существующие модели для тестовых данных
        /** @var ArmsModel $model1 */
        $model1 = $modelClass::find()->limit(1)->one();
        /** @var ArmsModel $model2 */
        $model2 = $modelClass::find()->limit(1)->offset(1)->one();

        if (!$model1 || !$model2) {
            $I->comment("Недостаточно данных в модели {$modelClass} для тестирования, пропускаем");
            return;
        }

        // Запоминаем данные
        $data1 = Helper\ModelData::fillForm(
			Helper\ModelData::getFormAttributes($model1),
			$model1
		);
		$data2 = Helper\ModelData::fillForm(
			Helper\ModelData::getFormAttributes($model2),
			$model2
		);
		
		//Точечные фиксы
		if ($controllerName === 'PartnersController') {
			$data1['inn']=(string)$data1['inn'];
			$data2['inn']=(string)$data2['inn'];
		}

        // Теперь выполняем CRUD тесты
		$I->haveHttpHeader('Content-Type', 'application/json');

        // 1. Index - проверка на код 200
        if (!in_array('index', $disabledActions)) {
            $I->sendGET("/{$this->getControllerRoute($controllerName)}");
            $I->seeResponseCodeIs(200);
            $I->seeResponseIsJson();
        }

        // 2. View - проверка на код 200
        if (!in_array('view', $disabledActions)) {
            $I->sendGET("/{$this->getControllerRoute($controllerName)}/{$model1->id}");
            $I->seeResponseCodeIs(200);
            $I->seeResponseIsJson();
        }
		
		// 3.1 Рвем связи
		$wipedLinks=$model1->clearReverseLinks();
		
		// 3.3 Смотрим что все удалилось
		if (!in_array('view', $disabledActions)) {
			$I->sendGET("/{$this->getControllerRoute($controllerName)}/{$model1->id}?expand=".implode(',',$model1->extraFields()));
			$I->seeResponseCodeIs(200);
		}
		
		// 3. Delete - удаляем model1
		if (!in_array('delete', $disabledActions)) {
			$I->sendDELETE("/{$this->getControllerRoute($controllerName)}/{$model1->id}");
			$I->seeResponseCodeIs(204);
		}
		
		// 5. Update - обновляем созданный объект данными model1
		if (!in_array('update', $disabledActions)) {
			$I->sendPUT("/{$this->getControllerRoute($controllerName)}/{$model2->id}", $data1);
			$I->seeResponseCodeIs(200);
		}
		$model2->restoreReverseLinks($wipedLinks);
		
		// 6. Create - создаем новый объект с данными model2
        if (!in_array('create', $disabledActions)) {
			$I->sendPOST("/{$this->getControllerRoute($controllerName)}", $data2);
			$I->seeResponseCodeIs(201);
		}

        // 7. Search - проверка на отсутствие 500
        /*if (!in_array('search', $disabledActions)) {
            $I->sendGET("/{$this->getControllerRoute($controllerName)}/search", ['id' => $model2->id]);
            $I->seeResponseCodeIsNot(500);
            $I->seeResponseIsJson();
        }*/

        // 8. Filter - проверка на отсутствие 500
        /*if (!in_array('filter', $disabledActions)) {
            $I->sendGET("/{$this->getControllerRoute($controllerName)}/filter", ['id' => $model2->id]);
            $I->seeResponseCodeIsNot(500);
            $I->seeResponseIsJson();
        }*/

        $I->comment("Тестирование контроллера {$controllerName} завершено");
    }

    /**
     * Получаем маршрут контроллера (snake_case из CamelCase)
     */
    private function getControllerRoute(string $controllerName): string
    {
        // Конвертируем CamelCase в snake_case и убираем 'Controller'
        $route = preg_replace('/Controller$/', '', $controllerName);
        $route = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $route));
        return $route;
    }
}