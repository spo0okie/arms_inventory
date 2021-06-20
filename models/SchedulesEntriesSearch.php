<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\SchedulesEntries;

/**
 * SchedulesEntriesSearch represents the model behind the search form of `\app\models\SchedulesEntries`.
 */
class SchedulesEntriesSearch extends SchedulesEntries
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'schedule_id'], 'integer'],
            [['date', 'schedule', 'comment', 'created_at'], 'safe'],
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
        $query = SchedulesEntries::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
			'sort' => [
				'defaultOrder' => [
					'date' => SORT_DESC
				]
			]
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
            'schedule_id' => $this->schedule_id,
            'created_at' => $this->created_at,
        ]);

        $query->andFilterWhere(['like', 'date', $this->date])
            ->andFilterWhere(['not',['in', 'date', ['1','2','3','4','5','6','7','def']]])
            ->andFilterWhere(['like', 'schedule', $this->schedule])
            ->andFilterWhere(['like', 'comment', $this->comment]);

        return $dataProvider;
    }
}
