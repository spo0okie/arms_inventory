<?php

namespace app\models;

use app\helpers\QueryHelper;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * MaintenanceJobsSearch represents the model behind the search form of `app\models\MaintenanceJobs`.
 */
class MaintenanceJobsSearch extends MaintenanceJobs
{
	
	public $objects;
	public $schedule;
	
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'schedules_id', 'services_id'], 'integer'],
            [['name', 'description', 'links', 'updated_at', 'updated_by','schedule','objects'], 'safe'],
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
        $query = MaintenanceJobs::find()
		->joinWith([
			'schedule',
			'comps',
			'techs',
			'services'
		]);

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
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'description', $this->description])
            ->andFilterWhere(['like', 'links', $this->links])
            ->andFilterWhere(QueryHelper::querySearchString(['AND/OR',
				'IFNULL(comps.name,"")',
				'IFNULL(techs.num,"")',
				'IFNULL(techs.hostname,"")',
				'IFNULL(services.name,"")'
			], $this->objects))
            ->andFilterWhere(['like', 'updated_by', $this->updated_by]);

        return $dataProvider;
    }
}
