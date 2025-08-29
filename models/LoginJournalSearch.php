<?php

namespace app\models;

use app\helpers\QueryHelper;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\LoginJournal;
use yii\helpers\StringHelper;

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
	        'sort'=> ['defaultOrder' => ['calc_time'=>SORT_DESC,'id'=>SORT_DESC]],
		    'pagination' => ['pageSize' => 100,],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere(['type' => $this->type,]);
		$query->andFilterWhere(QueryHelper::querySearchNumberOrDate('time',$this->time));
		$query->andFilterWhere(QueryHelper::querySearchNumberOrDate('calc_time',$this->calc_time));
		$query->andFilterWhere(QueryHelper::querySearchString('comp_name',$this->comp_name));
		$query->andFilterWhere(QueryHelper::querySearchString('user_login',$this->user_login));
		$query->andFilterWhere(QueryHelper::querySearchString('users.Ename',$this->users_id));
		$query->andFilterWhere(QueryHelper::querySearchString('comps.name',$this->comps_id));
		
        return $dataProvider;
    }
}
