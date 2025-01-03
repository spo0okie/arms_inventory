<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\StringHelper;

/**
 * SchedulesSearch represents the model behind the search form of `\app\models\Schedules`.
 */
class SchedulesSearch extends Schedules
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['name', 'comment', 'created_at'], 'safe'],
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
		$query = Schedules::find()
			->joinWith(['providingServices','acls','entries'])
			->where(['acls.schedules_id'=>null,'schedules.override_id'=>null]);
	
		// add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
			'pagination' => false,
			'totalCount' => $query->count('distinct(schedules.id)'),
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
	
	
		$query->andFilterWhere(['or like', 'schedules.name', StringHelper::explode($this->name,'|',true,true)]);
            //->andFilterWhere(['like', 'comment', $this->comment]);

        return new ActiveDataProvider([
			'query' => $query,
			'pagination' => false,
			'totalCount' => $query->count('distinct(schedules.id)'),
		]);
    }
}
