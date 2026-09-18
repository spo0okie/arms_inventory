<?php

namespace tests\unit\models;

use app\models\Aces;
use app\models\AcesSearch;
use app\models\Acls;
use app\models\Networks;
use app\models\Segments;
use Codeception\Test\Unit;
use Yii;

/**
 * Сегмент в списках доступа и матрица межсегментного доступа
 * (plans/access-chains.md, итерация 4; issue #220).
 *
 * Опора на демо-данные (tests/_data/demo-seed/13-segments-access.sql):
 *   ACL 9300 → сегмент 6 «Сеть серверов»: ACE 9300 (сегмент 1), 9301 (сегмент 8),
 *                                          9302 (сервис 20 — НЕ сегмент)
 *   ACL 9301 → сегмент 9 «Сегмент управления»: ACE 9303 (сегмент 8)
 *   ACL 9302 → сегмент 10 «Внешний»: ACE 9304 (сегмент 1)
 *   сети 1,2 — в сегменте 6.
 *
 * Данные создаются в транзакции и откатываются (unit-suite без cleanup).
 */
class SegmentsAccessTest extends Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	/** @var \yii\db\Transaction */
	private $transaction;

	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
		Segments::invalidateAllItemsCache();
	}

	protected function _after()
	{
		if ($this->transaction && $this->transaction->isActive) {
			$this->transaction->rollBack();
		}
		Segments::invalidateAllItemsCache();
	}

	/** @param Aces[] $aces */
	private function ids(array $aces): array
	{
		$ids = array_map(static fn(Aces $ace) => (int)$ace->id, $aces);
		sort($ids);
		return $ids;
	}

	// --- сегмент как ресурс и как субъект ------------------------------------

	public function testSegmentIsValidSoleResource()
	{
		$acl = new Acls(['segments_id' => 6]);
		$this->assertTrue($acl->validate(), print_r($acl->errors, true));

		$empty = new Acls();
		$this->assertFalse($empty->validate(), 'без ресурса по-прежнему нельзя');
	}

	public function testSegmentResourceUnfoldsIntoNetworksAndServiceNodes()
	{
		$acl = Acls::findOne(9300);
		$this->assertInstanceOf(Segments::class, $acl->resource);
		$this->assertSame('Сеть серверов', $acl->sname);

		$nodeClasses = [];
		$networkIds = [];
		foreach ($acl->nodes as $node) {
			$nodeClasses[get_class($node)] = true;
			if ($node instanceof Networks) $networkIds[] = (int)$node->id;
		}
		$this->assertContains(1, $networkIds, 'подсети сегмента — узлы доступа');
		$this->assertContains(2, $networkIds);
		$this->assertArrayHasKey(\app\models\Comps::class, $nodeClasses, 'узлы сервисов сегмента — тоже узлы доступа');
	}

	public function testSegmentIsValidSoleSubject()
	{
		$ace = new Aces(['acls_id' => 9300]);
		$ace->segments_ids = [4];
		$this->assertTrue($ace->validate(), print_r($ace->errors, true));
		$this->assertTrue($ace->save());

		$ace = Aces::findOne($ace->id);
		$subjects = array_values($ace->subjects);
		$this->assertCount(1, $subjects);
		$this->assertInstanceOf(Segments::class, $subjects[0]);

		$hasNetwork = false;
		foreach ($ace->nodes as $node) if ($node instanceof Networks && (int)$node->id === 9) $hasNetwork = true;
		$this->assertTrue($hasNetwork, 'сегмент-субъект разворачивается в свои подсети');
	}

	// --- матрица --------------------------------------------------------------

	public function testMatrixHoldsOnlySegmentToSegmentAccess()
	{
		$matrix = Segments::accessMatrix();

		$this->assertSame([9300], $this->ids($matrix[1][6] ?? []), 'Открытый → Сеть серверов');
		$this->assertSame([9301], $this->ids($matrix[8][6] ?? []), 'IT → Сеть серверов');
		$this->assertSame([9303], $this->ids($matrix[8][9] ?? []), 'IT → Управление');
		$this->assertSame([9304], $this->ids($matrix[1][10] ?? []), 'Открытый → Внешний');
		$this->assertArrayNotHasKey(6, $matrix, 'из серверного сегмента доступов нет');

		//несегментный субъект (сервис → сегмент) и точечные доступы к узлам сегмента матрица игнорирует
		$all = [];
		foreach ($matrix as $row) foreach ($row as $cell) foreach ($cell as $ace) $all[] = (int)$ace->id;
		$this->assertNotContains(9302, $all, 'сервис «Мониторинг» → сегмент: субъект не сегмент');
		$this->assertNotContains(9200, $all, 'доступ к сервису внутри сегмента — не межсегментный');
	}

	public function testNonSegmentSubjectIsVisibleOnSegmentPage()
	{
		//входящие доступы сегмента (вкладка страницы сегмента) — все ACE его ACL
		$search = new AcesSearch();
		$provider = $search->search(['AcesSearch' => ['segments_resource_ids' => [6]]]);
		$this->assertSame([9300, 9301, 9302], $this->ids($provider->getModels()));

		//исходящие — ACE, где сегмент субъект
		$search = new AcesSearch();
		$provider = $search->search(['AcesSearch' => ['segments_subject_ids' => [8]]]);
		$this->assertSame([9301, 9303], $this->ids($provider->getModels()));
	}

	public function testArchivedSegmentDropsOutOfMatrix()
	{
		Yii::$app->db->createCommand()->update('segments', ['archived' => 1], ['id' => 6])->execute();
		$matrix = Segments::accessMatrix();
		$this->assertArrayNotHasKey(6, $matrix[1] ?? [], 'доступ к архивному сегменту мёртв');
		$this->assertSame([9304], $this->ids($matrix[1][10] ?? []), 'остальные на месте');
	}

	// --- входящие соединения сети ---------------------------------------------

	public function testNetworkSeesAccessToItsSegment()
	{
		$incoming = array_keys(Networks::findOne(1)->incomingAcesEffective);
		$this->assertContains(9300, $incoming, 'доступ к сегменту сети — доступ и в саму сеть');
		$this->assertContains(9301, $incoming);
		$this->assertContains(9302, $incoming, 'и от несегментного субъекта тоже');

		$foreign = array_keys(Networks::findOne(13)->incomingAcesEffective);
		$this->assertNotContains(9300, $foreign, 'сеть другого сегмента этих доступов не видит');
	}
}
