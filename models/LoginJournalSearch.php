<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\LoginJournal;

/**
 * LoginJournalSearch represents the model behind the search form of `app\models\LoginJournal`.
 */
class LoginJournalSearch extends LoginJournal
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'comps_id'], 'integer'],
            [['time', 'comp_name', 'user_login', 'users_id'], 'safe'],
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
        $query = LoginJournal::find()
        ->joinWith('user')
        ->joinWith('comp');

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
	        'sort'=> ['defaultOrder' => ['id'=>SORT_DESC]],
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
            'time' => $this->time,
        ]);

        $query->andFilterWhere(['like', 'comp_name', $this->comp_name])
            ->andFilterWhere(['like', 'user_login', $this->user_login])
	        ->andFilterWhere(['like', 'users.Ename', $this->users_id])
	        ->andFilterWhere(['like', 'comps.name', $this->comps_id]);

        return $dataProvider;
    }
}
