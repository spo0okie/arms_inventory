<?php

namespace app\components;

use app\models\base\ArmsModel;
use app\models\NotifyRecipientsInterface;
use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\AfterSaveEvent;
use yii\helpers\Html;

/**
 * Оповещение ответственных при смене отслеживаемого атрибута
 * (plans/notifications.md, issue #184/#64).
 *
 * Вешается на модель декларативно (behaviors()); при EVENT_AFTER_UPDATE
 * ставит письмо в outbox через Yii::$app->notifier — синхронной отправки
 * почты здесь нет, запись в очередь идёт в той же транзакции, что и
 * сохранение модели.
 *
 * Пример (Contracts): следить за статусом документа
 * ```php
 * [
 *     'class' => AttributeChangeNotifyBehavior::class,
 *     'attributes' => ['state_id'],
 * ],
 * ```
 * Получатели по умолчанию — $model->getNotifyRecipients()
 * (NotifyRecipientsInterface).
 */
class AttributeChangeNotifyBehavior extends Behavior
{
	/**
	 * @var bool|callable точечный выключатель этого триггера (обычно — флаг из params).
	 * Гасит только событийные оповещения этого behavior, не трогая остальной
	 * механизм (правила notify/watch и т.п.); мастер-рубильник notify.enable
	 * при этом продолжает действовать на всё (проверяется в Notifier).
	 * Callable вычисляется на каждое событие — params можно менять на лету.
	 */
	public $enabled = true;

	/** @var string[] атрибуты, смена которых порождает оповещение */
	public $attributes = [];

	/** @var callable|null fn(ArmsModel $model): Users[] — получатели; по умолчанию getNotifyRecipients() */
	public $recipients;

	/** @var callable|null fn(ArmsModel $model, string $attr, string $oldText, string $newText): string — тема письма */
	public $subject;

	/** @var callable|null fn(ArmsModel $model, string $attr, string $oldText, string $newText): string — HTML-тело письма */
	public $body;

	/**
	 * @var callable|null fn(ArmsModel $model, string $attr): ?string — ключ дедупликации.
	 * По умолчанию '<class>:<id>:<attr>': несколько смен атрибута до отправки
	 * схлопываются в одно письмо с последним состоянием.
	 */
	public $eventKey;

	/** {@inheritdoc} */
	public function events()
	{
		return [ActiveRecord::EVENT_AFTER_UPDATE => 'afterUpdate'];
	}

	/**
	 * @param AfterSaveEvent $event
	 */
	public function afterUpdate($event)
	{
		if (!(is_callable($this->enabled) ? call_user_func($this->enabled) : $this->enabled)) return;
		//вне полного приложения (частичные тестовые конфиги) оповещать некуда
		if (!Yii::$app->has('notifier')) return;

		/** @var ArmsModel $model */
		$model = $this->owner;

		foreach ($this->attributes as $attr) {
			if (!array_key_exists($attr, $event->changedAttributes)) continue;
			$old = $event->changedAttributes[$attr];
			$new = $model->getAttribute($attr);
			//dirty-сравнение Yii строгое ('5'!==5), фактическую смену проверяем нестрого
			if ((string)$old === (string)$new) continue;

			$users = $this->recipients
				? call_user_func($this->recipients, $model)
				: ($model instanceof NotifyRecipientsInterface ? $model->getNotifyRecipients() : []);
			if (!count($users)) continue;

			$oldText = $this->renderValueText($attr, $old);
			$newText = (string)$model->renderAttributeToText($attr);

			$subject = $this->subject
				? call_user_func($this->subject, $model, $attr, $oldText, $newText)
				: $this->defaultSubject($model, $attr, $newText);

			$body = $this->body
				? call_user_func($this->body, $model, $attr, $oldText, $newText)
				: $this->defaultBody($model, $attr, $oldText, $newText);

			$eventKey = $this->eventKey
				? call_user_func($this->eventKey, $model, $attr)
				: \app\helpers\StringHelper::class2Id(get_class($model)) . ':' . $model->id . ':' . $attr;

			Yii::$app->notifier->notifyUsers($users, $subject, $body, $eventKey);
		}
	}

	/**
	 * Текстовый вид старого значения атрибута: рендерим тем же
	 * renderAttributeToText, но на клоне модели со старым значением
	 * (ссылки-справочники разрешаются в имена штатным путём).
	 */
	protected function renderValueText(string $attr, $value): string
	{
		/** @var ArmsModel $clone */
		$clone = clone $this->owner;
		$clone->setAttribute($attr, $value);
		return (string)$clone->renderAttributeToText($attr);
	}

	protected function modelName(ArmsModel $model): string
	{
		return $model->hasAttribute('name') && strlen((string)$model->getAttribute('name'))
			? (string)$model->getAttribute('name')
			: '#' . $model->id;
	}

	protected function defaultSubject(ArmsModel $model, string $attr, string $newText): string
	{
		return $model::$title . ' «' . $this->modelName($model) . '»: '
			. $model->getAttributeLabel($attr) . ' — ' . ($newText === '' ? '(пусто)' : $newText);
	}

	protected function defaultBody(ArmsModel $model, string $attr, string $oldText, string $newText): string
	{
		$empty = '<i>(пусто)</i>';
		$name = Html::encode($this->modelName($model));
		$link = ($url = Notifier::modelUrl($model)) ? Html::a($name, $url) : $name;
		$oldHtml = $oldText === '' ? $empty : Html::encode($oldText);
		$newHtml = $newText === '' ? $empty : Html::encode($newText);
		return '<p>' . Html::encode($model::$title) . ' ' . $link . '</p>'
			. '<p><strong>' . Html::encode($model->getAttributeLabel($attr)) . ':</strong> '
			. $oldHtml . ' &rarr; ' . $newHtml . '</p>'
			. Notifier::modelLinksFooter($model);
	}
}
