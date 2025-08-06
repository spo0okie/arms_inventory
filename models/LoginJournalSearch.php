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
            [['type'], 'integer'],
            [['time', 'calc_time', 'comp_name', 'user_login', 'users_id', 'comps_id'], 'safe'],
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
        ->joinWith('comp.arm');

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
            'type' => $this->type,
			'time' => $this->time,
			'calc_time' => $this->calc_time,
        ]);

        $query->andFilterWhere(['or like', 'comp_name', \yii\helpers\StringHelper::explode($this->comp_name,'|',true,true)])
            ->andFilterWhere(['or like', 'user_login', \yii\helpers\StringHelper::explode($this->user_login,'|',true,true)])
	        ->andFilterWhere(['or like', 'users.Ename', \yii\helpers\StringHelper::explode($this->users_id,'|',true,true)])
	        ->andFilterWhere(['or like', 'comps.name', \yii\helpers\StringHelper::explode($this->comps_id,'|',true,true)]);

        return $dataProvider;
    }
}
