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
	public $places_id;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'domain_id','archived'], 'integer'],
            [['name', 'os', 'raw_hw', 'raw_soft', 'raw_version', 'comment', 'updated_at', 'arm_id','ip','mac','places_id'], 'safe'],
			['mac', 'filter', 'filter' => function ($value) {
				$macs=explode("\n",$value);
				foreach ($macs as $i=>$mac) {
					$macs[$i]=preg_replace('/[^0-9a-f]/', '', mb_strtolower($mac));
				}
				return implode("\n",$macs);;
			}],
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
			->joinWith(['arm.place','domain']);

        // add conditions that should always apply here
		$sort=[
			'attributes'=>[
				//'num',
				'ip',
				'mac',
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
				'places_id'=>[
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
            //return $dataProvider;
        }

	
		if (!$this->archived) {
			$query->andWhere(['not',['comps.archived'=>1]]);
		}

        $query->andFilterWhere(['or like', 'concat(IFNULL(domains.name,""),"\\\\",comps.name)', yii\helpers\StringHelper::explode($this->name,'|',true,true)])
            ->andFilterWhere(['or like', 'raw_version', \yii\helpers\StringHelper::explode($this->raw_version,'|',true,true)])
			->andFilterWhere(['or like', 'comps.ip', \yii\helpers\StringHelper::explode($this->ip,'|',true,true)])
			->andFilterWhere(['or like', 'comps.mac', \yii\helpers\StringHelper::explode($this->mac,'|',true,true)])
            ->andFilterWhere(['or like', 'arms.num', \yii\helpers\StringHelper::explode($this->arm_id,'|',true,true)])
			->andFilterWhere(['or like', 'comment', \yii\helpers\StringHelper::explode($this->comment,'|',true,true)])
			->andFilterWhere(['or like', 'comps.updated_at', \yii\helpers\StringHelper::explode($this->updated_at,'|',true,true)])
			->andFilterWhere(['or like', 'getplacepath({{places}}.id)', \yii\helpers\StringHelper::explode($this->places_id,'|',true,true)])
			->andFilterWhere(['or',
				['or like', 'os', \yii\helpers\StringHelper::explode($this->os,'|',true,true)],
				['or like', 'raw_soft', \yii\helpers\StringHelper::explode($this->os,'|',true,true)],
				['or like', 'raw_hw', \yii\helpers\StringHelper::explode($this->os,'|',true,true)],
			]);

        return $dataProvider;
    }
}
