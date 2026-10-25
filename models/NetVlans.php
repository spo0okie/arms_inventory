<?php

namespace app\models;

use app\generation\context\AttributeContext;
use app\generation\context\GenerationContext;
use app\generation\ModelFactory;
use app\models\base\ArmsModel;
use app\types\VlanType;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "net_vlans".
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $sname
 * @property string $domainCode
 * @property int|string $vlan
 * @property int|null $domain_id
 * @property string $comment
 * @property NetDomains $netDomain
 * @property Networks[] $networks
 * @property NetVlans|null $parent Глобальный резерв для локального VLAN
 * @property NetVlans|null $globalVlan Связь для жадной загрузки резерва
 * @property string $nameRecursive
 * @property string $commentRecursive
 */
class NetVlans extends ArmsModel
{

	public static $title='Vlan';
	public static $titles='Vlan\'ы';

	/**
	 * Роли генерации VLAN; локальный VLAN обязательно принадлежит L2-домену.
	 * @return array<string, callable(self, GenerationContext): void>
	 */
	public static function roles(): array
	{
		return [
			'real' => static function (self $model, GenerationContext $context): void {
				// Сохраняем seed и глубину исходной генерации; готовый домен не заменяем.
				ModelFactory::generateAttribute(new AttributeContext(
					attribute: 'domain_id',
					model: $model,
					generationContext: $context,
					empty: false,
					required: true,
				));
			},
		];
	}

