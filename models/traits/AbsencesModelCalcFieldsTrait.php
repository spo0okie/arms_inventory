<?php
/**
 * Вычисляемые поля отсутствий.
 *
 * Трейт содержит ТОЛЬКО calc-поля (без атрибутов таблицы), чтобы его можно было
 * подключить и к паре Absences/AbsencesHistory: у отсутствия нет колонки name,
 * поэтому человекочитаемая подпись собирается здесь и работает как в мастере,
 * так и в записи журнала.
 */

namespace app\models\traits;

use app\models\Absences;

/**
 * @package app\models\traits
 *
 * @property string $type
 * @property string $date_from
 * @property string $date_to
 * @property int    $user_id
 */
trait AbsencesModelCalcFieldsTrait
{
	/**
	 * Человекочитаемая подпись отсутствия: «Иванов И.И.: отпуск ежегодный (01.01.2026 – 10.01.2026)».
	 * У таблицы нет поля name — подпись собирается из типа, сотрудника и периода.
	 * Обращение к связи user обёрнуто в try/catch: на записи журнала (AbsencesHistory)
	 * связь-геттер не объявлен, тогда просто опускаем имя сотрудника.
	 * @return string
	 */
	public function getName()
	{
		$type = Absences::$types[$this->type] ?? ($this->type ?: 'отсутствие');
		$who = $this->getUserSname();
		$period = $this->getPeriodLabel();
		return trim(($who !== '' ? $who . ': ' : '') . $type . ($period !== '' ? ' (' . $period . ')' : ''));
	}

	/**
	 * Имя сотрудника через связь user, если она доступна (мастер-модель), иначе ''.
	 * @return string
	 */
	protected function getUserSname(): string
	{
		try {
			$user = $this->user;
			return is_object($user) ? (string)$user->sname : '';
		} catch (\Throwable $e) {
			return '';
		}
	}

	/**
	 * Период отсутствия в формате «дд.мм.гггг – дд.мм.гггг».
	 * @return string
	 */
	protected function getPeriodLabel(): string
	{
		$fmt = static fn($d) => $d ? date('d.m.Y', strtotime($d)) : '';
		$from = $fmt($this->date_from);
		$to = $fmt($this->date_to);
		if ($from !== '' && $to !== '') return $from . ' – ' . $to;
		return $from !== '' ? $from : $to;
	}
}
