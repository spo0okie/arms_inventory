<?php

namespace app\modules\api\controllers;

/**
 * REST-контроллер отсутствий (/api/absences). Потребитель — SAPsync
 * (приём SAP/1C → ARMS и выгрузка ARMS → Bitrix), см. plans/ARMS-absences-spec.md.
 *
 * CRUD-действия (index/view/create/update/delete/search/filter) предоставляет
 * BaseRestController автоматически. Здесь задаётся только набор полей поиска.
 */
class AbsencesController extends BaseRestController
{
	public $modelClass = 'app\models\Absences';

	/**
	 * Поля поиска (search/filter) с маппингом в атрибуты модели:
	 *  - source + external_id — проверка наличия записи перед upsert;
	 *  - source + user_id     — все записи источника по сотруднику (обратный проход:
	 *                           удаление исчезнувших из источника, не трогая manual/другие);
	 *  - user_id              — записи по конкретному трудоустройству;
	 *  - type                 — по нормализованному типу отсутствия.
	 * Организация у отсутствия не хранится — фильтрация по юрлицу делается на стороне
	 * потребителя через сотрудников (GET /api/users?org_id=… → выборка по user_id).
	 * Диапазонная фильтрация по датам в базовом searchFilter не поддерживается.
	 * @var array
	 */
	public static array $searchFields = [
		'id',
		'user_id' => 'user_id',
		'type' => 'type',
		'source' => 'source',
		'external_id' => 'external_id',
	];
}
