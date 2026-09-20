<?php

namespace app\modules\schedules\components;

use app\modules\schedules\models\Schedules;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;

/**
 * Поведение владельца расписания (issue #139).
 *
 * Вешается декларативно на каждую модель, у которой есть ссылка на расписание,
 * и делает две вещи в одном месте (вместо копипасты по всем потребителям):
 *
 * 1. **Сборка мусора.** При удалении владельца или переключении его на другое
 *    расписание зовет {@see Schedules::gc()} по СТАРОМУ значению ссылки:
 *    индивидуальное (безымянное) расписание, оставшееся без владельца,
 *    удаляется - выбрать его где-либо еще все равно невозможно.
 *
 * 2. **Инвариант «у индивидуального расписания один владелец».** Не дает
 *    сослаться на чужое индивидуальное расписание - в UI такого выбора нет
 *    (скрыто из {@see Schedules::fetchNames()}), но REST/консоль проставляют
 *    ссылку числом, мимо формы.
 *
 * Пример ({@see \app\models\Services}):
 * ```php
 * [
 *     'class' => ScheduleOwnerBehavior::class,
 *     'attributes' => ['providing_schedule_id','support_schedule_id'],
 * ],
 * ```
 *
 * Сторож {@see \tests\unit\modules\schedules\ScheduleOwnersGuardTest} следит,
 * чтобы у каждой ссылки на Schedules был этот behavior.
 */
class ScheduleOwnerBehavior extends Behavior
{
	/** @var string[] атрибуты-ссылки на расписание */
	public $attributes = [];

	/**
	 * @var bool объекты этого класса владеют расписанием безраздельно.
	 *
	 * Включается для {@see \app\models\Acls}: расписание временного доступа
	 * создается вместе с доступом, делится между всеми ACL этого доступа
	 * и в общем пуле не участвует. Поэтому (а) несколько записей этого класса
	 * могут ссылаться на одно индивидуальное расписание, (б) с последней из них
	 * расписание удаляется, даже если у него есть имя.
	 */
	public $exclusive = false;

	/** {@inheritdoc} */
	public function events()
	{
		return [
			ActiveRecord::EVENT_BEFORE_VALIDATE	=> 'beforeValidate',
			ActiveRecord::EVENT_AFTER_UPDATE	=> 'afterUpdate',
			ActiveRecord::EVENT_AFTER_DELETE	=> 'afterDelete',
		];
	}

	/**
	 * Запрещаем сослаться на чужое индивидуальное расписание.
	 * @return void
	 */
	public function beforeValidate()
	{
		//поисковые модели наследуют behaviors основной, но в БД не пишут: там значение
		//ссылки - условие фильтра, а не связь (конвенция проекта - суффикс Search)
		if (preg_match('/Search$/',get_class($this->owner))) return;

		/** @var ActiveRecord $model */
		$model=$this->owner;

		foreach ($this->attributes as $attribute) {
			$id=$model->$attribute;
			if (!$id) continue;
			//проверяем только то, что реально меняется: чтение расписания на каждую
			//валидацию любого владельца было бы лишним запросом
			if (!$model->isNewRecord && !$model->isAttributeChanged($attribute,false)) continue;

			/** @var Schedules $schedule */
			$schedule=Schedules::findOne($id);
			if (!is_object($schedule) || !$schedule->isPrivate) continue;

			if ($this->foreignOwners($schedule)) $model->addError($attribute,
				'Это индивидуальное расписание уже принадлежит другому объекту. '
				.'Индивидуальное расписание (без названия) используется только одним объектом - '
				.'дайте расписанию название, чтобы использовать его повторно.'
			);
		}
	}

	/**
	 * Переключили владельца на другое расписание - старое могло осиротеть.
	 * @param AfterSaveEvent $event
	 * @return void
	 */
	public function afterUpdate($event)
	{
		foreach ($this->attributes as $attribute) {
			if (!array_key_exists($attribute,$event->changedAttributes)) continue;
			$was=$event->changedAttributes[$attribute];
			if (!$was || $was==$this->owner->$attribute) continue;
			Schedules::gc($was,$this->exclusive);
		}
	}

	/**
	 * Удалили владельца - его расписание могло осиротеть.
	 * @return void
	 */
	public function afterDelete()
	{
		foreach ($this->attributes as $attribute) {
			Schedules::gc($this->owner->$attribute,$this->exclusive);
		}
	}

	/**
	 * Есть ли у расписания владельцы, кроме текущей модели.
	 * Для exclusive-владельцев (ACL) свои же записи чужими не считаются:
	 * несколько ACL одного временного доступа делят одно расписание.
	 * @param Schedules $schedule
	 * @return bool
	 */
	protected function foreignOwners($schedule): bool
	{
		$model=$this->owner;
		foreach ($schedule->usedBy as $owner) {
			if ($this->exclusive && $owner instanceof $model) continue;
			if ($owner instanceof $model && !$model->isNewRecord && $owner->id==$model->id) continue;
			return true;
		}
		return false;
	}
}
