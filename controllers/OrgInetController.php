<?php

namespace app\controllers;

use app\models\OrgInet;

/**
 * OrgInetController implements the CRUD actions for OrgInet model.
 *
 * OrgInet представляет интернет-ввод организации (провайдерское подключение к помещению).
 * Данные модели тесно связаны с Places, Services и Networks через внешние ключи,
 * которые должны существовать в БД до создания записи.
 * Все CRUD-действия унаследованы из {@see ArmsBaseController}.
 */
class OrgInetController extends ArmsBaseController
{
	/**
	 * Acceptance test data for actionView (унаследованного).
	 *
	 * Тест пропущен, так как OrgInet требует набора связанных объектов:
	 * Places, Services, Networks — которые должны существовать в БД.
	 * ModelFactory не поддерживает OrgInet (нет определения фабрики),
	 * поэтому автоматическое создание тестовой записи через getTestData() невозможно.
	 * Для включения теста необходимо добавить OrgInet в ModelFactory.
	 *
	 * @return array
	 */
	public function testView(): array
	{
		return self::skipScenario('default', 'requires external integration context: ModelFactory does not support OrgInet');
	}

	/**
	 * Acceptance test data for actionTtip (унаследованного).
	 *
	 * Тест пропущен по тем же причинам, что и testView():
	 * OrgInet требует связанных Places/Services/Networks,
	 * ModelFactory не поддерживает OrgInet — создание тестовой записи невозможно.
	 *
	 * @return array
	 */
	public function testTtip(): array
	{
		return self::skipScenario('default', 'requires external integration context: ModelFactory does not support OrgInet');
	}
	
	public $modelClass=OrgInet::class;
}
