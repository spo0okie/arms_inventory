<?php

namespace tests\unit\models;

use app\generation\ModelFactory;
use app\models\AccessTypes;
use app\models\Acls;
use app\models\Services;
use app\models\Techs;
use Codeception\Test\Unit;

/**
 * Тесты механизма копирования «по образцу» (plans/access-defaults-and-copy.md):
 * ArmsModel::copyPrefillAttributes() / copyPrefillFrom().
 *
 * В копию переносятся safe-атрибуты дефолтного сценария без первичного ключа,
 * readOnly-атрибутов, обратных ссылок и пер-модельных исключений $dontCopyAttrs.
 */
class CopyPrefillTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/**
	 * Общие исключения: PK и readOnly-атрибуты не переносятся,
	 * обычные атрибуты и прямые связи — переносятся.
	 */
	public function testCommonExclusions()
	{
		$attrs=(new Services())->copyPrefillAttributes();

		$this->assertNotContains('id',$attrs,'Первичный ключ не переносится в копию');
		$this->assertNotContains('updated_at',$attrs,'readOnly-атрибуты не переносятся в копию');
		$this->assertNotContains('updated_by',$attrs,'readOnly-атрибуты не переносятся в копию');
		$this->assertContains('name',$attrs);
		$this->assertContains('responsible_id',$attrs,'Прямые ссылки *_id переносятся');
		$this->assertContains('default_access_types_ids',$attrs,'Прямые связи *_ids переносятся');
	}

	/**
	 * Пер-модельные исключения $dontCopyAttrs: уникальные/инвентарные атрибуты
	 * оборудования не переносятся.
	 */
	public function testDontCopyAttrs()
	{
		$attrs=(new Techs())->copyPrefillAttributes();

		foreach (['num','inv_num','sn','uid','ip','mac','hostname'] as $attr) {
			$this->assertNotContains($attr,$attrs,"Атрибут $attr из dontCopyAttrs не должен переноситься");
		}
		$this->assertContains('model_id',$attrs);
		$this->assertContains('services_ids',$attrs);
	}

	/**
	 * Обратные ссылки (дети) не переносятся: копия ACL не забирает чужие ACE.
	 */
	public function testReverseLinksExcluded()
	{
		$this->assertNotContains('aces_ids',(new Acls())->copyPrefillAttributes());
		$this->assertNotContains('aces_ids',(new AccessTypes())->copyPrefillAttributes());
		$this->assertNotContains('default_services_ids',(new AccessTypes())->copyPrefillAttributes());
		//прямая m2m-связь при этом переносится
		$this->assertContains('children_ids',(new AccessTypes())->copyPrefillAttributes());
	}

	/**
	 * copyPrefillFrom переносит значения, включая m2m-связи, и не трогает PK.
	 */
	public function testCopyPrefillFrom()
	{
		$type=new AccessTypes();
		$type->name='копия-тип';
		$type->is_ip=1;
		$type->ip_params_def='TCP 443';
		$this->assertTrue($type->save(),'Не удалось сохранить тип доступа для фикстуры');

		/** @var Services $source */
		$source=ModelFactory::create(Services::class,['empty'=>true]);
		$this->assertIsObject($source,'Не удалось создать сервис-образец');
		$source->defaultIpParams=[$type->id=>'TCP 8140'];
		$source->default_access_types_ids=[$type->id];
		$this->assertTrue($source->save(),'Не удалось сохранить сервис-образец');

		//сервисное переопределение сетевых параметров легло в junction (читаем свежей моделью)
		$reloaded=Services::findOne($source->id);
		$this->assertEquals([$type->id=>'TCP 8140'],$reloaded->defaultIpParams,
			'Переопределение IP-параметров должно сохраниться в junction-таблице');

		$copy=new Services();
		$copy->copyPrefillFrom($reloaded);

		$this->assertNull($copy->id,'PK копии должен остаться пустым');
		$this->assertEquals($reloaded->name,$copy->name);
		$this->assertEquals([$type->id],(array)$copy->default_access_types_ids,
			'M2M-связи образца должны предзаполниться в копии');
		$this->assertEquals([$type->id=>'TCP 8140'],$copy->defaultIpParams,
			'Переопределения IP-параметров образца должны предзаполниться в копии');
	}
}
