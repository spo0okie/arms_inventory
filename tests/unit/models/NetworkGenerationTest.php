<?php

namespace tests\unit\models;

use app\generation\context\GenerationContext;
use app\generation\ModelFactory;
use app\models\NetDomains;
use app\models\NetVlans;
use app\models\Networks;
use Codeception\Test\Unit;
use Yii;

/** Проверяет граничные условия генерации связи сеть — VLAN — L2-домен. */
class NetworkGenerationTest extends Unit
{
	private $transaction;

	/** Изолирует создаваемые генератором VLAN и домены в тестовой БД. */
	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
	}

	/** Откатывает все связанные модели после каждого сценария. */
	protected function _after()
	{
		if ($this->transaction->isActive) $this->transaction->rollBack();
	}

	/** На предельной глубине обязательный домен VLAN всё равно создаётся. */
	public function testNetworkRequiresVlanDomainAtDepthLimit(): void
	{
		// Обычную глубину уже проверяет общий набор генерации всех моделей.
		$model = ModelFactory::create(Networks::class, [
			'seed' => 321,
			'maxDepth' => 1,
			'save' => false,
		]);
		$this->assertInstanceOf(NetVlans::class, $model->netVlan);
		$this->assertNotEmpty($model->netVlan->domain_id);
		$this->assertInstanceOf(NetDomains::class, $model->netDomain);
		$this->assertTrue($model->validate(), print_r($model->errors, true));
	}

	/** Роль реального VLAN требует домен даже в пустом режиме на предельной глубине. */
	public function testRealRoleRequiresDomainAtDepthLimit(): void
	{
		$seedBefore = ModelFactory::$seed;
		$model = ModelFactory::create(NetVlans::class, [
			'role' => 'real',
			'empty' => true,
			'seed' => 12345,
			'depth' => 2,
			'maxDepth' => 2,
			'save' => false,
		]);
		$this->assertNotEmpty($model->domain_id);
		// Вложенная генерация не должна расходовать глобальный счётчик seed.
		$this->assertSame($seedBefore, ModelFactory::$seed);
	}
	/** Повторное применение роли не заменяет готовый домен и не создаёт лишних записей. */
	public function testRealRolePreservesExistingDomain(): void
	{
		$domain = new NetDomains(['name' => 'Generation test']);
		$this->assertTrue($domain->save());
		$model = new NetVlans(['domain_id' => $domain->id]);
		$countBefore = NetDomains::find()->count();
		$role = NetVlans::roles()['real'];
		$context = new GenerationContext(empty: true, seed: 321, maxDepth: 0);
		$role($model, $context);
		$role($model, $context);
		$this->assertSame($domain->id, $model->domain_id);
		$this->assertSame($countBefore, NetDomains::find()->count());
	}

	/** У пустой сети необязательный VLAN остаётся незаполненным. */
	public function testEmptyNetworkDoesNotRequireVlan(): void
	{
		$model = ModelFactory::create(Networks::class, ['empty' => true, 'save' => false]);
		$this->assertEmpty($model->vlan_id);
	}

	/** Без роли пустой VLAN по-прежнему может быть глобальным резервом без домена. */
	public function testDefaultVlanCanBeReservation(): void
	{
		// Берём свободный номер, чтобы существующие тестовые данные не влияли на проверку.
		$used = NetVlans::find()->where(['domain_id' => null])->select('vlan')->column();
		$free = array_values(array_diff(range(1, 4094), $used));
		$this->assertNotEmpty($free);
		$model = ModelFactory::create(NetVlans::class, [
			'empty' => true,
			'save' => false,
			'overrides' => ['vlan' => $free[0]],
		]);
		$this->assertNull($model->domain_id);
	}
}
