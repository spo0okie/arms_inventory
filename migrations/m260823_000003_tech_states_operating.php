<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Признак «В работе» у состояний оборудования: техника в этом состоянии
 * считается эксплуатируемой (стоит на месте установки и используется).
 *
 * Отличается от «Архивный»: архивность прячет запись из списков, а «В работе»
 * отделяет эксплуатируемую технику от той, что ещё/уже не в строю (согласована,
 * в снабжении, на складе, сломана) — при том, что все они остаются активными.
 *
 * Флаг добавляется выключенным, а типовым состояниям начальной поставки
 * (m230821_160259_init_empty_tables) проставляется по их служебным именам —
 * только при первом применении миграции, чтобы повторный прогон не затирал
 * настройку администратора.
 */
class m260823_000003_tech_states_operating extends ArmsMigration
{
	/** @var string[] служебные имена состояний, означающих эксплуатацию */
	protected $operatingCodes = [
		'state_operating',		//ОК - работает по месту установки
		'state_malfunction',	//Замеч. - работает, но с замечаниями
	];

	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$existed = isset($this->db->getTableSchema('tech_states', true)->columns['operating']);

		$this->addColumnIfNotExists(
			'tech_states',
			'operating',
			$this->boolean()->defaultValue(false)->comment('Оборудование в работе'),
			true
		);

		if (!$existed) $this->update('tech_states', ['operating' => 1], ['code' => $this->operatingCodes]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropIndexIfExists('idx-tech_states-operating', 'tech_states');
		$this->dropColumnIfExists('tech_states', 'operating');
	}
}
