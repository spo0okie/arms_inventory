<?php
/**
 * Вычисляемые поля DNS-имён.
 *
 * Трейт содержит ТОЛЬКО calc-поля (без атрибутов таблицы), чтобы подключаться
 * и к паре DnsNames/DnsNamesHistory: колонки name у таблицы нет (левая часть
 * имени хранится в host), полное имя собирается здесь и работает как в мастере,
 * так и в записи журнала.
 */

namespace app\models\traits;

use app\models\Domains;

/**
 * @package app\models\traits
 *
 * @property int    $domain_id
 * @property string $host
 * @property string $fqdn
 * @property bool   $isApex
 */
trait DnsNamesModelCalcFieldsTrait
{
	/**
	 * Полное DNS-имя: host.zone, для apex — fqdn самой зоны.
	 * Зона берётся из общего кэша справочника доменов (getLoadedItem): trait
	 * подключён и к записи журнала, где связи domain нет.
	 * @return string
	 */
	public function getFqdn()
	{
		$domain=$this->domain_id?Domains::getLoadedItem($this->domain_id,true):null;
		$zone=is_object($domain)?(string)$domain->fqdn:'';
		$host=trim((string)($this->host??''));
		if ($host==='') return $zone!==''?$zone:'- зона не задана -';
		return $zone!==''?$host.'.'.$zone:$host;
	}

	/**
	 * Имя на apex зоны (host пустой): запись самой зоны, а не имени в ней.
	 * @return bool
	 */
	public function getIsApex()
	{
		return trim((string)($this->host??''))==='';
	}
}
