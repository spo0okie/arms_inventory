<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use app\types\VlanType;
use app\models\NetVlans;

/**
 * NetVlansSearch represents the model behind the search form of `\app\models\NetVlans`.
 */
class NetVlansSearch extends NetVlans
{
	public $networks_ids;
	/** @var string Режим списка внутри L2-домена: used, used_reserved, free или all. */
	public $availability = 'used';
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', ], 'integer'],
            [['name', 'comment', 'vlan'], 'string'],
            [['availability'], 'in', 'range' => ['used', 'used_reserved', 'free', 'all']],
            [['name', 'comment','vlan', 'domain_id','networks_ids', 'availability'], 'safe'],
        ];
    }

	/** Метаданные фильтра занятости для стандартных заголовков и подсказок DynaGrid. */
	public function attributeData()
	{
		return array_merge(parent::attributeData(), [
			'availability' => [
				'Статус',
				'hint' => 'Занят - VLAN создан в этом L2-домене.<br>'
					.'Свободные номера включают глобально зарезервированные но не занятые в этом L2 домене.',
				'searchHint' => 'Показать занятые, занятые вместе с резервами, все или только свободные номера (включая глобальные резервы).',
				'type' => 'string',
			],
		]);
	}

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = NetVlans::find()
		//.marker — жадная загрузка цветового маркера домена (issue #141)
		->joinWith(['netDomain.marker','networks', 'globalVlan']);

        $vlanName = "CONCAT(".static::inheritedSql('name').", ' (', net_vlans.vlan, ')')";
		$vlanComment = static::inheritedSql('comment');
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
			'pagination' => ['pageSize' => 100,],
			'sort'=> [
				'defaultOrder' => ['name'=>SORT_ASC],
				'attributes'=>[
					'name'=>[
						'asc'=>[$vlanName=>SORT_ASC],
						'desc'=>[$vlanName=>SORT_DESC],
					],
					'vlan' => ['asc' => ['net_vlans.vlan' => SORT_ASC], 'desc' => ['net_vlans.vlan' => SORT_DESC]],
					'domain_id'=>[
						'asc'=>['net_domains.name'=>SORT_ASC],
						'desc'=>['net_domains.name'=>SORT_DESC],
					],
					'comment' => ['asc' => [$vlanComment => SORT_ASC], 'desc' => [$vlanComment => SORT_DESC]],
				]
			],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query
			->andFilterWhere(['net_vlans.vlan' => $this->vlan])
			->andFilterWhere(['or like', $vlanName, \app\helpers\StringHelper::explode($this->name,'|',true,true)])
			->andFilterWhere(['or like', 'net_domains.name', \app\helpers\StringHelper::explode($this->domain_id,'|',true,true)])
			->andFilterWhere(['or like', 'concat(networks.text_addr,"/",networks.mask,"(",IFNULL(networks.name,""))', \app\helpers\StringHelper::explode($this->networks_ids,'|',true,true)])
            ->andFilterWhere(['or like', $vlanComment, \app\helpers\StringHelper::explode($this->comment,'|',true,true)]);

        return $dataProvider;
    }

	/**
	 * Строит список номеров в одном домене; свободные строки существуют только в памяти.
	 * Глобальный резерв даёт описание, но не занимает номер в выбранном домене.
	 * @param int $domainId
	 * @param array $params
	 * @return ArrayDataProvider
	 */
	public function searchDomain(int $domainId, array $params): ArrayDataProvider
	{
		$this->load($params);
		$rows = [];
		$provider = new ArrayDataProvider([
			'allModels' => [],
			'key' => 'vlan',
			'pagination' => ['pageSize' => 100, 'pageParam' => 'vlan-page'],
			'sort' => [
				'sortParam' => 'vlan-sort',
				'defaultOrder' => ['vlan' => SORT_ASC],
				'attributes' => [
					'vlan',
					'name' => ['asc' => ['nameRecursive' => SORT_ASC], 'desc' => ['nameRecursive' => SORT_DESC]],
					'comment' => ['asc' => ['commentRecursive' => SORT_ASC], 'desc' => ['commentRecursive' => SORT_DESC]],
				],
			],
		]);
		if (!$this->validate()) return $provider;
		$bounds = null;
		if ($this->vlan !== null && $this->vlan !== '') {
			$bounds = VlanType::bounds($this->vlan);
			if ($bounds === null) {
				$this->addError('vlan', 'Укажите номер или диапазон от 1 до 4094.');
				return $provider;
			}
		}
		$local = NetVlans::find()->where(['domain_id' => $domainId])
			->with(['globalVlan', 'netDomain.marker'])->indexBy('vlan')->all();
		$reserved = NetVlans::find()->where(['domain_id' => null])->indexBy('vlan')->all();
		$numbers = match ($this->availability) {
			'used' => array_keys($local),
			// Объединяем по номеру: локальный VLAN и его резерв дают одну строку.
			'used_reserved' => array_keys($local + $reserved),
			default => range(VlanType::MIN_ID, VlanType::MAX_ID),
		};
		foreach ($numbers as $number) {
			$item = $local[$number] ?? null;
			if ($this->availability === 'free' && $item !== null) continue;
			if ($bounds !== null && ($number < $bounds[0] || $number > $bounds[1])) continue;
			$reserve = $reserved[$number] ?? null;
			$source = $item ?? $reserve;
			$name = (string)($source->nameRecursive ?? '');
			$comment = (string)($source->commentRecursive ?? '');
			if ($this->name !== null && $this->name !== '' && mb_stripos($name, $this->name) === false) continue;
			if ($this->comment !== null && $this->comment !== '' && mb_stripos($comment, $this->comment) === false) continue;
			if ($item === null) {
				// Свободный номер — несохранённая модель: штатные колонки получают тот же контракт.
				$item = new NetVlans(['vlan' => (int)$number, 'domain_id' => $domainId]);
				$item->populateRelation('globalVlan', $reserve);
				$item->populateRelation('networks', []);
			}
			$rows[] = $item;
		}
		$provider->allModels = $rows;
		return $provider;
	}
}
