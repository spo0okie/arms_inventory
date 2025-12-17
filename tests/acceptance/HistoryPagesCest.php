<?php

use app\helpers\StringHelper;
use PHPUnit\Framework\Assert;
use yii\helpers\FileHelper;

class HistoryPagesCest
{
	
	public function _failed($test, $fail)
	{
		Helper\Acceptance::$testsFailed = true;
	}
	
	protected function classesProvider()
	{
		Helper\Yii2::initFromFilename('test-acceptance.php');
		codecept_debug('Initializing Suite/HistoryTest DB...');
		//Подготавливаем временную БД
		Helper\Database::dropYiiDb();
		Helper\Database::prepareYiiDb();
		Helper\Database::loadSqlDump(__DIR__ . '/../_data/arms_demo.sql');
		
		$classes=[];
		$modelsPath = __DIR__.'/../../models';
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
			$classes[]=['class'=>$className];
		}
		return $classes;
	}
	
	/**
	 * @dataProvider classesProvider
	 * @return void
	 */
    public function testHistoryClass(AcceptanceTester $I, \Codeception\Example $example)
    {
		$className=$example['class'];
		// Проверим, действительно ли это класс и он существует
		Assert::assertTrue(class_exists($className), "Class $className exists");
		//выясняем кто у нас мастер-класс
		$masterClass=str_replace('History','',$className);
		//проверяем наличие мастер-класса
		Assert::assertTrue(class_exists($masterClass), "Master class $masterClass exists");
		//выбираем самый часто изменяемый master_id для этого класса
		$id=$className::find()
			->select(['master_id', 'cnt' => 'COUNT(*)'])
			->where('exists (SELECT 1 FROM '.$masterClass::tableName().' m WHERE m.id = '.$className::tableName().'.master_id)')
			->groupBy('master_id')
			->orderBy(['cnt' => SORT_DESC])
			->limit(1)
			->asArray()
			->one()['master_id'] ?? null;
		
		// Проверяем что история есть
		Assert::assertTrue($id !== null, "History records for $className exists");
		$route='history/journal?class='.$className.'&id='.$id;
		$I->amOnPage("/$route");
		$I->seeResponseCodeIs(200,"GET $route is accessible");
    }
}
