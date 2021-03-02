<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Ports;

/**
 * PortsSearch represents the model behind the search form of `app\models\Ports`.
 */
class PortsSearch extends Ports
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'techs_id', 'link_techs_id', 'link_arms_id', 'link_ports_id'], 'integer'],
            [['name', 'comment'], 'safe'],
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
        $query = Ports::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
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
            'techs_id' => $this->techs_id,
            'link_techs_id' => $this->link_techs_id,
            'link_arms_id' => $this->link_arms_id,
            'link_ports_id' => $this->link_ports_id,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'comment', $this->comment]);

        return $dataProvider;
    }
}
