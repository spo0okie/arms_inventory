<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Флаг access_types.is_forward — тип доступа описывает проброс/транзит
 * (docs/dev/access-chains.md, §3).
 *
 * NAT-проброс документируется тем же механизмом, что и остальные связи, — ACE:
 * субъект — адрес входа (белый IP), ресурс ACL — узел назначения или его серый IP,
 * сетевые параметры вида «TCP 443->8443». Флаг в ряду is_app/is_ip/is_phone/is_vpn
 * (а не зарезервированный код) позволяет завести сколько нужно форвард-типов
 * (DNAT, L7-прокси, ssh-туннель) — все они попадают в выборки пробросов.
 */
class M260919100000AccessTypesIsForward extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		$this->addColumnIfNotExists('access_types', 'is_forward', $this->boolean()->defaultValue(false));
	}

	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropColumnIfExists('access_types', 'is_forward');
	}
}
