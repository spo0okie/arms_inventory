<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;
use app\migrations\m191103_203015_add_procedures_for_places as PlacesProcs;

/**
 * Нормализация коллации хранимой функции getplacepath к utf8mb4_unicode_ci.
 *
 * Зачем: M260702104735NormalizeCollation привёл к канону таблицы/столбцы, но
 * ALTER TABLE ... CONVERT TO не трогает тело/RETURNS хранимых процедур и функций.
 * getplacepath была создана как `RETURNS TEXT CHARACTER SET utf8mb4` без явного
 * COLLATE, поэтому её коллация возврата зафиксировалась дефолтом сервера на момент
 * создания (на старых инсталляциях — utf8mb4_general_ci). Функция участвует в
 * LIKE-поиске (CompsSearch/TechsSearch: `getplacepath(places.id) LIKE '%...%'`),
 * и сравнение general_ci-возврата с литералом (коллация соединения — unicode_ci,
 * см. config/db.php) снова даёт 1267 "Illegal mix of collations".
 *
 * Пересоздаём процедуру и функцию из исправленного исходника (m191103), где
 * теперь явно проставлен COLLATE utf8mb4_unicode_ci. getplacetop / getServiceSegment
 * возвращают INT — коллации не имеют, их не трогаем.
 *
 * @see \app\migrations\m191103_203015_add_procedures_for_places
 * @see tests\unit\db\CollationConsistencyTest::testAllRoutineParamsUseCanonicalCollation
 */
class M260702165648NormalizeRoutinesCollation extends ArmsMigration
{
	public function up()
	{
		$this->execute('DROP PROCEDURE IF EXISTS getplacepath');
		$this->execute('DROP FUNCTION IF EXISTS getplacepath');
		$this->execute(PlacesProcs::$getPlacePathProc);
		$this->execute(PlacesProcs::$getPlacePathFunc);
	}

	public function down()
	{
		echo static::class . ": откат не поддерживается.\n";
		return false;
	}
}
