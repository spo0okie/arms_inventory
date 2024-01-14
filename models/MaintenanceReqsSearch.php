<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * MaintenanceReqsSearch represents the model behind the search form of `app\models\MaintenanceReqs`.
 */
class MaintenanceReqsSearch extends MaintenanceReqs
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'spread_comps', 'spread_techs'], 'integer'],
            [['name', 'description', 'links', 'updated_at', 'updated_by'], 'safe'],
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
        $query = MaintenanceReqs::find();

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
            'spread_comps' => $this->spread_comps,
            'spread_techs' => $this->spread_techs,
            'updated_at' => $this->updated_at,
            'updated_by' => $this->updated_by,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'description', $this->description])
            ->andFilterWhere(['like', 'links', $this->links]);

        return $dataProvider;
    }
}
