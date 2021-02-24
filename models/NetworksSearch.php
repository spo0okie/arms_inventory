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
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'vlan_id', 'addr', 'mask', 'router', 'dhcp'], 'integer'],
            [['name', 'comment','domain'], 'safe'],
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
        $query = Networks::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
			'pagination' => ['pageSize' => 100,],
		]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'vlan_id' => $this->vlan_id,
            'addr' => $this->addr,
            'mask' => $this->mask,
            'router' => $this->router,
            'dhcp' => $this->dhcp,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'comment', $this->comment]);

        return $dataProvider;
    }
}
