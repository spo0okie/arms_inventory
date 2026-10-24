<?php

namespace tests\unit\models;

use app\components\LinkObjectWidget;
use app\generation\ModelFactory;
use app\models\LicGroups;
use app\models\LicTypes;
use app\models\TechModels;
use app\models\TechStates;
use app\models\Techs;
use app\models\TechTypes;
use Codeception\Test\Unit;
use Yii;

/** Проверяет доступность удаления в шапке по реальным обратным связям справочников. */
class CardHeaderReverseLinksTest extends Unit
{
	private $transaction;

	/** Изолирует все создаваемые фабрикой данные в транзакции тестовой БД. */
	protected function _before()
	{
		$this->transaction = Yii::$app->db->beginTransaction();
	}

	/** Откатывает справочники, дочерние объекты и служебные записи. */
	protected function _after()
	{
		if ($this->transaction->isActive) $this->transaction->rollBack();
	}

	/** Удаление доступно только без дочерних объектов; статический заголовок не содержит действий. */
	public function testReverseLinksControlHeaderActions(): void
	{
		foreach ([
			[LicTypes::class, LicGroups::class, 'lic_types_id', 'lic_groups_ids'],
			[TechTypes::class, TechModels::class, 'type_id', 'tech_models_ids'],
			[TechStates::class, Techs::class, 'state_id', 'techs_ids'],
		] as [$parentClass, $childClass, $foreignKey, $reverseAttribute]) {
			$parent = ModelFactory::create($parentClass, ['empty' => true]);
			$this->assertTrue($parent->attributeIsReverseLink($reverseAttribute));
			$this->assertSame(0, array_sum($parent->nonDeletableReverseLinks()));
			$this->assertStringContainsString('delete-item-button', LinkObjectWidget::widget(['model' => $parent]));

			$child = ModelFactory::create($childClass, [
				'empty' => true, 'overrides' => [$foreignKey => $parent->id],
			]);
			$parent = $parentClass::findOne($parent->id);
			$this->assertSame(1, array_sum($parent->nonDeletableReverseLinks()));
			$this->assertEquals([$child->id], $parent->$reverseAttribute);
			// Копия справочника не должна присваивать себе дочерние объекты оригинала.
			$this->assertNotContains($reverseAttribute, $parent->copyPrefillAttributes());

			$html = LinkObjectWidget::widget(['model' => $parent, 'hideUndeletable' => false]);
			$this->assertStringNotContainsString('delete-item-button', $html);
			$this->assertStringContainsString('fa-lock', $html);
			$this->assertStringContainsString('/update', $html);
			$html = LinkObjectWidget::widget(['model' => $parent, 'static' => true, 'hideUndeletable' => false]);
			$this->assertStringNotContainsString('delete-item-button', $html);
			$this->assertStringNotContainsString('fa-lock', $html);
			$this->assertStringNotContainsString('/update', $html);

			// Проверяем и возврат разрешения после исчезновения последней обратной связи.
			$this->assertSame(1, $child->delete());
			$parent = $parentClass::findOne($parent->id);
			$this->assertSame(0, array_sum($parent->nonDeletableReverseLinks()));
			$this->assertStringContainsString('delete-item-button', LinkObjectWidget::widget(['model' => $parent]));
		}
	}
}
