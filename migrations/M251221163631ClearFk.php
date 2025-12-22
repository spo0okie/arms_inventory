<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;
use yii\db\Migration;

class M251221163631ClearFk extends ArmsMigration
{
    /**
     * {@inheritdoc}
     */
    public function up()
    {
		foreach ([
			'fk-aces-acl' => 'aces',
			'fk-acls-comp' => 'acls',
			'fk-acls-ip' => 'acls',
			'fk-acls-schedule' => 'acls',
			'fk-acls-service' => 'acls',
			'fk-acls-tech' => 'acls',
			'fk-admins_in_comps-comps_id' => 'admins_in_comps',
			'fk-admins_in_comps-users_id' => 'admins_in_comps',
			'auth_assignment_ibfk_1' => 'auth_assignment',
			'auth_item_ibfk_1' => 'auth_item',
			'auth_item_child_ibfk_1' => 'auth_item_child',
			'auth_item_child_ibfk_2' => 'auth_item_child',
			'fk-comps_user' => 'comps',
			'contracts_ibfk_1' => 'contracts',
			'fk-contracts-state' => 'contracts',
			'lic_groups' => 'lic_items',
			'fk-lic_keys_lic_items' => 'lic_keys',
			'fk-maintenance_jobs_in_comps-comps_id' => 'maintenance_jobs_in_comps',
			'fk-maintenance_jobs_in_comps-jobs_id' => 'maintenance_jobs_in_comps',
			'fk-maintenance_jobs_in_services-jobs_id' => 'maintenance_jobs_in_services',
			'fk-maintenance_jobs_in_services-services_id' => 'maintenance_jobs_in_services',
			'fk-maintenance_jobs_in_techs-jobs_id' => 'maintenance_jobs_in_techs',
			'fk-maintenance_jobs_in_techs-techs_id' => 'maintenance_jobs_in_techs',
			'fk-maintenance_reqs_in_comps-comps_id' => 'maintenance_reqs_in_comps',
			'fk-maintenance_reqs_in_comps-reqs_id' => 'maintenance_reqs_in_comps',
			'fk-maintenance_reqs_in_jobs-jobs_id' => 'maintenance_reqs_in_jobs',
			'fk-maintenance_reqs_in_jobs-reqs_id' => 'maintenance_reqs_in_jobs',
			'fk-maintenance_reqs_in_reqs-includes_id' => 'maintenance_reqs_in_reqs',
			'fk-maintenance_reqs_in_reqs-reqs_id' => 'maintenance_reqs_in_reqs',
			'fk-maintenance_reqs_in_services-reqs_id' => 'maintenance_reqs_in_services',
			'fk-maintenance_reqs_in_services-services_id' => 'maintenance_reqs_in_services',
			'fk-maintenance_reqs_in_techs-reqs_id' => 'maintenance_reqs_in_techs',
			'fk-maintenance_reqs_in_techs-techs_id' => 'maintenance_reqs_in_techs',
			'fk-net_ips-networks_id' => 'net_ips',
			'fk-networks-segments_id' => 'networks',
			'fk-networks_in_aces-aces_id' => 'networks_in_aces',
			'fk-networks_in_aces-networks_id' => 'networks_in_aces',
			'org_inet_ibfk_1' => 'org_inet',
			'fk-ports_link_port' => 'ports',
			'scans_ibfk_1' => 'scans',
			'fk-services-providing_schedule' => 'services',
			'fk-services-responsible' => 'services',
			'fk-services-segment' => 'services',
			'fk-services-support_schedule' => 'services',
			'fk-services_in_aces-aces_id' => 'services_in_aces',
			'fk-services_in_aces-services_id' => 'services_in_aces',
			'comp_id_restr' => 'soft_hits',
			'soft_id_restr' => 'soft_hits',
			'soft_in_lics_ibfk_1' => 'soft_in_lics',
			'soft_in_lics_ibfk_2' => 'soft_in_lics',
		] as $fk=>$table) {
			$this->dropFkIfExists($fk, $table);
		};


    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        return true;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "M251221163631ClearFk cannot be reverted.\n";

        return false;
    }
    */
}
