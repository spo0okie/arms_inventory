<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * ServiceConnectionsSearch represents the model behind the search form of `app\models\ServiceConnections`.
 */
class ServiceConnectionsSearch extends ServiceConnections
{
	public $ids;
	public $services_ids;
	
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
			[['ids','services_ids'],'each','rule'=>['integer']],
            [['id', 'initiator_id', 'target_id'], 'integer'],
            [['initiator', 'target', 'comment'], 'safe'],
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
        $query = ServiceConnections::find();

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
            'initiator_id' => $this->initiator_id,
            'target_id' => $this->target_id,
            'updated_at' => $this->updated_at,
        ]);
	
		if (isset($this->ids) && is_array($this->ids)) {
			if (count($this->ids))
				$query->andFilterWhere(['service_connections.id'=>$this->ids]);
			else
				$query->where('0=1');
		}
	
		if (isset($this->services_ids) && is_array($this->services_ids)) {
			if (count($this->services_ids))
				$query
					->andFilterWhere(['or',
						['service_connections.target_id'=>$this->services_ids],
						['service_connections.initiator_id'=>$this->services_ids],
					]);
			else
				$query->where('0=1');
		}
	
		$query
			->andFilterWhere(['like', 'initiator_details', $this->initiator_details])
            ->andFilterWhere(['like', 'target_details', $this->target_details])
            ->andFilterWhere(['like', 'comment', $this->comment])
            //->andFilterWhere(['like', 'updated_by', $this->updated_by])
		;
        
        
        return $dataProvider;
    }
}
