<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Users;

/**
 * UsersSearch represents the model behind the search form of `app\models\Users`.
 */
class UsersSearch extends Users
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['Persg', 'Uvolen', 'nosync'], 'integer'],
            [['id', 'Orgeh', 'Doljnost', 'Ename', 'Login', 'Email', 'Phone', 'Mobile', 'work_phone', 'Bday', 'manager_id', 'employee_id'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
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
        $query = Users::find();

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
            //'id' => $this->id,
            'Persg' => $this->Persg,
            'Uvolen' => $this->Uvolen,
            'nosync' => $this->nosync,
        ]);

        $query->andFilterWhere(['like', 'id', $this->id])
	        ->andFilterWhere(['like', 'employee_id', $this->employee_id])
	        ->andFilterWhere(['like', 'Orgeh', $this->Orgeh])
            ->andFilterWhere(['like', 'Doljnost', $this->Doljnost])
            ->andFilterWhere(['like', 'Ename', $this->Ename])
            ->andFilterWhere(['like', 'Login', $this->Login])
            ->andFilterWhere(['like', 'Email', $this->Email])
            ->andFilterWhere(['like', 'Phone', $this->Phone])
            ->andFilterWhere(['like', 'Mobile', $this->Mobile])
            ->andFilterWhere(['like', 'work_phone', $this->work_phone])
            ->andFilterWhere(['like', 'Bday', $this->Bday])
            ->andFilterWhere(['like', 'manager_id', $this->manager_id]);

        return $dataProvider;
    }
}
