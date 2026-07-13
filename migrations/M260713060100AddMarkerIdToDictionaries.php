<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Добавление ссылки marker_id (цветовой маркер, issue #141) в справочники,
 * которые раньше раскрашивались вручную через CSS-классы.
 */
class M260713060100AddMarkerIdToDictionaries extends ArmsMigration
{
	/** @var string[] справочники, получающие цветовой маркер */
	protected $tables = [
		'tech_states',
		'segments',
		'net_domains',
		'tech_types',
		'contracts_states',
	];

	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		foreach ($this->tables as $table) {
			$this->addColumnIfNotExists(
				$table,
				'marker_id',
				$this->integer()->null()->comment('Цветовой маркер'),
				true
			);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		foreach ($this->tables as $table) {
			$this->dropIndexIfExists("idx-$table-marker_id", $table);
			$this->dropColumnIfExists($table, 'marker_id');
		}
	}
}
