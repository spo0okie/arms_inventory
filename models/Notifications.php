<?php

namespace app\models;

use app\helpers\ArrayHelper;
use app\models\base\ArmsModel;
use yii\db\ActiveQuery;
use yii\db\Expression;

/**
 * Outbox механизма оповещений (plans/notifications.md, issue #184).
 *
 * Жизненный цикл записи: enqueue() → notify/send (sent_at) → notify/cleanup.
 * Отправленные записи хранятся до чистки — по ним wasSent() определяет,
 * не рано ли слать повторное напоминание (repeat в notify/watch).
 *
 * CRUD-страницы (NotificationsController) — админский контроль очереди:
 * посмотреть/поправить застрявшее письмо (сбросить attempts после починки
 * SMTP), удалить неактуальное. History-пары нет намеренно: это очередь,
 * а не учётная сущность.
 *
 * @property int $id
 * @property int $user_id Получатель
 * @property string|null $event_key Ключ дедупликации/повторов
 * @property string $subject Тема письма
 * @property string|null $body Готовый HTML письма
 * @property string $created_at Поставлено в очередь
 * @property string|null $sent_at Отправлено (NULL = в очереди)
 * @property int $attempts Число неудачных попыток отправки
 * @property string|null $last_error Последняя ошибка отправки
 *
 * @property-read Users $user
 */
class Notifications extends ArmsModel
{
	public static $title = 'Уведомление';
	public static $titles = 'Уведомления';

	public static function modelDescription(): string
	{
		return 'Очередь e-mail-оповещений (outbox): письма ставятся сюда событиями '
			. '(смена статуса документа) и сторожевыми правилами (notify/watch), '
			. 'фактическую отправку делает консольная команда notify/send. '
			. 'Отправленные записи хранятся до notify/cleanup — по ним определяется, '
			. 'когда можно слать повторное напоминание.';
	}

	/** {@inheritdoc} */
	public static function tableName()
	{
		return 'notifications';
	}

	public $linksSchema = [
		'user_id' => Users::class,
	];

	/** {@inheritdoc} */
	public function rules()
	{
		return [
			[['user_id', 'subject'], 'required'],
			[['user_id', 'attempts'], 'integer'],
			[['attempts'], 'default', 'value' => 0],
			[['event_key', 'body', 'last_error'], 'default', 'value' => null],
			[['body'], 'string'],
			[['event_key'], 'string', 'max' => 128],
			[['subject', 'last_error'], 'string', 'max' => 255],
			[['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['user_id' => 'id']],
		];
	}

	/** {@inheritdoc} */
	public function attributeData()
	{
		return ArrayHelper::recursiveOverride(parent::attributeData(), [
			'user_id' => [
				'Получатель',
				'hint' => 'Сотрудник, которому адресовано письмо (адрес берётся из его поля E-Mail на момент отправки)',
				'placeholder' => 'Получатель',
				'join' => ['user'],
				'typeClass' => \app\types\LinkType::class,
			],
			'event_key' => [
				'Ключ события',
				'hint' => 'Ключ дедупликации: пока письмо не отправлено, повторное событие с тем же ключом '
					. 'обновляет его, а не создаёт новое. По отправленным записям с этим ключом '
					. 'notify/watch отсчитывает интервал повторного напоминания (repeat)',
				'placeholder' => 'без дедупликации',
				'typeClass' => \app\types\StringType::class,
			],
			'subject' => [
				'Тема',
				'hint' => 'Тема письма',
				'typeClass' => \app\types\StringType::class,
			],
			'body' => [
				'Письмо',
				'hint' => 'Готовый HTML-текст письма (формируется при постановке в очередь)',
				'typeClass' => \app\types\TextType::class,
			],
			'created_at' => [
				'Поставлено в очередь',
				'hint' => 'Когда письмо попало в очередь (или было обновлено повторным событием)',
				'readOnly' => true,
				'typeClass' => \app\types\DatetimeType::class,
			],
			'sent_at' => [
				'Отправлено',
				'hint' => 'Время фактической отправки; пусто — письмо ещё в очереди',
				'readOnly' => true,
				'typeClass' => \app\types\DatetimeType::class,
			],
			'attempts' => [
				'Попытки',
				'hint' => 'Число неудачных попыток отправки. Исчерпавшее лимит письмо остаётся в таблице '
					. 'для разбора; чтобы отправить его повторно (например, после починки SMTP) — обнулите счётчик',
				'typeClass' => \app\types\IntegerType::class,
			],
			'last_error' => [
				'Последняя ошибка',
				'hint' => 'Текст последней ошибки отправки (SMTP и т.п.)',
				'readOnly' => true,
				'typeClass' => \app\types\StringType::class,
			],
		]);
	}

	/**
	 * Имени-колонки у очереди нет — подписью служит тема письма
	 * @return string
	 */
	public function getName()
	{
		return strlen((string)$this->subject) ? $this->subject : '#' . $this->id;
	}

	/**
	 * Получатель письма
	 * @return ActiveQuery
	 */
	public function getUser(): ActiveQuery
	{
		return $this->hasOne(Users::class, ['id' => 'user_id']);
	}

	/**
	 * Ставит уведомление в очередь отправки.
	 *
	 * Дедупликация: при непустом $eventKey неотправленная запись с тем же
	 * (user_id, event_key) не дублируется, а обновляется свежим содержимым —
	 * несколько событий до отправки схлопываются в одно письмо с последним
	 * состоянием. Счётчик попыток при этом сбрасывается.
	 *
	 * Пишет в той же БД-транзакции, что и вызывающий код: откат транзакции
	 * отменяет и уведомление.
	 */
	public static function enqueue(int $userId, string $subject, string $body, ?string $eventKey = null): self
	{
		$notification = null;
		if ($eventKey !== null) {
			$notification = static::find()
				->where(['user_id' => $userId, 'event_key' => $eventKey, 'sent_at' => null])
				->one();
		}
		if (!$notification) {
			$notification = new static(['user_id' => $userId, 'event_key' => $eventKey]);
		}
		$notification->subject = $subject;
		$notification->body = $body;
		$notification->created_at = new Expression('CURRENT_TIMESTAMP');
		$notification->attempts = 0;
		$notification->last_error = null;
		$notification->save();
		return $notification;
	}

	/**
	 * Отправлялось ли такое событие этому пользователю: при $withinSeconds —
	 * за последние N секунд, при null — вообще когда-либо.
	 * Сравнение времени целиком на стороне БД (NOW() и sent_at в одних часах).
	 */
	public static function wasSent(int $userId, string $eventKey, ?int $withinSeconds = null): bool
	{
		$query = static::find()->where(['user_id' => $userId, 'event_key' => $eventKey]);
		if ($withinSeconds === null)
			$query->andWhere(['not', ['sent_at' => null]]);
		else
			$query->andWhere(['>=', 'sent_at', new Expression('DATE_SUB(NOW(), INTERVAL :sec SECOND)', [':sec' => $withinSeconds])]);
		return $query->exists();
	}

	/**
	 * Очередь на отправку (для notify/send): неотправленные, не исчерпавшие попытки.
	 * @return ActiveQuery
	 */
	public static function findPending(int $maxAttempts): ActiveQuery
	{
		return static::find()
			->where(['sent_at' => null])
			->andWhere(['<', 'attempts', $maxAttempts])
			->orderBy(['id' => SORT_ASC]);
	}
}
