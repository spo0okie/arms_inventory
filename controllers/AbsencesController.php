<?php

namespace app\controllers;

use app\models\Absences;

/**
 * AbsencesController реализует CRUD операции для модели Absences (ручной ввод отсутствий).
 */
class AbsencesController extends ArmsBaseController
{
	/**
	 * @var string Класс модели для CRUD операций
	 */
	public $modelClass = Absences::class;

	/**
	 * item-by-name отключён: у отсутствий нет колонки name (подпись вычисляемая),
	 * а findByName() ищет строго по столбцу name — на этой модели это SQL-ошибка.
	 * @return array
	 */
	public function disabledActions()
	{
		return ['item-by-name'];
	}
}
