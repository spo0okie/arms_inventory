<?php

namespace app\models;

use app\models\traits\AbsencesModelCalcFieldsTrait;

/**
 * Журнал изменений отсутствий (зеркало таблицы absences_history).
 *
 * @property int $id
 * @property int|null $master_id
 * @property int|null $user_id
 * @property string|null $type
 * @property string|null $date_from
 * @property string|null $date_to
 * @property string|null $comment
 * @property string|null $source
 * @property string|null $external_id
 * @property string|null $updated_at
 * @property string|null $updated_by
 * @property string|null $updated_comment
 * @property string $name
 */
class AbsencesHistory extends HistoryModel
{
	use AbsencesModelCalcFieldsTrait;

	public static $title = 'Изменение отсутствия';
	public static $titles = 'Изменения отсутствий';

	public $masterClass = Absences::class;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'absences_history';
	}
}
