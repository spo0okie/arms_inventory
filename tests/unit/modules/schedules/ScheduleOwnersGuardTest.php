<?php

namespace tests\unit\modules\schedules;

use app\helpers\ModelHelper;
use app\modules\schedules\components\ScheduleOwnerBehavior;
use app\modules\schedules\models\Schedules;
use app\modules\schedules\models\SchedulesEntries;
use Codeception\Test\Unit;

/**
 * Сторож владельцев расписаний (issue #139).
 *
 * Инвариант индивидуального расписания держится на том, что КАЖДЫЙ владелец
 * зовет сборщик мусора, отпуская расписание. Новая ссылка на Schedules без
 * ScheduleOwnerBehavior означает молчаливую утечку: индивидуальное расписание
 * останется сиротой и всплывет в общем списке.
 */
class ScheduleOwnersGuardTest extends Unit
{
	public function testEveryScheduleLinkIsCoveredByBehavior(): void
	{
		$uncovered=[];
		$checked=0;

		foreach (ModelHelper::getModelClasses() as $class) {
			//зеркала не пишут в БД самостоятельно
			if (preg_match('/(History|Search)$/',$class)) continue;
			if (is_a($class,Schedules::class,true)) continue;
			//запись расписания - часть расписания, а не владелец: она не удерживает
			//расписание и удаляется вместе с ним (Schedules::beforeDelete)
			if (is_a($class,SchedulesEntries::class,true)) continue;

			try {
				$model=new $class();
				$links=$model->getLinksSchema();
			} catch (\Throwable $e) {
				continue;
			}

			foreach ($links as $attribute=>$schema) {
				//прямая ссылка-колонка на расписание (обратные связи живут на той стороне)
				if (!$model->hasAttribute($attribute)) continue;
				$target=is_array($schema)?($schema[0]??null):$schema;
				if (!is_string($target) || !is_a($target,Schedules::class,true)) continue;

				$checked++;
				if (!$this->covered($model,$attribute)) $uncovered[]=$class.'::'.$attribute;
			}
		}

		$this->assertGreaterThan(0,$checked,'Ссылки на расписания вообще должны находиться');
		$this->assertSame([],$uncovered,
			"Ссылки на расписание без ScheduleOwnerBehavior (индивидуальное расписание останется сиротой):\n"
			.implode("\n",$uncovered)
		);
	}

	/**
	 * Прикрыт ли атрибут поведением владельца расписания.
	 * @param \app\models\base\ArmsModel $model
	 * @param string $attribute
	 * @return bool
	 */
	private function covered($model, string $attribute): bool
	{
		foreach ($model->getBehaviors() as $behavior) {
			if (!($behavior instanceof ScheduleOwnerBehavior)) continue;
			if (in_array($attribute,$behavior->attributes)) return true;
		}
		return false;
	}
}
