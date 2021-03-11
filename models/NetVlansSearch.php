<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\NetVlans;

/**
 * NetVlansSearch represents the model behind the search form of `\app\models\NetVlans`.
 */
class NetVlansSearch extends NetVlans
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', ], 'integer'],
            [['name', 'comment','vlan', 'domain_id'], 'safe'],
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
        $query = NetVlans::find()
		->joinWith(['netDomain']);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
			'pagination' => ['pageSize' => 100,],
			'sort'=> [
				//'defaultOrder' => ['domains_id'=>SORT_ASC],
				'attributes'=>[
					'name'=>[
						'asc'=>['vlan'=>SORT_ASC],
						'desc'=>['vlan'=>SORT_DESC],
					],
					'domain_id'=>[
						'asc'=>['net_domains.name'=>SORT_ASC],
						'desc'=>['net_domains.name'=>SORT_DESC],
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

        // grid filtering conditions
        $query->andFilterWhere(['like', 'CONCAT(net_vlans.name," (",net_vlans.vlan)', $this->name])
			->andFilterWhere(['like', 'net_domains.name', $this->domain_id])
            ->andFilterWhere(['like', 'comment', $this->comment]);

        return $dataProvider;
    }
}
