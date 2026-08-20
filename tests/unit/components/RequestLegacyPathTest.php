<?php

namespace tests\unit\components;

use app\components\Request;
use Codeception\Test\Unit;
use Yii;

/**
 * Совместимость со старой схемой публикации: адреса с лишним токеном `/web/`
 * ({@see Request}, docs/help/admin/install.md).
 *
 * Канон - DocumentRoot=<проект>/web, при нём baseUrl пуст и адреса чистые.
 * Пока внешние интеграции ходят по старым адресам, `/web/api/users` должен
 * маршрутизироваться ровно как `/api/users`.
 */
class RequestLegacyPathTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * Запрос в канонической публикации (DocumentRoot=web, baseUrl пуст).
	 */
	protected function request($url,$scriptUrl='/index.php',$config=[]): Request
	{
		$request=new Request($config);
		$request->setScriptUrl($scriptUrl);
		$request->setUrl($url);
		return $request;
	}

	/** легаси-адрес разбирается как канонический */
	public function testLegacyPrefixStripped()
	{
		$request=$this->request('/web/api/users?login=ivanov');
		$this->assertSame('api/users',$request->getPathInfo());
		$this->assertTrue($request->getIsLegacyPath());
	}

	/** нормализуется весь URL: из него берётся action форм (Url::to('')) */
	public function testLegacyUrlNormalized()
	{
		$this->assertSame('/api/users?login=ivanov',$this->request('/web/api/users?login=ivanov')->getUrl());
		$this->assertSame('/',$this->request('/web')->getUrl());
		$this->assertSame('/?showArchived=1',$this->request('/web?showArchived=1')->getUrl());
	}

	/** канонический адрес не меняется */
	public function testCanonicalPathUntouched()
	{
		$request=$this->request('/api/users?login=ivanov');
		$this->assertSame('api/users',$request->getPathInfo());
		$this->assertFalse($request->getIsLegacyPath());
	}

	/** легаси-корень = главная страница */
	public function testLegacyRoot()
	{
		$this->assertSame('',$this->request('/web')->getPathInfo());
		$this->assertSame('',$this->request('/web/')->getPathInfo());
	}

	/** срезается только отдельный токен пути, а не начало слова */
	public function testSimilarPathNotStripped()
	{
		$this->assertSame('website/index',$this->request('/website/index')->getPathInfo());
		$this->assertSame('webinars',$this->request('/webinars')->getPathInfo());
	}

	/** в старой схеме публикации (DocumentRoot=корень) префикс лежит в baseUrl и вычитается штатно */
	public function testLegacyDocumentRootStillWorks()
	{
		$request=$this->request('/web/api/users','/web/index.php');
		$this->assertSame('/web',$request->getBaseUrl());
		$this->assertSame('api/users',$request->getPathInfo());
		$this->assertFalse($request->getIsLegacyPath());
	}

	/** точка входа в адресе (showScriptName): лишний токен идёт после неё */
	public function testLegacyPrefixAfterEntryScript()
	{
		$request=$this->request('/index-test.php/web/api/users','/index-test.php');
		$this->assertSame('api/users',$request->getPathInfo());
		$this->assertSame('/index-test.php/api/users',$request->getUrl());
		$this->assertTrue($request->getIsLegacyPath());
	}

	/** слой совместимости выключается настройкой, когда интеграции перенастроены */
	public function testCompatibilityCanBeDisabled()
	{
		$request=$this->request('/web/api/users','/index.php',['legacyPathPrefix'=>null]);
		$this->assertSame('web/api/users',$request->getPathInfo());
		$this->assertFalse($request->getIsLegacyPath());
	}

	/** маршрутизатор приложения выводит из легаси-адреса штатный маршрут */
	public function testUrlManagerParsesLegacyRequest()
	{
		$urlManager=Yii::$app->urlManager;
		$this->assertSame(
			$urlManager->parseRequest($this->request('/api/users')),
			$urlManager->parseRequest($this->request('/web/api/users'))
		);
	}
}
