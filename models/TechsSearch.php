<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Techs;

/**
 * TechsSearch represents the model behind the search form of `app\models\Techs`.
 */
class TechsSearch extends Techs
{

	public $model;
	public $place;

	/**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'model_id', 'arms_id', 'places_id'], 'integer'],
            [['num', 'inv_num', 'sn', 'user_id', 'it_staff_id', 'ip', 'url', 'comment','model','place'], 'safe'],
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
        $query = Techs::find()
            ->joinWith(['place','model','arm','arm.place','model.manufacturer']);

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

        $query->andFilterWhere(['like', 'techs.num', $this->num])
            ->andFilterWhere(['like', 'techs.inv_num', $this->inv_num])
            ->andFilterWhere(['like', 'techs.sn', $this->sn])
            ->andFilterWhere(['or',['like', 'getplacepath(places_techs.id)', $this->place],['like', 'getplacepath(places.id)', $this->place]])
            ->andFilterWhere(['like', 'concat(manufacturers.name," ",tech_models.name)', $this->model])
            ->andFilterWhere(['like', 'techs.ip', $this->ip]);
            //->andFilterWhere(['like', 'url', $this->url])
            //->andFilterWhere(['like', 'comment', $this->comment]);

        return $dataProvider;
    }
}
