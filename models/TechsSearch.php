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
	public $type_id;

	/**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'model_id', 'arms_id', 'places_id','type_id'], 'integer'],
            [['num', 'inv_num', 'sn', 'user_id', 'user_id', 'it_staff_id', 'ip', 'url', 'comment','model','place','mac'], 'safe'],
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
            ->joinWith(['place','model','arm.user','arm.place','model.manufacturer','techUser','state','contracts']);

        $this->load($params);
	
        if (!$this->validate()) {
            return new ActiveDataProvider([
				'query' => $query,
				'totalCount' => $query->count('distinct(techs.id)'),
				'pagination' => ['pageSize' => 100,],
			]);
        }

        $query->andFilterWhere(['like', 'techs.num', $this->num])
            ->andFilterWhere(['like', 'techs.inv_num', $this->inv_num])
	        ->andFilterWhere(['like', 'techs.sn', $this->sn])
	        ->andFilterWhere(['like', 'techs.comment', $this->comment])
            ->andFilterWhere(['or',['like', 'getplacepath(places_techs.id)', $this->place],['like', 'getplacepath(places.id)', $this->place]])
            ->andFilterWhere(['like', 'concat(manufacturers.name," ",tech_models.name)', $this->model])
            ->andFilterWhere(['like', 'techs.ip', $this->ip])
	        ->andFilterWhere(['techs.model_id'=>$this->model_id])
	        ->andFilterWhere(['tech_models.type_id'=>$this->type_id])
		    ->andFilterWhere(['like','techs.mac',$this->mac]);
	
		$totalQuery=clone $query;
	
		return new ActiveDataProvider([
			'query' => $query->groupBy('techs.id'),
			'totalCount' => $totalQuery->count('distinct(techs.id)'),
			'pagination' => ['pageSize' => 100,],
		]);
    }
}
