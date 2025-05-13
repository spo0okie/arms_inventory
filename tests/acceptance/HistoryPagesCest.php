<?php

use app\helpers\StringHelper;
use yii\helpers\FileHelper;

class HistoryPagesCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    // tests
    public function tryToTest(AcceptanceTester $I)
    {
		$modelsPath = Yii::getAlias('@app/models');
		$modelFiles = FileHelper::findFiles($modelsPath, [
			'only' => ['*.php'],
			'recursive' => true,
		]);
		
		foreach ($modelFiles as $file) {
			$relativePath = str_replace($modelsPath . DIRECTORY_SEPARATOR, '', $file);
			$classPath = str_replace(['/', '\\'], '\\', rtrim($relativePath, '.php'));
			$className = 'app\\models\\' . str_replace('.php', '', $classPath);
			
			//проверяем что это класс истории
			if (!StringHelper::endsWith($className, 'History')) continue;
			
			// Проверим, действительно ли это класс и он существует
			if (!class_exists($className)) {
				$I->comment("Class $className does not exist");
				continue;
			}
			
			//выясняем кто у нас мастер-класс
			$masterClass=str_replace('History','',$className);
			if (!class_exists($masterClass)) {
				$I->comment("Master class $masterClass does not exist");
				continue;
			}
			
			//выбираем самый часто изменяемый master_id для этого класса
			$id=$className::find()
				->select(['master_id', 'cnt' => 'COUNT(*)'])
				->groupBy('master_id')
				->orderBy(['cnt' => SORT_DESC])
				->limit(1)
				->asArray()
				->one()['master_id'] ?? null;
			
			if (!$id) {
				// Если нет id, то пропускаем
				$I->comment("No id with history found for class $masterClass");
				continue;
			}
			
			$route='history/journal?class='.$className.'&id='.$id;
			$I->amOnPage("/$route");
			$I->seeResponseCodeIs(200,"GET $route is accessible");
		}
    }
}
