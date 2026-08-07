<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Поле external_links (ExternalDataModelTrait) в требованиях и работах
 * регламентного обслуживания.
 *
 * Мотивация: сделать инвентаризацию машиночитаемым источником истины для
 * скриптов резервного копирования. Требование несёт декларативную часть
 * (класс РК, GFS-схема), работа — исполнительную (механизм, источник);
 * из этих данных консольная команда backup/* генерирует конфигурации
 * для скриптов прореживания и переноса на ленту.
 *
 * Схема самих JSON-данных продуктом не навязывается — это соглашение
 * организации (см. docs/help/models/maintenance-reqs.md).
 */
class M260804120000AddExternalLinksMaintenance extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->addColumnIfNotExists('maintenance_reqs', 'external_links', $this->text()->null());
		$this->addColumnIfNotExists('maintenance_jobs', 'external_links', $this->text()->null());
	}

	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropColumnIfExists('maintenance_reqs', 'external_links');
		$this->dropColumnIfExists('maintenance_jobs', 'external_links');
	}
}
