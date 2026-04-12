<?php

namespace app\controllers;

use app\models\Manufacturers;

/**
 * ManufacturersController implements the CRUD actions for Manufacturers model.
 */
class ManufacturersController extends ArmsBaseController
{
	/**
	 * Acceptance test data for ItemByName.
	 *
	 * Тест пропускается, так как поиск производителя по имени опирается на нормализацию
	 * через словарь производителей (ManufacturersDict). Даже если запись создана через
	 * factory/getTestData(), поиск по полю name не найдёт её без предварительно заполненного
	 * словаря, поскольку имя хранится в нормализованном виде и сопоставляется через него.
	 * Заменить skip на реальный тест можно только после реализации фикстуры ManufacturersDict.
	 */
	public function testItemByName(): array
	{
		return self::skipScenario('default', 'name lookup depends on ManufacturersDict normalization — search will not work even with a factory-created record until the dictionary is populated');
	}
	
	public $modelClass=Manufacturers::class;
}
