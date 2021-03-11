<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Networks;

/**
 * NetworksSearch represents the model behind the search form of `app\models\Networks`.
 */
class NetworksSearch extends Networks
{
	public $domain_id;
	
	
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id',  'addr', 'mask', 'router', 'dhcp'], 'integer'],
            [['name','vlan_id', 'domain_id', 'comment', 'segments_id',], 'safe'],
        ];
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
        $query = Networks::find()
			//->select(['*',''])
			->joinWith([
				'segment',
				'netVlan.netDomain',
			]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
			'pagination' => ['pageSize' => 100,],
			'sort'=> [
				//'defaultOrder' => ['domains_id'=>SORT_ASC],
				'attributes'=>[
					'name'=>[
						'asc'=>['addr'=>SORT_ASC],
						'desc'=>['addr'=>SORT_DESC],
					],
					'domain_id'=>[
						'asc'=>['net_domains.name'=>SORT_ASC],
						'desc'=>['net_domains.name'=>SORT_DESC],
					],
					'vlan_id'=>[
						'asc'=>['net_vlans.vlan'=>SORT_ASC],
						'desc'=>['net_vlans.vlan'=>SORT_DESC],
					],
					'segments_id'=>[
						'asc'=>['segments.name'=>SORT_ASC],
						'desc'=>['segments.name'=>SORT_DESC],
					],
					'comment'
				]
			],
		]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query
			->andFilterWhere(['like', 'concat(networks.text_addr,"/",networks.mask,"(",IFNULL(networks.name,""))', $this->name])
			->andFilterWhere(['like', 'concat(net_vlans.name," (",net_vlans.vlan)', $this->vlan_id])
			->andFilterWhere(['like', 'net_domains.name', $this->domain])
			->andFilterWhere(['like', 'segments.name', $this->segments_id])
            ->andFilterWhere(['like', 'networks.comment', $this->comment]);

        return $dataProvider;
    }
}
