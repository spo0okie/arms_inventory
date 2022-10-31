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
            [['id', 'domain_id'], 'integer'],
            [['name', 'os', 'raw_hw', 'raw_soft', 'raw_version', 'comment', 'updated_at', 'arm_id','ip','mac','places_id','archived'], 'safe'],
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
            var_dump($this->errors);
        }

	
		if (!$this->archived) {
			$query->andWhere(['not',['comps.archived'=>1]]);
		}

        $query
			->andFilterWhere(\app\helpers\QueryHelper::querySearchString('concat(IFNULL(domains.name,""),"\\\\",comps.name)', $this->name))
            ->andFilterWhere(\app\helpers\QueryHelper::querySearchString('raw_version', $this->raw_version))
			->andFilterWhere(\app\helpers\QueryHelper::querySearchString('comps.ip', $this->ip))
			->andFilterWhere(\app\helpers\QueryHelper::querySearchString('comps.mac', $this->mac))
            ->andFilterWhere(\app\helpers\QueryHelper::querySearchString('arms.num', $this->arm_id))
			->andFilterWhere(\app\helpers\QueryHelper::querySearchString('comment', $this->comment))
			->andFilterWhere(\app\helpers\QueryHelper::querySearchString('getplacepath({{places}}.id)', $this->places_id))
			->andFilterWhere(['or',
				\app\helpers\QueryHelper::querySearchString('os', $this->os),
				\app\helpers\QueryHelper::querySearchString('raw_soft', $this->os),
				\app\helpers\QueryHelper::querySearchString('raw_hw', $this->os),
			])
			->andFilterWhere(['or like', 'raw_hw', \yii\helpers\StringHelper::explode($this->raw_hw,'|',true,true)]);

		if (strlen($this->updated_at)) {
			if (substr($this->updated_at,0,1)=='>') {
				$query->andFilterWhere(['>', 'comps.updated_at', substr($this->updated_at,1)]);
			} elseif (substr($this->updated_at,0,1)=='<') {
				$query->andFilterWhere(['<', 'comps.updated_at', substr($this->updated_at,1)]);
			} else
				$query->andFilterWhere([
					'or like',
					'comps.updated_at',
					\yii\helpers\StringHelper::explode($this->updated_at,'|',true,true)
				]);

		}
        return $dataProvider;
    }
}
