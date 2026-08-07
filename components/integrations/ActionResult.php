<?php

namespace app\components\integrations;

/**
 * Результат выполнения действия интеграции
 * (plans/integrations-contract.md §2).
 *
 * Возвращается из {@see IntegrationProvider::runAction()}. Всё что попадает
 * в message/logParams пишется в журнал integrations_log — секретов
 * (пароли, тексты содержащие пароли) там быть не должно; фильтрация —
 * обязанность провайдера.
 */
class ActionResult
{
	/** @var bool успешно ли выполнено действие */
	public bool $ok = false;

	/** @var string итог для пользователя и журнала (без секретов) */
	public string $message = '';

	/**
	 * @var string опциональный расширенный HTML для вывода в модалку;
	 * если пуст — ядро отобразит message стандартной плашкой
	 */
	public string $html = '';

	/** @var array параметры действия для журнала (JSON), без секретов */
	public array $logParams = [];

	/**
	 * @var int|null id записи журнала; заполняется реестром после записи —
	 * используется как parent_id вложенных вызовов (композиция §2.2)
	 */
	public ?int $logId = null;

	public static function success(string $message = 'OK', array $logParams = [], string $html = ''): self
	{
		$result = new static();
		$result->ok = true;
		$result->message = $message;
		$result->logParams = $logParams;
		$result->html = $html;
		return $result;
	}

	public static function error(string $message, array $logParams = []): self
	{
		$result = new static();
		$result->ok = false;
		$result->message = $message;
		$result->logParams = $logParams;
		return $result;
	}
}
