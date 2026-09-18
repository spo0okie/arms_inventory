<?php

namespace app\models;

use app\helpers\ArrayHelper;
use app\models\base\ArmsModel;
use app\models\traits\DnsNamesModelCalcFieldsTrait;
use voskobovich\linker\LinkerBehavior;
use yii\db\ActiveQuery;

/**
 * DNS-имя: «имя в зоне → набор IP» (plans/access-chains.md, итерация 1).
 *
 * Дополняет hostname узлов, которые остаются каноническими именами
 * ([[Comps::$name]]/[[Techs::$hostname]] + домен): здесь живёт то, чего у узла
 * нет — алиасы, имена сервисов на реверс-прокси, A-записи на белые адреса без
 * узла. Зона — существующий справочник [[Domains]] (он и так DNS-зона).
 * К чему имя относится (узел, сервис) — выводится через адреса: имя → IP →
 * ОС/оборудование → сервис; собственных ссылок на узлы и сервисы у имени нет.
 *
 * Адреса — как у оборудования: текстовый список `ip` для ввода/REST и junction
 * dns_names_in_ips для связей (заполняется в beforeSave из текста). Пустой набор
 * допустим: имя заведено, но ни на что не указывает.
 *
 * @property int $id
 * @property int $domain_id Зона
 * @property string $host Имя в зоне (левая часть FQDN); пусто = apex
 * @property string|null $ip IP-адреса по одному в строке
 * @property string|null $comment Комментарий
 * @property string|null $updated_at
 * @property string|null $updated_by
 *
 * @property string $fqdn Полное имя (calc)
 * @property string $name Подпись объекта (= fqdn)
 * @property Domains $domain
 * @property NetIps[] $netIps
 * @property int[] $net_ips_ids
 */
class DnsNames extends ArmsModel
{
	use DnsNamesModelCalcFieldsTrait;

	public static $title = 'DNS-имя';
	public static $titles = 'DNS-имена';

	//подпись объекта — полное имя (колонки name у таблицы нет)
	public static $nameAttr = 'fqdn';

	public static $newItemPrefix = 'Новое';

	/** @var NetIps[] адреса на момент beforeDelete — после удаления junction их надо проверить на «пустоту» */
	private $ipsBeforeDelete = [];

