<?php

/**
 * Совместимость со старой схемой публикации: адреса с лишним токеном `/web`.
 *
 * Первый релиз публиковался с DocumentRoot в корне проекта, из-за чего во всех
 * адресах присутствовал `/web` (docs/help/admin/install.md#адреса-приложения).
 * Внешние интеграции до сих пор ходят по таким адресам, поэтому приложение
 * обслуживает их наравне с каноническими - прозрачно, без редиректа
 * ({@see app\components\Request}).
 *
 * Тестовое окружение само опубликовано по старой схеме (entryUrl
 * .../web/index-test.php), поэтому легаси-адрес здесь моделируется лишним
 * токеном ПОСЛЕ точки входа: `/web/index-test.php/web/techs/index` - ровно тот
 * путь, который на бою приходит как `/web/techs/index`.
 */
class LegacyWebPrefixCest
{
	/** страница открывается и по каноническому, и по легаси-адресу */
	public function legacyPathServesPage(AcceptanceTester $I)
	{
		$I->amOnPage('/techs/index');
		$I->seeResponseCodeIs(200);
		$I->seeElement('table');

		$I->amOnPage('/web/techs/index');
		$I->seeResponseCodeIs(200);
		$I->seeElement('table');
	}

	/** ссылки на странице остаются каноническими - пришедший по старому адресу «вылечивается» */
	public function legacyPageRendersCanonicalLinks(AcceptanceTester $I)
	{
		$I->amOnPage('/web/techs/index');
		//точка входа в тестовом окружении сама лежит в /web, поэтому следом легаси-адреса
		//в разметке был бы удвоенный префикс
		$I->dontSeeInSource('index-test.php/web/');
	}

	/** REST-эндпоинты интеграций тоже отвечают по старому адресу */
	public function legacyPathServesApi(AcceptanceTester $I)
	{
		$I->amOnPage('/api/tech-types');
		$I->seeResponseCodeIs(200);
		$I->seeInSource('"name"');

		$I->amOnPage('/web/api/tech-types');
		$I->seeResponseCodeIs(200);
		$I->seeInSource('"name"');
	}
}
