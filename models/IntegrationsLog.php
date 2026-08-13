<?php

namespace app\models;

use app\components\integrations\ActionResult;
use app\models\base\ArmsModel;
use Yii;

/**
 * Журнал действий интеграций с внешними ИС
 * (docs/dev/integrations.md).
 *
 * Пишется реестром интеграций ({@see \app\components\integrations\IntegrationsRegistry::runActionForm()})
 * на каждое выполненное действие. Секреты сюда попадать не должны:
 * состав params определяет провайдер через ActionResult::$logParams.
 *
 * @property int $id
 * @property string $created_at Когда выполнено
 * @property int|null $users_id Инициатор (пользователь ARMS), NULL для консоли
 * @property string $provider Id провайдера интеграции
 * @property string $action Id действия
 * @property string|null $class Класс объекта ARMS (NULL для standalone)
 * @property int|null $object_id Id объекта ARMS (NULL для standalone)
 * @property int|null $parent_id Запись-инициатор для вложенных вызовов
 * @property string|null $ext_login Исполнитель во внешней ИС (L2+)
 * @property string|null $params Параметры действия (JSON, без секретов)
 * @property string $result ok / error
 * @property string|null $message Итог/ответ внешней ИС
 * @property Users|null $user
 */
class IntegrationsLog extends ArmsModel
{
	public static $title='Действие интеграции';
	public static $titles='Журнал интеграций';

	public static function modelDescription(): string
	{
		return 'Журнал действий, выполненных во внешних информационных системах через механизм интеграций '
			.'(отправка SMS, сброс пароля и т.п.): кто, когда, что и с каким результатом. '
			.'Секреты (пароли, тексты с паролями) в журнал не попадают.';
	}

	public static function tableName()
	{
		return 'integrations_log';
	}

	public $linksSchema = [
		'users_id' => Users::class,
		'parent_id' => IntegrationsLog::class,
	];

	public function rules()
	{
		return [
			[['provider', 'action', 'result'], 'required'],
			[['users_id', 'object_id', 'parent_id'], 'integer'],
			[['provider', 'action'], 'string', 'max' => 64],
			[['class', 'ext_login'], 'string', 'max' => 128],
			[['params'], 'string', 'max' => 1024],
			[['result'], 'string', 'max' => 8],
			[['message'], 'string', 'max' => 255],
			[['users_id'], 'exist', 'skipOnError' => true, 'targetClass' => Users::class, 'targetAttribute' => ['users_id' => 'id']],
			[['parent_id'], 'exist', 'skipOnError' => true, 'targetClass' => IntegrationsLog::class, 'targetAttribute' => ['parent_id' => 'id']],
		];
	}

	public function attributeData()
	{
		return [
			'created_at' => [
				'Когда',
				'hint' => 'Время выполнения действия',
			],
			'users_id' => [
				'Инициатор',
				'hint' => 'Пользователь инвентаризации, запустивший действие (пусто для консольных запусков)',
			],
			'provider' => [
				'Интеграция',
				'hint' => 'Id провайдера интеграции (ключ в конфиге integrations)',
			],
			'action' => [
				'Действие',
				'hint' => 'Id действия провайдера (например send у SMS)',
			],
			'class' => [
				'Класс объекта',
				'hint' => 'Класс объекта инвентаризации, над которым выполнено действие (пусто для standalone-действий)',
			],
			'object_id' => [
				'Объект',
				'hint' => 'Id объекта инвентаризации, над которым выполнено действие (пусто для standalone-действий)',
			],
			'parent_id' => [
				'Вызвано из',
				'hint' => 'Запись-инициатор: заполнено у шагов составных действий, когда одна интеграция вызывает другую',
			],
			'ext_login' => [
				'Внешняя учетка',
				'hint' => 'Логин во внешней ИС, от имени которого выполнено именное действие (пароль не сохраняется)',
			],
			'params' => [
				'Параметры',
				'hint' => 'Параметры действия в JSON; секреты сюда не пишутся',
			],
			'result' => [
				'Результат',
				'hint' => 'run - выполняется (или прервано на середине), ok - выполнено, error - не выполнено',
			],
			'message' => [
				'Сообщение',
				'hint' => 'Итог действия / ответ внешней ИС',
			],
		];
	}

