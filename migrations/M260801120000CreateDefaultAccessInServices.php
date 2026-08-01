<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Дефолтные типы доступа у сервиса (issue #204, plans/access-defaults-and-copy.md).
 *
 * M2M-связка services ↔ access_types: какие типы доступа обычно предоставляются
 * при выдаче доступа к сервису (RDP для терминалов, HTTPS для портала и т.п.).
 * Используется для предзаполнения галочек типов доступа в формах ACE/ACL
 * и как документация «как ходят в этот сервис».
 *
 * ip_params — переопределение сетевых параметров типа доступа для этого сервиса
 * (например HTTPS на нестандартном порту: «TCP 8140» у PUPPET) — по образцу
 * такой же колонки в access_in_aces.
 */
class M260801120000CreateDefaultAccessInServices extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		//числовые ключи = без FK: конвенция после M251221163631ClearFk — junction-таблицы
		//без foreign key (FK ломал бы удаление сервисов/типов доступа с назначенными дефолтами)
		$this->createMany2ManyTable('default_access_in_services', [
			'services_id',
			'access_types_id',
		],[
			'ip_params'=>$this->text(),
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropTableIfExists('default_access_in_services');
	}
}
