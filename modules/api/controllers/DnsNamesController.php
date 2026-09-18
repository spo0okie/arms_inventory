<?php

namespace app\modules\api\controllers;

/**
 * REST-контроллер DNS-имён (/api/dns-names).
 *
 * CRUD-действия (index/view/create/update/delete/search/filter) предоставляет
 * BaseRestController автоматически. Адреса передаются текстом в поле `ip`
 * (по одному в строке) — как у оборудования; связи с IP строятся при сохранении.
 */
class DnsNamesController extends BaseRestController
{
	public $modelClass = 'app\models\DnsNames';

	/**
	 * Поля поиска (search/filter) с маппингом в атрибуты модели:
	 *  - domain_id — все имена зоны;
	 *  - host      — имя в зоне (точное совпадение; полное имя ищется через item-by-name web-контроллера).
	 * @var array
	 */
	public static array $searchFields = [
		'id',
		'domain_id' => 'domain_id',
		'host' => 'host',
	];
}
