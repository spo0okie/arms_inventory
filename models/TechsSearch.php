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

        $query->andFilterWhere(['or like', 'techs.num', \yii\helpers\StringHelper::explode($this->num,'|',true,true)])
            ->andFilterWhere(['or like', 'techs.inv_num', \yii\helpers\StringHelper::explode($this->inv_num,'|',true,true)])
	        ->andFilterWhere(['or like', 'techs.sn', \yii\helpers\StringHelper::explode($this->sn,'|',true,true)])
	        ->andFilterWhere(['or like', 'techs.comment', \yii\helpers\StringHelper::explode($this->comment,'|',true,true)])
            ->andFilterWhere(['or',
				['or like', 'getplacepath(places_techs.id)', \yii\helpers\StringHelper::explode($this->place,'|',true,true)],
				['or like', 'getplacepath(places.id)', \yii\helpers\StringHelper::explode($this->place,'|',true,true)]
			])
            ->andFilterWhere(['or like', 'concat(manufacturers.name," ",tech_models.name)', \yii\helpers\StringHelper::explode($this->model,'|',true,true)])
            ->andFilterWhere(['or like', 'techs.ip', \yii\helpers\StringHelper::explode($this->ip,'|',true,true)])
		    ->andFilterWhere(['or like','techs.mac',\yii\helpers\StringHelper::explode($this->mac,'|',true,true)])
			->andFilterWhere(['techs.model_id'=>$this->model_id])
			->andFilterWhere(['tech_models.type_id'=>$this->type_id]);
	
		$totalQuery=clone $query;
	
		return new ActiveDataProvider([
			'query' => $query->groupBy('techs.id'),
			'totalCount' => $totalQuery->count('distinct(techs.id)'),
			'pagination' => ['pageSize' => 100,],
		]);
    }
}