	/** {@inheritdoc} */
	public static function modelDescription(): string
	{
		return 'VLAN в доменах L2 и глобальные резервы номеров. Локальные VLAN наследуют незаполненные название и пояснение от резерва.';
	}

	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'net_vlans';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
			// Пустой выбор домена всегда обозначает один и тот же резерв (NULL).
			[['domain_id'], 'default', 'value' => null],
			[['domain_id'], 'integer', 'min' => 1],
			[['domain_id'], 'exist', 'targetClass' => NetDomains::class, 'targetAttribute' => 'id'],
			[['domain_id'], 'validateReservation', 'skipOnEmpty' => false],
			[['name'], 'string', 'max' => 255],
			[['comment'], 'safe'],
			[['vlan'], 'required'],
			[['vlan'], 'validateVlanRange', 'skipOnEmpty' => false],
			[['vlan'], 'validateVlanUnique'],
		];
    }

	public $linksSchema=[
		'domain_id'=>[NetDomains::class,'net_vlans_ids'],
		'networks_ids'=>[Networks::class,'vlan_id'],
	];

    /**
     * {@inheritdoc}
     */
    public function attributeData()
    {
        return [
            'id' => [
            	'ID',
			],
			'name' => [
				'Название',
				'hint' => 'Понятное обозначение.<br>'
					.'Может наследоваться от глобального резервирования этого VLAN (если такое есть).<br>'
					.'Можно использовать макрос {VLAN} для подстановки номера Vlan<br>'
					.'(удобно при создании нескольких VLAN указанием диапазона в поле Vlan ID).<br>'
					.Networks::$latinNameHint,
				'is_inheritable' => true,
			],
			'networks_ids' => [
				'Сети',
			],
            'vlan' => [
            	'Vlan ID',
				//формат и границы (1-4094, диапазоны) подскажет VlanType (inputHint)
				'hint' => 'При указании диапазона будут созданы все Vlan этого диапазона',
				'type' => 'integer', 	//вообще можно в форме указывать диапазоны, но это будет обработано
										//и сконвертировано в множество integer
				'typeClass' => VlanType::class,
			],
			'domain_id' => [
				'Домен L2',
				'hint' => 'В каком L2 домене находится этот Vlan'
				. '<br>(Для разделения VLAN с одинаковыми номерами, но в разных доменах)'
				. '<br><i>Если L2 домен не указать, будет создано резервирование номера VLAN для всех L2 доменов<br>'
				.'(для сквозного использования номеров VLAN).</i>',
				'placeholder' => 'Резервирование VLAN',
			],
			'comment' => [
				'Пояснение',
				//был ключ 'comment' вместо 'hint' (опечатка) - подсказка не отображалась
				'hint' => 'Все что нужно знать об этом VLAN, но что не ясно из названия.<br>'
				.'Может наследоваться от глобального резервирования этого VLAN (если такое есть).',
				'is_inheritable' => true,
				'type' => 'text',
				'typeClass' => \app\types\TextType::class,
			],
        ];
    }


	/**
	 * Валидация формата номера и диапазона единым типом VLAN.
	 * @param string $attribute
	 * @param array $params
	 */
	public function validateVlanRange($attribute, $params = [])
	{
		VlanType::validateValue($this, $attribute, $this->isNewRecord);
	}

	/** Проверяет все номера диапазона в выбранном домене, включая глобальный резерв. */
	public function validateVlanUnique($attribute, $params = []): void
	{
		if ($this->hasErrors('domain_id') || ($bounds = VlanType::bounds($this->$attribute)) === null) return;
		$query = static::find()->where(['domain_id' => $this->domain_id ?: null])
			->andWhere(['between', 'vlan', $bounds[0], $bounds[1]]);
		if (!$this->isNewRecord) $query->andWhere(['<>', 'id', $this->id]);
		$conflicts = $query->orderBy('vlan')->select('vlan')->column();
		if ($conflicts) $this->addError($attribute, 'В выбранном домене / резервированиях уже есть VLAN: '.implode(', ', $conflicts).'.');
	}

	/** Не позволяет превратить используемый локальный VLAN в резерв. */
	public function validateReservation($attribute, $params = []): void
	{
		if ($this->$attribute === null && !$this->isNewRecord
			&& $this->getOldAttribute('domain_id') !== null && $this->getNetworks()->exists()) {
			$this->addError($attribute, 'Нельзя убрать L2-домен у VLAN, к которому привязаны L3 сети.');
		}
	}

	/** Сбрасывает вычисленные значения перед AJAX-валидацией изменённой формы. */
	public function beforeValidate()
	{
		$this->recursiveCache = [];
		return parent::beforeValidate();
	}

	/** Резерв не имеет родителя: иначе пустые поля рекурсивно наследовались бы от себя. */
	public function getParent(): ?NetVlans
	{
		return empty($this->domain_id) || VlanType::bounds($this->vlan) === null
			|| str_contains((string)$this->vlan, '-') ? null : $this->globalVlan;
	}

	/** Наследует пустое пояснение; общий обработчик TextType заменяет только макрос PARENT. */
	public function getCommentRecursive(): string
	{
		return (string)$this->findRecursiveAttr('comment', 'commentRecursive');
	}

	/** Связь по номеру для жадной загрузки и SQL-поиска унаследованных значений. */
	public function getGlobalVlan(): ActiveQuery
	{
		return $this->hasOne(self::class, ['vlan' => 'vlan'])
			->alias('global_vlan')->andOnCondition(['global_vlan.domain_id' => null]);
	}

	/** SQL-эквивалент наследования строковых полей; имена колонок задаются только кодом. */
	public static function inheritedSql(string $attribute): string
	{
		if (!in_array($attribute, ['name', 'comment'], true)) {
			throw new \InvalidArgumentException('Наследуются только название и пояснение VLAN.');
		}
		// empty() в общем механизме наследования считает строку «0» пустой.
		return "COALESCE(NULLIF(NULLIF(net_vlans.$attribute, ''), '0'), NULLIF(NULLIF(global_vlan.$attribute, ''), '0'), '')";
	}

	/**
	 * @return ActiveQuery
	 */
	public function getNetDomain()
	{
		return $this->hasOne(NetDomains::class, ['id' => 'domain_id']);
	}



	/**
	 * CSS код сегмента к которому относится VLAN
	 * @return string
	 */
	public function getDomainCode()
	{
		if (is_object($domain=$this->netDomain)) return 'net-domain-'.$domain->name;
		return '';
	}

	/**
	 * @return ActiveQuery|Networks
	 */
	public function getNetworks()
	{
		return $this->hasMany(Networks::class, ['vlan_id' => 'id']);
	}

	/**
	 * Подсказки по номерам VLAN для помещений: имя VLAN и его сети.
	 *
	 * Нужны там, где VLAN показан голым номером (таблица портов коммутатора):
	 * что это за сеть, инвентаризация знает через объявленную цепочку
	 * сеть → VLAN → L2-домен → помещение. Один номер может жить в нескольких
	 * доменах площадки - тогда описания склеиваются.
	 *
	 * @param int[] $placeIds помещения (обычно поддерево площадки)
	 * @return array [номер VLAN => текст подсказки]
	 */
	public static function hintsForPlaces(array $placeIds): array
	{
		if (!count($placeIds)) return [];
		$hints = [];
		/** @var static $vlan */
		foreach (static::find()
			->joinWith('netDomain', false, 'INNER JOIN')
			->where(['net_domains.places_id' => $placeIds])
			->with(['networks', 'globalVlan'])->all() as $vlan) {
			$parts = array_filter([trim((string)$vlan->nameRecursive)]);
			foreach ($vlan->networks as $network) {
				if (!empty($network->archived)) continue;
				$parts[] = trim($network->text_addr.' '
					.($network->name ? '('.$network->name.')' : ''));
			}
			if (!count($parts)) continue;
			$text = implode(', ', $parts);
			$known = $hints[(int)$vlan->vlan] ?? '';
			$hints[(int)$vlan->vlan] = $known === '' ? $text : $known.'; '.$text;
		}
		return $hints;
	}

	/**
	 * Search name
	 * @return string
	 */
	public function getSname()
	{
		return $this->nameRecursive.' ('.$this->vlan.')';
	}

	/**
	 * Возвращает подписи VLAN с учётом наследования; для L3-сетей исключает резервы.
	 * @param bool $localOnly Только VLAN с указанным доменом.
	 * @return array
	 */
	public static function fetchNames($localOnly = false)
	{
		$query = static::find()->joinWith('globalVlan');
		if ($localOnly) $query->andWhere(['not', ['net_vlans.domain_id' => null]]);
		$list = $query->orderBy(new \yii\db\Expression(static::inheritedSql('name').' ASC, net_vlans.vlan ASC'))->all();
		return ArrayHelper::map($list, 'id', 'sname');
	}

	/**
	 * Создаёт диапазон целиком в транзакции, сохраняя последний VLAN в текущем объекте.
	 * При ошибке восстанавливает введённый диапазон для повторного показа формы.
	 * @param bool $runValidation
	 * @param array|null $attributeNames
	 * @return bool
	 */
	public function save($runValidation = true, $attributeNames = null)
	{
		if (!is_string($this->vlan) || !str_contains($this->vlan, '-')) {
			return parent::save($runValidation, $attributeNames);
		}
		// Диапазон проверяется целиком до первой записи, даже при save(false).
		if (!$this->validate()) return false;
		[$start, $end] = VlanType::bounds($this->vlan);
		$original = $this->attributes;
		$transaction = static::getDb()->beginTransaction();
		$saved = false;
		try {
			for ($number = $start; $number < $end; $number++) {
				$item = new self();
				$item->attributes = $original;
				$item->vlan = $number;
				if (!$item->save()) {
					$this->addErrors($item->errors);
					return false;
				}
			}
			$this->vlan = $end;
			if (!parent::save(true, $attributeNames)) return false;
			$transaction->commit();
			$saved = true;
			return true;
		} finally {
			if (!$saved) {
				if ($transaction->isActive) $transaction->rollBack();
				$this->setAttributes($original, false);
				$this->setOldAttributes(null);
				$this->recursiveCache = [];
			}
		}
	}

	/** Подставляет номер в явно введённое название, не копируя унаследованное значение. */
	public function beforeSave($insert)
	{
		if (!parent::beforeSave($insert)) return false;
		if ($this->name !== null) $this->name = str_replace('{VLAN}', (string)$this->vlan, $this->name);
		return true;
	}
}
