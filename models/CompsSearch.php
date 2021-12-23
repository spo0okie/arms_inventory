<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Comps;

/**
 * CompsSearch represents the model behind the search form of `app\models\Comps`.
 */
class CompsSearch extends Comps
{
	public $place;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'domain_id'], 'integer'],
            [['name', 'os', 'raw_hw', 'raw_soft', 'raw_version', 'comment', 'updated_at', 'arm_id','ip','place'], 'safe'],
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
        $query = Comps::find()
			->joinWith(['arm','domain']);

        // add conditions that should always apply here
		$sort=[
			'attributes'=>[
				//'num',
				'ip',
				'os',
				'name',
				'raw_version',
				'arm_id'=>[
					'asc'=>['arms.num'=>SORT_ASC],
					'desc'=>['arms.num'=>SORT_DESC],
				],
				'comp_ip'=>[
					'asc'=>['comps.ip'=>SORT_ASC],
					'desc'=>['comps.ip'=>SORT_DESC],
				],
				'user_id'=>[
					'asc'=>['users.Ename'=>SORT_ASC],
					'desc'=>['users.Ename'=>SORT_DESC],
				],
				'departments_id'=>[
					'asc'=>['org_struct.name'=>SORT_ASC],
					'desc'=>['org_struct.name'=>SORT_DESC],
				],
				'user_position'=>[
					'asc'=>['users.doljnost'=>SORT_ASC],
					'desc'=>['users.doljnost'=>SORT_DESC],
				],
				'place'=>[
					'asc'=>['getplacepath(arms.places_id)'=>SORT_ASC],
					'desc'=>['getplacepath(arms.places_id)'=>SORT_DESC],
				],
				'updated_at'
			]
		];
	
		$dataProvider = new ActiveDataProvider([
            'query' => $query,
	        'pagination' => ['pageSize' => 100,],
			'sort'=>$sort,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        /*$query->andFilterWhere([
            //'id' => $this->id,
            //'domain_id' => $this->domain_id,
            //'arm_id' => $this->arm_id,
            //'updated_at' => $this->updated_at,
        ]);*/
		

        $query->andFilterWhere(['like', 'concat(IFNULL(domains.name,""),"\\\\",comps.name)', $this->name])
            ->andFilterWhere(['like', 'raw_version', $this->raw_version])
            ->andFilterWhere(['like', 'ip', $this->ip])
            ->andFilterWhere(['like', 'arms.num', $this->arm_id])
            ->andFilterWhere(['like', 'comment', $this->comment]);

		if (strlen($this->os)) {
			if (is_array($arrOs=explode('|',$this->os))){
				$query->andFilterWhere(['or',
					['or like', 'os', $arrOs],
					['or like', 'raw_soft', $arrOs],
					['or like', 'raw_hw', $arrOs],
				]);
			}
		} else {
			$query->andFilterWhere(['or',
				['like', 'os', $this->os],
				['like', 'raw_soft', $this->os],
				['like', 'raw_hw', $this->os],
			]);
		}

        return $dataProvider;
    }
}
