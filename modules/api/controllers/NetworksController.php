<?php

namespace app\modules\api\controllers;

use app\models\Networks;
use OpenApi\Attributes as OA;
use yii\web\NotFoundHttpException;

/**
 * REST-контроллер IP-сетей (/api/networks).
 *
 * CRUD-действия (index/view/create/update/delete/search/filter) предоставляет
 * BaseRestController автоматически, здесь задаётся набор полей поиска и
 * добавлен поиск сети по произвольному IP-адресу.
 *
 * Особенности модели при create/update:
 *  - `text_addr` — обязательное поле, адрес с маской в CIDR ('192.168.1.0/24');
 *    числовые `addr`/`mask` пересчитываются моделью в beforeSave, слать их не нужно;
 *  - шлюз и DHCP-сервер удобнее передавать текстом (`text_router`, `text_dhcp`),
 *    числовые `router`/`dhcp` модель заполняет сама.
 *
 * @package app\modules\api\controllers
 */
class NetworksController extends BaseRestController
{
	public $modelClass = Networks::class;

	public function accessMap(): array
	{
		return array_merge_recursive(parent::accessMap(), [
			'view' => ['by-ip'],
			'view-networks' => ['by-ip'],
		]);
	}

	/**
	 * Поля поиска (search/filter) с маппингом в атрибуты модели.
	 * Маппим только реальные столбцы таблицы: у Networks нет метаданных
	 * `join`/`filter`, поэтому поиск по связанным моделям (VLAN, сегмент)
	 * ведётся по их идентификаторам.
	 *
	 *  - `addr`/`text_addr` — точный поиск по адресу сети в CIDR-нотации
	 *    (адрес нормализуется в beforeSave, поэтому '192.168.1.0/24', а не '192.168.1.5/24');
	 *  - `name-like`        — неточный (LIKE) поиск по названию;
	 *  - `vlan_id`, `segments_id`, `archived` — фильтрация списков.
	 *
	 * Поиск сети, которой принадлежит произвольный адрес — отдельным
	 * действием {@see actionByIp}, т.к. требует сравнения по маске.
	 *
	 * @var array
	 */
	public static array $searchFields = [
		'id',
		'name' => 'name',
		'name-like' => 'name',
		'addr' => 'text_addr',
		'text_addr' => 'text_addr',
		'vlan_id' => 'vlan_id',
		'segments_id' => 'segments_id',
		'archived' => 'archived',
	];

	/**
	 * Параметры неточного (LIKE) поиска
	 * @var array
	 */
	public static array $searchFieldsLike = ['name-like'];

	/**
	 * Сортировка результатов поиска по числовому адресу сети —
	 * даёт естественный порядок подсетей (10.0.0.0 < 10.0.1.0 < 192.168.0.0).
	 * @var array
	 */
	public static array $searchOrder = ['addr' => SORT_ASC];

	/**
	 * Возвращает сеть, которой принадлежит указанный IP-адрес.
	 * Если адрес попадает в несколько сетей (вложенные подсети),
	 * возвращается самая узкая из них (с наибольшей маской).
	 *
	 * GET-параметры:
	 * @param string $ip IP-адрес ('192.168.1.15')
	 *
	 * @return Networks
	 * @throws NotFoundHttpException если ни одна сеть не содержит этот адрес
	 */
	#[OA\Get(
		path: "/web/api/{controller}/by-ip",
		summary: "Найти сеть, которой принадлежит указанный IP-адрес",
		parameters: [
			new OA\Parameter(
				name: "ip",
				description: "IPv4-адрес узла",
				in: "query",
				required: true,
				schema: new OA\Schema(type: "string", example: "192.168.1.15")
			)
		],
		responses: [
			new OA\Response(
				response: 200,
				description: "OK",
				content: new OA\MediaType(
					mediaType: "application/json",
					schema: new OA\Schema(ref: "#/components/schemas/{model}(read)")
				),
			),
			new OA\Response(response: 404, description: "Сеть с таким адресом не найдена"),
		]
	)]
	public function actionByIp(string $ip): Networks
	{
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
			throw new NotFoundHttpException("Invalid IPv4 address $ip");
		}
		$numeric = ip2long($ip) & 0xFFFFFFFF;

		//адрес принадлежит сети, если его сетевая часть совпадает с адресом сети:
		//addr = ip & маска, где маска собирается из длины префикса [[mask]]
		$network = Networks::find()
			->where(['and',
				'[[mask]] IS NOT NULL',
				'[[addr]] = :ip & ((0xFFFFFFFF << (32 - [[mask]])) & 0xFFFFFFFF)',
			])
			->params([':ip' => $numeric])
			->orderBy(['mask' => SORT_DESC])	//самая узкая (вложенная) сеть первой
			->one();

		if (!is_object($network)) {
			throw new NotFoundHttpException("Network for $ip not found");
		}
		/* @var Networks $network */
		return $network;
	}

	/**
	 * Сценарии REST acceptance для {@see actionByIp} (см. tests/rest/RestAccessCest.php).
	 * Адрес самой сети всегда ей принадлежит, поэтому позитивный сценарий строится
	 * из text_addr тестовой модели. Негативный — заведомо невалидный адрес → 404.
	 *
	 * @return array
	 */
	public function testByIp(): array
	{
		$data = $this->getTestData();
		$ip = explode('/', (string)$data['view']->text_addr)[0];
		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
			return static::skipScenario('no usable network address', 'у тестовой сети пустой text_addr');
		}
		return [
			[
				'name' => 'by network address',
				'method' => 'GET',
				'route' => '{controller}/by-ip',
				'GET' => ['ip' => $ip],
				'response' => 200,
			],
			[
				'name' => 'invalid ip',
				'method' => 'GET',
				'route' => '{controller}/by-ip',
				'GET' => ['ip' => 'not-an-ip'],
				'response' => 404,
			],
		];
	}
}
