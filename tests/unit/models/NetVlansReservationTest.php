<?php

namespace tests\unit\models;

use app\components\Forms\ArmsForm;
use app\models\NetDomains;
use app\models\NetVlans;
use app\models\NetVlansSearch;
use app\models\Networks;
use app\types\AttributeRuleContext;
use app\types\VlanType;
use Codeception\Test\Unit;
use Yii;
use yii\validators\Validator;

/** Проверяет резервы, наследование, границы и атомарное создание диапазонов на тестовой БД. */
class NetVlansReservationTest extends Unit
{
	private $transaction;
	private NetDomains $domain;

	/** Создаёт изолированный домен; изменения тестовых данных всегда откатываются. */
	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
		NetVlans::deleteAll(['and', ['domain_id' => null], ['between', 'vlan', 3900, 3960]]);
		$this->domain = new NetDomains(['name' => 'VLAN reservation test']);
		$this->assertTrue($this->domain->save());
	}

	/** Возвращает тестовую БД в исходное состояние. */
	protected function _after()
	{
		if ($this->transaction->isActive) $this->transaction->rollBack();
	}

	/** Создаёт резерв или локальный VLAN для проверяемого сценария. */
	private function makeVlan(int $number, ?int $domainId = null, string $name = '', string $comment = ''): NetVlans
	{
		$model = new NetVlans(['vlan' => $number, 'domain_id' => $domainId, 'name' => $name, 'comment' => $comment]);
		$this->assertTrue($model->save(), print_r($model->errors, true));
		return $model;
	}

	/** Тип и модель одинаково принимают стандартные номера и отвергают неверный ввод. */
	public function testNumberValidation(): void
	{
		$valid = [1, 1002, 1005, 4094, '1-4094', '1002-1005', ' 10 - 20 '];
		$invalid = [0, 4095, 4096, -1, '', null, '1.5', '1e2', '12foo', '10-9', '1-4095', '1-2-3', '1.2-3', [], true];
		foreach (array_merge($valid, $invalid) as $value) {
			$expected = in_array($value, $valid, true);
			$model = new NetVlans(['vlan' => $value]);
			$model->validateVlanRange('vlan');
			$this->assertSame($expected, !$model->hasErrors(), var_export($value, true));
			$model->clearErrors();
			$rule = (new VlanType())->rules(new AttributeRuleContext($model, 'vlan'))[0]->toYiiRule('vlan', $model);
			$validator = Validator::createValidator($rule[1], $model, $rule[0], array_slice($rule, 2));
			$validator->validateAttribute($model, 'vlan');
			$this->assertSame($expected, !$model->hasErrors(), var_export($value, true));
		}
		$model = $this->makeVlan(3900);
		$model->vlan = '3900-3901';
		$this->assertFalse($model->validate());
	}

	/** NULL и пустой выбор домена обозначают один резерв; локальные VLAN ему не конфликтуют. */
	public function testUniqueNumbersAndDomains(): void
	{
		$reserve = $this->makeVlan(3900);
		$this->assertNull($reserve->parent);
		$this->assertTrue($reserve->save());
		foreach ([null, ''] as $domain) {
			$duplicate = new NetVlans(['vlan' => 3900, 'domain_id' => $domain]);
			$this->assertFalse($duplicate->save());
			$this->assertTrue($duplicate->hasErrors('vlan'));
		}
		$local = $this->makeVlan(3900, $this->domain->id);
		$duplicate = new NetVlans(['vlan' => 3900, 'domain_id' => $this->domain->id]);
		$this->assertFalse($duplicate->save());
		$this->assertTrue($local->save());
		$other = new NetDomains(['name' => 'Other VLAN domain']);
		$this->assertTrue($other->save());
		$this->makeVlan(3900, $other->id);
	}

	/** Пустые поля наследуются без копирования; AJAX отражает смену номера и очистку домена. */
	public function testInheritanceAndPlaceholders(): void
	{
		$reserve = $this->makeVlan(3900, null, 'Телефония', 'Общее назначение');
		$this->makeVlan(3901, null, 'Камеры', 'Видеонаблюдение');
		$local = $this->makeVlan(3900, $this->domain->id);
		$this->assertSame('Телефония', $local->nameRecursive);
		$this->assertSame('Общее назначение', $local->commentRecursive);
		$this->assertSame('', NetVlans::findOne($local->id)->name);
		$form = ArmsForm::validate($local);
		$this->assertStringContainsString('Телефония', $form[ArmsForm::PLACEHOLDERS_ATTR]['netvlans-name']);
		$local->vlan = 3901;
		$form = ArmsForm::validate($local);
		$this->assertStringContainsString('Камеры', $form[ArmsForm::PLACEHOLDERS_ATTR]['netvlans-name']);
		$local->domain_id = '';
		$form = ArmsForm::validate($local);
		$this->assertSame('', $form[ArmsForm::PLACEHOLDERS_ATTR]['netvlans-name']);
		$local->domain_id = $this->domain->id;
		$local->name = 'Свое имя';
		$local->comment = 'Свое пояснение';
		$this->assertTrue($local->save());
		$this->assertSame('Свое имя', $local->nameRecursive);
		$this->assertSame('Свое пояснение', $local->commentRecursive);
		$reserve->name = '';
		$this->assertTrue($reserve->save());
		$this->assertEmpty($reserve->nameRecursive);
	}

	/** L3-сеть выбирает только локальный VLAN; очистка его домена блокируется. */
	public function testNetworkCannotUseReservation(): void
	{
		$reserve = $this->makeVlan(3900, null, 'Телефония');
		$local = $this->makeVlan(3900, $this->domain->id);
		$network = new Networks(['text_addr' => '192.0.2.0/24', 'vlan_id' => $reserve->id]);
		$this->assertFalse($network->validate(['vlan_id']));
		$network->vlan_id = $local->id;
		$this->assertTrue($network->save(), print_r($network->errors, true));
		$local->domain_id = '';
		$this->assertFalse($local->save());
		$this->assertTrue($local->hasErrors('domain_id'));
		$choices = NetVlans::fetchNames(true);
		$this->assertArrayNotHasKey($reserve->id, $choices);
		$this->assertSame('Телефония (3900)', $choices[$local->id]);
	}

	/** Конфликт в середине диапазона не оставляет созданные префиксные VLAN. */
	public function testRangeConflictAndCreation(): void
	{
		$this->makeVlan(3901, $this->domain->id);
		$model = new NetVlans(['vlan' => '3900-3902', 'domain_id' => $this->domain->id]);
		$this->assertFalse($model->save());
		$this->assertFalse(NetVlans::find()->where(['domain_id' => $this->domain->id, 'vlan' => [3900, 3902]])->exists());
		$model = new NetVlans(['vlan' => '3910-3912', 'domain_id' => $this->domain->id, 'name' => 'VLAN {VLAN}']);
		$this->assertTrue($model->save(), print_r($model->errors, true));
		$this->assertEquals(3912, $model->vlan);
		$this->assertSame(['VLAN 3910', 'VLAN 3911', 'VLAN 3912'], NetVlans::find()
			->where(['domain_id' => $this->domain->id])->andWhere(['between', 'vlan', 3910, 3912])->orderBy('vlan')->select('name')->column());
		$this->makeVlan(3921);
		$reserveRange = new NetVlans(['vlan' => '3920-3922', 'domain_id' => '']);
		$this->assertFalse($reserveRange->save());
		$this->assertFalse(NetVlans::find()->where(['domain_id' => null, 'vlan' => [3920, 3922]])->exists());
	}

	/** Отказ при сохранении последнего VLAN откатывает уже вставленные строки. */
	public function testRangeRollbackOnSaveFailure(): void
	{
		$model = new NetVlans(['vlan' => '3930-3932', 'domain_id' => $this->domain->id]);
		$model->on(NetVlans::EVENT_BEFORE_INSERT, static function ($event) {
			$event->isValid = false;
		});
		$this->assertFalse($model->save());
		$this->assertSame('3930-3932', $model->vlan);
		$this->assertTrue($model->isNewRecord);
		$this->assertFalse(NetVlans::find()->where(['domain_id' => $this->domain->id])->exists());
	}

	/** Грид объединяет резерв с локальным VLAN, а чужой домен не занимает номер. */
	public function testDomainGridAndInheritedSearch(): void
	{
		$this->makeVlan(3900, null, 'ZZZ inherited', 'Описание резерва');
		$local = $this->makeVlan(3900, $this->domain->id);
		$this->makeVlan(3901, null, 'AAA reserved', 'Описание свободного');
		$other = new NetDomains(['name' => 'Other domain']);
		$this->assertTrue($other->save());
		$this->makeVlan(3902, $other->id, 'Чужой');
		$search = new NetVlansSearch();
		$provider = $search->searchDomain($this->domain->id, ['NetVlansSearch' => ['availability' => 'all', 'vlan' => '3900-3903']]);
		$rows = $provider->getModels();
		$this->assertCount(4, $rows);
		$this->assertSame([false, true, true, true], array_map(static fn($row) => $row->isNewRecord, $rows));
		$this->assertNotNull($rows[1]->parent);
		$this->assertNull($rows[2]->parent);
		$this->assertSame($this->domain->id, $rows[1]->domain_id);
		$this->assertNull($rows[1]->id);
		$this->assertSame([], $rows[1]->networks);
		$this->assertSame('ZZZ inherited', $rows[0]->nameRecursive);
		$this->assertSame('Описание резерва', $rows[0]->commentRecursive);
		$this->assertSame('AAA reserved', $rows[1]->nameRecursive);
		$this->assertEmpty($rows[2]->nameRecursive);
		$provider = (new NetVlansSearch())->searchDomain($this->domain->id,
			['NetVlansSearch' => ['availability' => 'free', 'vlan' => '3900-3903']]);
		$this->assertSame([3901, 3902, 3903], array_column($provider->getModels(), 'vlan'));
		$provider = (new NetVlansSearch())->searchDomain($this->domain->id, []);
		$this->assertSame([3900], array_column($provider->getModels(), 'vlan'));
		// Режим включает локальные VLAN без резерва и не дублирует зарезервированный локальный номер.
		$this->makeVlan(3903, $this->domain->id, 'Только локальный');
		$provider = (new NetVlansSearch())->searchDomain($this->domain->id,
			['NetVlansSearch' => ['availability' => 'used_reserved', 'vlan' => '3900-3904']]);
		$this->assertSame([3900, 3901, 3903], array_column($provider->getModels(), 'vlan'));
		$this->assertSame([false, true, false], array_map(static fn($row) => $row->isNewRecord, $provider->getModels()));
		$provider = (new NetVlansSearch())->search(['NetVlansSearch' => ['name' => 'ZZZ inherited', 'comment' => 'Описание резерва']]);
		$this->assertContains($local->id, array_column($provider->getModels(), 'id'));
		// Сортировка общего списка и селектор должны работать с тем же SQL наследования.
		$provider->sort->setAttributeOrders(['comment' => SORT_DESC, 'vlan' => SORT_ASC]);
		$provider->refresh();
		$this->assertContains($local->id, array_column($provider->getModels(), 'id'));
	}

	/** Карточка использует фильтры общего поиска, включая сети, диапазоны и альтернативы через |. */
	public function testDomainReusesVlanSearch(): void
	{
		$this->makeVlan(3900, null, 'Телефония', 'Общее назначение');
		$local = $this->makeVlan(3900, $this->domain->id);
		$this->makeVlan(3901, null, 'Камеры', 'Видеонаблюдение');
		$this->makeVlan(3901, $this->domain->id, 'Локальное имя', 'Локальное пояснение');
		$this->makeVlan(3902, null, 'Камеры', 'Видеонаблюдение');
		foreach (['192.0.2.0/25', '192.0.2.128/25'] as $address) {
			$network = new Networks(['text_addr' => $address, 'name' => 'Голос', 'vlan_id' => $local->id]);
			$this->assertTrue($network->save(), print_r($network->errors, true));
		}
		foreach ([
			['networks_ids' => 'нет совпадения|192.0.2.0/25'],
			['networks_ids' => 'Голос'],
			['name' => 'нет совпадения|Телефония', 'comment' => 'нет совпадения|Общее'],
			['name' => 'нет совпадения|3900'],
			['vlan' => '3900-3901'],
		] as $filter) {
			$params = ['NetVlansSearch' => $filter + ['availability' => 'all']];
			$common = (new NetVlansSearch())->search($params)->query
				->andWhere(['net_vlans.domain_id' => $this->domain->id])->all();
			$provider = (new NetVlansSearch())->searchDomain($this->domain->id, $params);
			// Сравниваем полные выборки; страницы двух списков могут иметь разную сортировку.
			$provider->pagination = false;
			$rows = $provider->getModels();
			$actual = array_filter($rows, static fn($row) => !$row->isNewRecord);
			$this->assertEqualsCanonicalizing(array_column($common, 'id'), array_column($actual, 'id'));
			if (isset($filter['networks_ids'])) {
				$this->assertCount(1, $rows);
				// JOIN для фильтра не обрезает список сетей, отображаемый в колонке.
				$this->assertCount(2, reset($rows)->networks);
			}
		}
		$params = ['NetVlansSearch' => [
			'availability' => 'all', 'vlan' => '3900-3903',
			'name' => 'нет совпадения|Камеры', 'comment' => 'нет совпадения|Видеонаблюдение',
		]];
		$rows = (new NetVlansSearch())->searchDomain($this->domain->id, $params)->getModels();
		// Номер 3901 занят переименованным VLAN: совпадение резерва не возвращает его как свободный.
		$this->assertSame([3902], array_column($rows, 'vlan'));
		$this->assertTrue(reset($rows)->isNewRecord);
		$params['NetVlansSearch']['availability'] = 'free';
		$this->assertSame([3902], array_column(
			(new NetVlansSearch())->searchDomain($this->domain->id, $params)->getModels(), 'vlan'
		));
		foreach (['0', '3900-4095', 'не номер'] as $number) {
			$params = ['NetVlansSearch' => ['vlan' => $number]];
			$search = new NetVlansSearch();
			$this->assertSame([], $search->search($params)->getModels());
			$this->assertTrue($search->hasErrors('vlan'));
			$this->assertSame([], (new NetVlansSearch())->searchDomain($this->domain->id, $params)->getModels());
		}
	}

	/** Фильтрация предшествует пагинации, включая свободные номера без резервов. */
	public function testDomainFiltersBeforePagination(): void
	{
		$params = ['NetVlansSearch' => ['availability' => 'all']];
		$provider = (new NetVlansSearch())->searchDomain($this->domain->id, $params);
		$this->assertSame(4094, $provider->getTotalCount());
		$this->assertCount(100, $provider->getModels());
		$params['NetVlansSearch']['name'] = 'нет совпадения|3903';
		$provider = (new NetVlansSearch())->searchDomain($this->domain->id, $params);
		$this->assertSame(1, $provider->getTotalCount());
		$this->assertSame([3903], array_column($provider->getModels(), 'vlan'));
		$this->assertTrue(reset($provider->allModels)->isNewRecord);
		// У незарезервированного номера нет ни пояснения, ни связанных сетей.
		foreach (['comment', 'networks_ids'] as $attribute) {
			$filtered = $params;
			$filtered['NetVlansSearch'][$attribute] = 'нет совпадения';
			$this->assertSame([], (new NetVlansSearch())->searchDomain($this->domain->id, $filtered)->getModels());
		}
	}

	/** Общая колонка пояснения показывает наследуемый текст штатным рендерером. */
	public function testInheritedCommentRendering(): void
	{
		$this->makeVlan(3900, null, 'Резерв', 'Унаследованное пояснение');
		$local = $this->makeVlan(3900, $this->domain->id);
		$columns = require Yii::getAlias('@app/views/net-vlans/columns.php');
		$grid = new \kartik\grid\GridView([
			'dataProvider' => new \yii\data\ArrayDataProvider(['allModels' => [$local]]),
			'columns' => [],
		]);
		$column = new \app\components\gridColumns\DefaultColumn(array_merge($columns['comment'], [
			'grid' => $grid, 'attribute' => 'comment',
		]));
		$this->assertStringContainsString('Унаследованное пояснение', $column->renderDataCell($local, $local->id, 0));
		$this->assertSame('', $local->comment);
	}
}
