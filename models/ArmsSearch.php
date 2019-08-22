<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Arms;

/**
 * ArmsSearch represents the model behind the search form of `\app\models\Arms`.
 */
class ArmsSearch extends Arms
{
	public $comp_ip;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['num', 'model_id', 'sn', 'user_id', 'places_id', 'comp_ip', 'comp_id'], 'safe'],
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
	    $query = new \yii\db\Query();

        $query = Arms::find()
	        ->joinWith(['user','techModel','comp','place']);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
	        'pagination' => ['pageSize' => 100,],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        //$query->select(['getplacepath({{places}}.id) AS path']);

        $query->andFilterWhere(['like', 'num', $this->num])
            ->andFilterWhere(['like', 'model', $this->model])
            ->andFilterWhere(['like', 'sn', $this->sn])
	        ->andFilterWhere(['like', 'users.Ename', $this->user_id])
	        ->andFilterWhere(['like', 'comps.ip', $this->comp_ip])
	        ->andFilterWhere(['like', 'comps.name', $this->comp_id])
	        ->andFilterWhere(['like', 'arms_models.name', $this->model_id])
	        ->andFilterWhere(['like', 'getplacepath({{places}}.id)', $this->places_id])
		    ->andFilterWhere(['like', 'comment', $this->comment]);

        return $dataProvider;
    }
}