	/**
	 * У журнала нет колонки name — подписью служит «интеграция/действие #id».
	 * Нужно для заголовка страницы просмотра (layouts/view) и renderItem.
	 */
	public function getName()
	{
		return $this->providerTitle().' / '.$this->action.' #'.$this->id;
	}

	public function getUser()
	{
		return $this->hasOne(Users::class, ['id' => 'users_id']);
	}

	/** Запись-инициатор (для шага составного действия) */
	public function getParent()
	{
		return $this->hasOne(IntegrationsLog::class, ['id' => 'parent_id']);
	}

	/** Вложенные шаги, вызванные этим действием (композиция) */
	public function getChildren()
	{
		return $this->hasMany(IntegrationsLog::class, ['parent_id' => 'id'])->orderBy(['id' => SORT_ASC]);
	}

	/** Человекочитаемое название провайдера из реестра (или его id, если выключен) */
	public function providerTitle(): string
	{
		$provider = \app\components\integrations\IntegrationsRegistry::provider($this->provider);
		return $provider ? $provider->getTitle() : (string)$this->provider;
	}

	/**
	 * Маршрут на объект действия (class + object_id) для ссылки в журнале,
	 * либо null (standalone-действие или неизвестный класс)
	 */
	public function objectRoute(): ?array
	{
		if (empty($this->class) || empty($this->object_id) || !class_exists($this->class)) return null;
		return ['/'.\app\helpers\StringHelper::class2Id($this->class).'/view', 'id' => $this->object_id];
	}

	/**
	 * Открывает запись журнала ДО выполнения действия (result='run').
	 * Так составные действия (§2.2 контракта) получают id записи-инициатора
	 * для parent_id вложенных вызовов ещё во время выполнения; а действие,
	 * убившее процесс, остаётся в журнале со статусом run.
	 * Ошибка записи журнала не должна ронять само действие: пишем в лог
	 * приложения и возвращаем null.
	 *
	 * @param string $provider id провайдера
	 * @param string $action id действия
	 * @param ArmsModel|null $model объект действия (null для standalone)
	 * @param string|null $extLogin исполнитель во внешней ИС (L2+), без пароля
	 * @param int|null $parentId запись-инициатор (вложенные вызовы)
	 * @return int|null id созданной записи
	 */
	public static function open(string $provider, string $action, ?ArmsModel $model,
		?string $extLogin = null, ?int $parentId = null): ?int
	{
		try {
			$log = new static();
			$log->users_id = (Yii::$app->has('user') && !Yii::$app->user->isGuest) ?
				Yii::$app->user->id : null;
			$log->provider = $provider;
			$log->action = $action;
			$log->class = is_object($model) ? get_class($model) : null;
			$log->object_id = is_object($model) ? $model->id : null;
			$log->parent_id = $parentId;
			$log->ext_login = $extLogin;
			$log->result = 'run';
			$log->save(false);
			return $log->id;
		} catch (\Throwable $e) {
			Yii::error("IntegrationsLog::open($provider/$action) failed: ".$e->getMessage(), __METHOD__);
			return null;
		}
	}

	/**
	 * Закрывает запись журнала итогом выполнения (пара к {@see open()})
	 */
	public static function close(?int $id, ActionResult $result): void
	{
		if (is_null($id)) return;
		try {
			$log = static::findOne($id);
			if (!$log) return;
			$log->params = count($result->logParams) ?
				mb_substr(json_encode($result->logParams, JSON_UNESCAPED_UNICODE), 0, 1024) : null;
			$log->result = $result->ok ? 'ok' : 'error';
			$log->message = mb_substr($result->message, 0, 255);
			$log->save(false);
		} catch (\Throwable $e) {
			Yii::error("IntegrationsLog::close($id) failed: ".$e->getMessage(), __METHOD__);
		}
	}

	/**
	 * Открыть и сразу закрыть запись одним итогом (короткий путь для
	 * случаев, когда действие не выполнялось: например, не прошла
	 * валидация параметров)
	 * @return int|null id записи
	 */
	public static function write(string $provider, string $action, ?ArmsModel $model,
		ActionResult $result, ?string $extLogin = null, ?int $parentId = null): ?int
	{
		$id = static::open($provider, $action, $model, $extLogin, $parentId);
		static::close($id, $result);
		return $id;
	}
}
