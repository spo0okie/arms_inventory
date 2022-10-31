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
	public $comp_mac;
	public $model_name;
	public $type_id;
	public $inv_sn;
	public $user_position;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
			[['state_id'],'each','rule'=>['integer']],
            [[
            	'num',
				'inv_num',
				'inv_sn',
				'model_id',
				'sn',
				'user_id',
				'places_id',
				'departments_id',
				'comp_ip',
				'comp_mac',
				'comp_id',
				'model_id',
				'model_name',
				'type_id',
				'model_id',
				'user_position',
				'comp_hw'
			], 'safe'],
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
	        ->joinWith(['user.orgStruct','techModel.manufacturer','comp.netIps','place','contracts','licItems','licGroups','licKeys','department']);

        $this->load($params);
		
        $sort=[
			//'defaultOrder' => ['num'=>SORT_ASC],
			'attributes'=>[
				'num',
				'sn',
				'state_id',
				'inv_num',
				'comp_id'=>[
					'asc'=>['comps.name'=>SORT_ASC],
					'desc'=>['comps.name'=>SORT_DESC],
				],
				'comp_ip'=>[
					'asc'=>['comps.ip'=>SORT_ASC],
					'desc'=>['comps.ip'=>SORT_DESC],
				],
				'comp_mac'=>[
					'asc'=>['comps.mac'=>SORT_ASC],
					'desc'=>['comps.mac'=>SORT_DESC],
				],
				'user_id'=>[
					'asc'=>['users.Ename'=>SORT_ASC],
					'desc'=>['users.Ename'=>SORT_DESC],
				],
				'model_name'=>[
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
				'inv_sn'=>[
					'asc'=>['concat(sn,inv_num)'=>SORT_ASC],
					'desc'=>['concat(sn,inv_num)'=>SORT_DESC],
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

        $query
			->andFilterWhere(\app\helpers\QueryHelper::querySearchString('num', $this->num))
	        ->andFilterWhere(\app\helpers\QueryHelper::querySearchString('inv_num', $this->inv_num))
			->andFilterWhere(\app\helpers\QueryHelper::querySearchString('sn', $this->sn))
			->andFilterWhere(\app\helpers\QueryHelper::querySearchString('concat(sn, ", ", inv_num)', $this->inv_sn))
	        ->andFilterWhere(\app\helpers\QueryHelper::querySearchString('users.Ename', $this->user_id))
	        ->andFilterWhere(\app\helpers\QueryHelper::querySearchString('users.Doljnost', $this->user_position))
			->andFilterWhere(\app\helpers\QueryHelper::querySearchString('comps.ip', $this->comp_ip))
			->andFilterWhere(\app\helpers\QueryHelper::querySearchString('comps.mac', $this->comp_mac))
	        ->andFilterWhere(\app\helpers\QueryHelper::querySearchString('comps.name', $this->comp_id))
			//->andFilterWhere(['or like', 'comps.raw_hw', \yii\helpers\StringHelper::explode($this->comp_hw,'|',true,true)])
			->andFilterWhere(\app\helpers\QueryHelper::querySearchString('comps.raw_hw',$this->comp_hw))
	        ->andFilterWhere(\app\helpers\QueryHelper::querySearchString('org_struct.name',$this->departments_id))
			->andFilterWhere(\app\helpers\QueryHelper::querySearchString('model_id', $this->model_id))
			->andFilterWhere(\app\helpers\QueryHelper::querySearchString('concat(manufacturers.name," ",arms_models.name)',$this->model_name))
	        ->andFilterWhere(\app\helpers\QueryHelper::querySearchString('getplacepath({{places}}.id)', $this->places_id))
	        ->andFilterWhere(['arms_models.type_id'=>$this->type_id])
			->andFilterWhere(['arms.state_id'=>$this->state_id])
		    ->andFilterWhere(\app\helpers\QueryHelper::querySearchString('comment', $this->comment));

		$totalQuery=clone $query;
	
		return new ActiveDataProvider([
			'query' => $query->groupBy('arms.id'),
			'totalCount' => $totalQuery->count('distinct(arms.id)'),
			'pagination' => ['pageSize' => 100,],
			'sort'=> $sort,
		]);
    }
}