	public static function modelDescription(): string
	{
		return 'DNS-имена: записи в зонах (доменах), указывающие на IP-адреса. Дополняют hostname ОС и '
			. 'оборудования — алиасы, имена сервисов на реверс-прокси, имена на белых адресах без узла. '
			. 'Карточка домена собирает имена узлов и DNS-имена в единую карту зоны.';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function tableName()
	{
		return 'dns_names';
	}

	public $linksSchema = [
		'domain_id' => [Domains::class, 'dns_names_ids'],
		//адреса имя не «держат»: имя удаляется вместе со связями (как ОС с её IP)
		'net_ips_ids' => [NetIps::class, 'dns_names_ids', 'deletable' => true],
	];

	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		return [
			[
				'class' => LinkerBehavior::class,
				'relations' => [
					'net_ips_ids' => 'netIps',
				],
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return [
			//порядок принципиален: фильтр host умеет вытащить зону из FQDN-ввода
			//(www.example.com → зона example.com + host www), поэтому идёт до required у domain_id
			[['host'], 'default', 'value' => ''],
			[['host'], 'string', 'max' => 128],
			[['host'], 'filter', 'filter' => function ($value) {return $this->filterHost($value);}],
			[['domain_id'], 'required'],
			[['domain_id'], 'integer'],
			[['domain_id'], 'exist', 'skipOnError' => true, 'targetClass' => Domains::class, 'targetAttribute' => ['domain_id' => 'id']],
			[['host'], 'unique', 'targetAttribute' => ['domain_id', 'host'],
				'message' => 'В этой зоне такое имя уже есть'],
			[['ip'], 'string', 'max' => 768],
			['ip', function ($attribute) {
				NetIps::validateInput($this, $attribute);
			}],
			['ip', 'filter', 'filter' => function ($value) {
				return NetIps::filterInput($value);
			}],
			[['comment'], 'string', 'max' => 255],
			[['comment', 'ip'], 'default', 'value' => null],
		];
	}

	/**
	 * Нормализация имени в зоне: нижний регистр, без точек по краям; FQDN-ввод
	 * (www.example.com) раскладывается на зону и имя, если суффикс совпал с fqdn
	 * одной из заведённых зон (берётся самая длинная). Если зона не найдена —
	 * имя остаётся как есть в выбранной зоне (точки внутри имени допустимы:
	 * a.b в зоне example.com).
	 * @param string|null $value
	 * @return string
	 */
	protected function filterHost($value): string
	{
		$host = mb_strtolower(trim((string)$value, " \t\n\r\0\x0B."));
		if ($host === '' || mb_strpos($host, '.') === false) return $host;
		[$domain, $rest] = Domains::splitFqdn($host);
		if (is_object($domain)) {
			$this->domain_id = $domain->id;
			return $rest;
		}
		return $host;
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeData()
	{
		return ArrayHelper::recursiveOverride(parent::attributeData(), [
			'domain_id' => [
				'Зона',
				'hint' => 'DNS-зона (домен), в которой заведено имя. Подставляется сама, если в поле имени '
					. 'ввести полное имя с суффиксом одной из заведённых зон',
				'placeholder' => 'Выберите зону',
				'join' => ['domain'],
				'typeClass' => \app\types\LinkType::class,
			],
			'host' => [
				'Имя в зоне',
				'hint' => 'Левая часть полного имени (для <b>www.example.com</b> — <b>www</b>). '
					. 'Можно ввести полное имя: зона определится по суффиксу.<br>'
					. 'Пустое имя — запись на саму зону (apex)',
				'placeholder' => 'пусто = apex зоны',
				'typeClass' => \app\types\StringType::class,
			],
			'fqdn' => [
				'Полное имя',
				'indexLabel' => 'DNS-имя',
				'hint' => 'Полное DNS-имя: имя в зоне + fqdn зоны',
				'indexHint' => '{same}',
				'typeClass' => \app\types\StringType::class,
				'readOnly' => true,
			],
			'ip' => [
				'IP адреса',
				//формат заполнения подскажет IpsType (inputHint)
				'hint' => 'На какие адреса указывает имя (A-записи). Одно имя может вести на несколько '
					. 'адресов, несколько имён — на один. Пусто — имя заведено, но ни на что не указывает',
				'typeClass' => \app\types\IpsType::class,
				'join' => ['netIps.network'],
			],
			'netIps' => [
				'IP адреса',
				'hint' => 'Адреса, на которые указывает имя, с узлами, к которым они привязаны',
				'typeClass' => \app\types\LinkType::class,
			],
			'comment' => [
				'Комментарий',
				'hint' => 'Всё, что нужно знать об имени: зачем заведено, кем обслуживается, когда убрать',
				'typeClass' => \app\types\StringType::class,
			],
		]);
	}

	/**
	 * Зона имени
	 * @return ActiveQuery
	 */
	public function getDomain()
	{
		return $this->hasOne(Domains::class, ['id' => 'domain_id']);
	}

	/**
	 * Адреса, на которые указывает имя
	 * @return ActiveQuery
	 */
	public function getNetIps()
	{
		return $this->hasMany(NetIps::class, ['id' => 'ips_id'])->from(['dns_names_ips' => NetIps::tableName()])
			->viaTable('{{%dns_names_in_ips}}', ['dns_names_id' => 'id']);
	}

	/**
	 * Поиск по полному имени: FQDN раскладывается на зону и имя в зоне.
	 * Имя без зоны (или с неизвестной зоной) ищется по host во всех зонах.
	 * @param string $name
	 * @return DnsNames|null
	 */
	public static function findByName(string $name)
	{
		$name = mb_strtolower(trim($name, " \t\n\r\0\x0B."));
		if ($name === '') return null;
		[$domain, $host] = Domains::splitFqdn($name);
		if (is_object($domain)) {
			return static::find()->where(['domain_id' => $domain->id, 'host' => $host])->one();
		}
		return static::find()->where(['host' => $name])->one();
	}

	/**
	 * {@inheritdoc}
	 */
	public function beforeSave($insert)
	{
		if (!parent::beforeSave($insert)) return false;

		/* взаимодействие с NetIPs — как у оборудования (Techs::beforeSave) */
		$this->net_ips_ids = NetIps::fetchIpIds($this->ip);

		//адреса, от которых имя отвязалось, проверяем на «пустоту»
		if (!$insert) {
			$old = static::findOne($this->id);
			if (!is_null($old)) {
				$removed = array_diff($old->net_ips_ids, $this->net_ips_ids);
				foreach ($removed as $id) {
					if (is_object($ip = NetIps::findOne($id))) $ip->detachDnsName($this->id);
				}
			}
		}
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function beforeDelete()
	{
		if (!parent::beforeDelete()) return false;
		//junction чистит generic deleteJunctionRows() в afterDelete — запоминаем адреса,
		//чтобы после этого проверить, не остались ли они ни к чему не привязанными
		$this->ipsBeforeDelete = $this->netIps;
		return true;
	}

	/**
	 * {@inheritdoc}
	 */
	public function afterDelete()
	{
		parent::afterDelete();
		foreach ($this->ipsBeforeDelete as $ip) {
			if (is_object($fresh = NetIps::findOne($ip->id))) $fresh->deleteIfEmpty();
		}
		$this->ipsBeforeDelete = [];
	}

	/**
	 * Возвращает список всех элементов
	 * @return array
	 */
	public static function fetchNames()
	{
		$list = static::find()
			->with(['domain'])
			->orderBy(['domain_id' => SORT_ASC, 'host' => SORT_ASC])
			->all();
		return ArrayHelper::map($list, 'id', 'fqdn');
	}
}
