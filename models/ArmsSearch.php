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
	public $comp_hw;
	public $comp_ip;
	public $type_id;
	public $user_position;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['num', 'inv_num', 'model_id', 'sn', 'user_id', 'places_id','departments_id', 'comp_ip', 'comp_id','model_id','type_id','model','user_position','comp_hw'], 'safe'],
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
        $query = Arms::find()
	        ->joinWith(['user.orgStruct','techModel','comp.netIps','place','contracts','licItems','licGroups','licKeys','department']);

        $this->load($params);
		
        $sort=[
			//'defaultOrder' => ['num'=>SORT_ASC],
			'attributes'=>[
				'num',
				'sn',
				'state',
				'inv_num',
				'comp_id'=>[
					'asc'=>['comps.name'=>SORT_ASC],
					'desc'=>['comps.name'=>SORT_DESC],
				],
				'comp_ip'=>[
					'asc'=>['comps.ip'=>SORT_ASC],
					'desc'=>['comps.ip'=>SORT_DESC],
				],
				'user_id'=>[
					'asc'=>['users.Ename'=>SORT_ASC],
					'desc'=>['users.Ename'=>SORT_DESC],
				],
				'model'=>[
					'asc'=>['arms_models.name'=>SORT_ASC],
					'desc'=>['arms_models.name'=>SORT_DESC],
				],
				'departments_id'=>[
					'asc'=>['org_struct.name'=>SORT_ASC],
					'desc'=>['org_struct.name'=>SORT_DESC],
				],
				'user_position'=>[
					'asc'=>['users.doljnost'=>SORT_ASC],
					'desc'=>['users.doljnost'=>SORT_DESC],
				],
				'places_id'=>[
					'asc'=>['getplacepath(arms.places_id)'=>SORT_ASC],
					'desc'=>['getplacepath(arms.places_id)'=>SORT_DESC],
				],
			]
		];
        
        if (!$this->validate()) {
        	return new ActiveDataProvider([
				'query' => $query,
				'totalCount' => $query->count('distinct(arms.id)'),
				'pagination' => ['pageSize' => 100,],
				'sort'=> $sort,
			]);
        }

        $query->andFilterWhere(['like', 'num', $this->num])
	        ->andFilterWhere(['like', 'inv_num', $this->inv_num])
            ->andFilterWhere(['like', 'sn', $this->sn])
	        ->andFilterWhere(['like', 'users.Ename', $this->user_id])
	        ->andFilterWhere(['like', 'users.Doljnost', $this->user_position])
	        ->andFilterWhere(['like', 'comps.ip', $this->comp_ip])
	        ->andFilterWhere(['like', 'comps.name', $this->comp_id])
	        ->andFilterWhere(['like', 'comps.raw_hw', $this->comp_hw])
	        ->andFilterWhere(['like', 'org_struct.name', $this->departments_id])
	        ->andFilterWhere(['like', 'arms_models.name', $this->model])
	        ->andFilterWhere(['like', 'getplacepath({{places}}.id)', $this->places_id])
	        ->andFilterWhere(['arms.model_id'=>$this->model_id])
	        ->andFilterWhere(['arms_models.type_id'=>$this->type_id])
		    ->andFilterWhere(['like', 'comment', $this->comment]);
	
		$totalQuery=clone $query;
	
		return new ActiveDataProvider([
			'query' => $query->groupBy('arms.id'),
			'totalCount' => $totalQuery->count('distinct(arms.id)'),
			'pagination' => ['pageSize' => 100,],
			'sort'=> $sort,
		]);
    }
}
