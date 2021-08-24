<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Acls;

/**
 * AclsSearch represents the model behind the search form of `\app\models\Acls`.
 */
class AclsSearch extends Acls
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'schedules_id', 'services_id', 'ips_id', 'comps_id', 'techs_id'], 'integer'],
            [['comment', 'notepad'], 'safe'],
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
        $query = Acls::find();

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
            'schedules_id' => $this->schedules_id,
            'services_id' => $this->services_id,
            'ips_id' => $this->ips_id,
            'comps_id' => $this->comps_id,
            'techs_id' => $this->techs_id,
        ]);

        $query->andFilterWhere(['like', 'comment', $this->comment])
            ->andFilterWhere(['like', 'notepad', $this->notepad]);

        return $dataProvider;
    }
}
