<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Services;

/**
 * ServicesSearch represents the model behind the search form of `app\models\Services`.
 */
class ServicesSearch extends Services
{
	
	public $responsible;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
	        [['id', 'is_end_user', 'responsible_id', 'providing_schedule_id', 'support_schedule_id'], 'integer'],
            [['name', 'description', 'links', 'notebook','responsible'], 'safe'],
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
        $query = Services::find()
            ->joinWith('support support')
            ->joinWith('responsible responsible');

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
	        'pagination' => ['pageSize' => 500,],
	        'sort'=> ['defaultOrder' => ['name'=>SORT_ASC]]
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
            'is_end_user' => $this->is_end_user,
	        'responsible_id' => $this->responsible_id,
	        'providing_schedule_id' => $this->providing_schedule_id,
	        'support_schedule_id' => $this->support_schedule_id,
        ]);

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'description', $this->description])
	        ->andFilterWhere(['like', 'links', $this->links])
	        ->andFilterWhere([
	        	'or',
		        ['like', 'responsible.Ename', $this->responsible],
		        ['like', 'support.Ename', $this->responsible]
	        ])
            ->andFilterWhere(['like', 'notebook', $this->notebook]);

        return $dataProvider;
    }
}
