<?php

namespace app\models;

use app\models\traits\DnsNamesModelCalcFieldsTrait;

/**
 * Журнал изменений DNS-имён (зеркало таблицы dns_names_history).
 *
 * @property int $id
 * @property int|null $master_id
 * @property int|null $domain_id
 * @property string|null $host
 * @property string|null $ip
 * @property string|null $comment
 * @property string|null $updated_at
 * @property string|null $updated_by
 * @property string|null $updated_comment
 * @property string $fqdn
 */
class DnsNamesHistory extends HistoryModel
{
	use DnsNamesModelCalcFieldsTrait;

	public static $title = 'Изменение DNS-имени';
	public static $titles = 'Изменения DNS-имён';

	public static $nameAttr = 'fqdn';

	public $masterClass = DnsNames::class;

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'dns_names_history';
	}
}
