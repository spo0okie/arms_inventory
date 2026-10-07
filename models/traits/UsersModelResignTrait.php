<?php
/**
 * Увольнение сотрудника с учётом его откладывания.
 *
 * Uvolen - кадровый факт (приходит синхронизацией с кадровой системой и ею же
 * перезаписывается), а откладывание увольнения - решение на стороне инвентаризации:
 * человек по кадрам уже уволен, но для ИТ ещё работает (дорабатывает по договору,
 * передаёт дела и т.п.). Поэтому «уволен ли сотрудник» везде спрашивается через
 * resigned, а не через голый Uvolen.
 *
 * Вынесено отдельным трейтом (а не в UsersModelCalcFieldsTrait), потому что нужно
 * и журнальной записи UsersHistory: по resigned она рисуется зачёркнутой.
 * Трейт опирается только на собственные колонки записи.
 */

namespace app\models\traits;

/**
 * @property bool $resignDeferred Увольнение отложено (и срок откладывания не истёк)
 * @property bool $resigned Уволен с учётом откладывания увольнения
 */
trait UsersModelResignTrait
{
	/**
	 * Действует ли откладывание увольнения: оно включено и срок его не истёк.
	 * Пустой срок - откладывание бессрочное; указанная дата - последний день,
	 * когда сотрудник ещё не считается уволенным.
	 * От Uvolen не зависит: откладывание можно выставить заранее.
	 * @return bool
	 */
	public function getResignDeferred()
	{
		if (!$this->resign_defer) return false;
		if (!strlen((string)$this->resign_defer_until)) return true;
		return (string)$this->resign_defer_until >= date('Y-m-d');
	}

	/**
	 * Уволен ли сотрудник с точки зрения инвентаризации:
	 * кадровый признак Uvolen за вычетом действующего откладывания увольнения
	 * @return bool
	 */
	public function getResigned()
	{
		return $this->Uvolen && !$this->resignDeferred;
	}

	/**
	 * SQL-двойник {@see getResigned()}: выражение, дающее 1 для уволенных и 0 для остальных.
	 * Хранимой колонкой resigned быть не может - значение меняется само с наступлением даты.
	 * @param string $alias алиас таблицы сотрудников в запросе
	 * @return string
	 */
	public static function resignedExpression(string $alias='users'): string
	{
		return "(COALESCE($alias.Uvolen,0)=1 AND NOT ("
			."COALESCE($alias.resign_defer,0)=1 AND "
			."($alias.resign_defer_until IS NULL OR $alias.resign_defer_until>=CURDATE())"
			."))";
	}

	/**
	 * SQL-условие «сотрудник не уволен» (с учётом откладывания увольнения)
	 * @param string $alias алиас таблицы сотрудников в запросе
	 * @return string
	 */
	public static function notResignedCondition(string $alias='users'): string
	{
		return 'NOT '.static::resignedExpression($alias);
	}
}
