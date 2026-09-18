<?php

namespace app\migrations;

use app\migrations\arms\ArmsMigration;

/**
 * Создание таблиц dns_names, dns_names_history и junction dns_names_in_ips —
 * DNS-имена, дополняющие hostname узлов (plans/access-chains.md, итерация 1).
 *
 * DNS-имя = «имя в зоне → набор IP». Зона — существующий справочник domains
 * (он и так DNS-зона: NetBIOS-имя + fqdn). Hostname ОС/оборудования никуда не
 * переезжают — DnsNames описывает то, чего у узла нет: алиасы, имена сервисов
 * на реверс-прокси, A-записи на белые адреса без узла.
 *
 * Адреса хранятся так же, как у оборудования (techs.ip + ips_in_techs): текстовый
 * список `ip` для ввода/REST и junction на net_ips для связей. Пустой набор IP
 * допустим (имя заведено, но ни на что не указывает).
 *
 * FK на уровне БД не создаём: скалярные связи описываются linksSchema модели,
 * junction-таблицы по конвенции проекта тоже без FK (M251221163631ClearFk),
 * сирот чистит generic deleteJunctionRows().
 */
class M260918120000CreateTableDnsNames extends ArmsMigration
{
	/**
	 * {@inheritdoc}
	 */
	public function up()
	{
		if (!$this->tableExists('dns_names')) {
			$this->createTable('dns_names', [
				'id' => $this->primaryKey(),
				'domain_id' => $this->integer()->notNull()->comment('Зона (domains.id)'),
				'host' => $this->string(128)->notNull()->defaultValue('')->comment('Имя в зоне (левая часть FQDN); пусто = apex зоны'),
				'ip' => $this->string(768)->null()->comment('IP-адреса по одному в строке (текстовый ввод; связи — dns_names_in_ips)'),
				'comment' => $this->string(255)->null()->comment('Комментарий'),
				'updated_at' => $this->timestamp()->null()->comment('Дата последнего изменения'),
				'updated_by' => $this->string(32)->null()->comment('Автор последних изменений (username)'),
			]);

			//в зоне имя уникально
			$this->createIndex('idx-dns_names-domain-host', 'dns_names', ['domain_id', 'host'], true);
		}

		if (!$this->tableExists('dns_names_history')) {
			$this->createTable('dns_names_history', [
				'id' => $this->primaryKey(),
				'master_id' => $this->integer(),
				'domain_id' => $this->integer(),
				'host' => $this->string(128),
				'ip' => $this->string(768),
				'comment' => $this->string(255),
				'updated_at' => $this->timestamp()->null(),
				'updated_by' => $this->string(32),
				'updated_comment' => $this->string(),
				'changed_attributes' => $this->text(),
			]);

			$this->createIndex('idx-dns_names_history-master_id', 'dns_names_history', 'master_id');
			$this->createIndex('idx-dns_names_history-updated_at', 'dns_names_history', 'updated_at');
			$this->createIndex('idx-dns_names_history-updated_by', 'dns_names_history', 'updated_by');
		}

		//числовые ключи = без FK (конвенция junction-таблиц); createMany2ManyTable
		//пересоздаёт таблицу — на повторном прогоне существующую не трогаем
		if (!$this->tableExists('dns_names_in_ips')) {
			$this->createMany2ManyTable('dns_names_in_ips', ['dns_names_id', 'ips_id']);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function down()
	{
		$this->dropTableIfExists('dns_names_in_ips');
		$this->dropTableIfExists('dns_names_history');
		$this->dropTableIfExists('dns_names');
	}
}
