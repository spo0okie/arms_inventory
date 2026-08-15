<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Чистка осиротевших строк junction-таблиц many-to-many связей.
 *
 * LinkerBehavior обслуживает junction-таблицы только на insert/update, FK на
 * junction-таблицах в проекте нет (M251221163631ClearFk), а модели до фикса
 * ArmsModel::afterDelete -> deleteJunctionRows() при delete() junction-строки
 * не чистили — за время работы накопились строки, ссылающиеся на удалённые
 * записи (осмотр копии боевой БД: ~82 строки, в основном в *_in_aces).
 *
 * Сознательно НЕ трогаем:
 *  - soft_hits (журнал обнаружений софта — таблица с данными, не чистый junction);
 *  - полиморфную сторону tags_links (чистится только tag_id -> tags).
 */
class M260815120000CleanJunctionOrphans extends ArmsMigration
{
	/**
	 * junction-таблица => [колонка => родительская таблица]
	 */
	const JUNCTIONS = [
		'access_in_aces' => ['aces_id' => 'aces', 'access_types_id' => 'access_types'],
		'access_types_hierarchy' => ['parent_id' => 'access_types', 'child_id' => 'access_types'],
		'admins_in_comps' => ['comps_id' => 'comps', 'users_id' => 'users'],
		'comps_in_aces' => ['aces_id' => 'aces', 'comps_id' => 'comps'],
		'comps_in_services' => ['comps_id' => 'comps', 'services_id' => 'services'],
		'contracts_in_lics' => ['contracts_id' => 'contracts', 'lics_id' => 'lic_items'],
		'contracts_in_materials' => ['contracts_id' => 'contracts', 'materials_id' => 'materials'],
		'contracts_in_services' => ['contracts_id' => 'contracts', 'services_id' => 'services'],
		'contracts_in_techs' => ['contracts_id' => 'contracts', 'techs_id' => 'techs'],
		'default_access_in_services' => ['access_types_id' => 'access_types', 'services_id' => 'services'],
		'ips_in_aces' => ['aces_id' => 'aces', 'ips_id' => 'net_ips'],
		'ips_in_comps' => ['comps_id' => 'comps', 'ips_id' => 'net_ips'],
		'ips_in_techs' => ['techs_id' => 'techs', 'ips_id' => 'net_ips'],
		'ips_in_users' => ['users_id' => 'users', 'ips_id' => 'net_ips'],
		'lic_groups_in_arms' => ['lic_groups_id' => 'lic_groups', 'arms_id' => 'techs'],
		'lic_groups_in_comps' => ['lic_groups_id' => 'lic_groups', 'comps_id' => 'comps'],
		'lic_groups_in_users' => ['lic_groups_id' => 'lic_groups', 'users_id' => 'users'],
		'lic_items_in_arms' => ['lic_items_id' => 'lic_items', 'arms_id' => 'techs'],
		'lic_items_in_comps' => ['lic_items_id' => 'lic_items', 'comps_id' => 'comps'],
		'lic_items_in_users' => ['lic_items_id' => 'lic_items', 'users_id' => 'users'],
		'lic_keys_in_arms' => ['lic_keys_id' => 'lic_keys', 'arms_id' => 'techs'],
		'lic_keys_in_comps' => ['lic_keys_id' => 'lic_keys', 'comps_id' => 'comps'],
		'lic_keys_in_users' => ['lic_keys_id' => 'lic_keys', 'users_id' => 'users'],
		'maintenance_jobs_in_comps' => ['jobs_id' => 'maintenance_jobs', 'comps_id' => 'comps'],
		'maintenance_jobs_in_services' => ['jobs_id' => 'maintenance_jobs', 'services_id' => 'services'],
		'maintenance_jobs_in_techs' => ['jobs_id' => 'maintenance_jobs', 'techs_id' => 'techs'],
		'maintenance_reqs_in_comps' => ['reqs_id' => 'maintenance_reqs', 'comps_id' => 'comps'],
		'maintenance_reqs_in_jobs' => ['reqs_id' => 'maintenance_reqs', 'jobs_id' => 'maintenance_jobs'],
		'maintenance_reqs_in_reqs' => ['reqs_id' => 'maintenance_reqs', 'includes_id' => 'maintenance_reqs'],
		'maintenance_reqs_in_services' => ['reqs_id' => 'maintenance_reqs', 'services_id' => 'services'],
		'maintenance_reqs_in_techs' => ['reqs_id' => 'maintenance_reqs', 'techs_id' => 'techs'],
		'networks_in_aces' => ['aces_id' => 'aces', 'networks_id' => 'networks'],
		'org_inets_in_networks' => ['org_inets_id' => 'org_inet', 'networks_id' => 'networks'],
		'partners_in_contracts' => ['partners_id' => 'partners', 'contracts_id' => 'contracts'],
		'services_depends' => ['service_id' => 'services', 'depends_id' => 'services'],
		'services_in_aces' => ['aces_id' => 'aces', 'services_id' => 'services'],
		'soft_in_comps' => ['comp_id' => 'comps', 'soft_id' => 'soft'],
		'soft_in_lics' => ['lics_id' => 'lic_groups', 'soft_id' => 'soft'],
		'soft_in_lists' => ['soft_id' => 'soft', 'list_id' => 'soft_lists'],
		'tags_links' => ['tag_id' => 'tags'],
		'techs_in_services' => ['service_id' => 'services', 'tech_id' => 'techs'],
		'users_in_aces' => ['aces_id' => 'aces', 'users_id' => 'users'],
		'users_in_contracts' => ['contracts_id' => 'contracts', 'users_id' => 'users'],
		'users_in_services' => ['service_id' => 'services', 'user_id' => 'users'],
		'users_in_svc_infrastructure' => ['services_id' => 'services', 'users_id' => 'users'],
	];

	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$total = 0;
		foreach (static::JUNCTIONS as $junction => $columns) {
			if (!$this->tableExists($junction)) continue;
			foreach ($columns as $column => $parent) {
				if (!$this->tableExists($parent)) continue;
				$affected = $this->db->createCommand(
					"DELETE j FROM {{%$junction}} j"
					. " LEFT JOIN {{%$parent}} p ON p.[[id]] = j.[[$column]]"
					. " WHERE p.[[id]] IS NULL"
				)->execute();
				if ($affected) {
					echo "    $junction.$column -> $parent: удалено $affected сирот\n";
					$total += $affected;
				}
			}
		}
		echo "    итого удалено осиротевших junction-строк: $total\n";
	}

	/**
	 * {@inheritdoc}
	 * Удаление мусорных строк необратимо (и не нужно откатывать)
	 */
	public function safeDown()
	{
		return true;
	}
}
