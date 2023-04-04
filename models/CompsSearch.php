<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Comps;
use \app\helpers\QueryHelper;

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
		ManufacturersDict::initCache();
        $query = Comps::find()
			->joinWith([
				'arm.place',
				'arm.user',
				'domain',
				"netIps.network"
			]);

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

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            //var_dump($this->errors);
			return new ActiveDataProvider([
				'query' => $query,
				'totalCount' => $query->count('distinct(techs.id)'),
				'pagination' => ['pageSize' => \Yii::$app->request->get('per-page',100),],
				'sort'=> $sort,
			]);
        }

	
		if (!$this->archived) {
			$query->andWhere(['not',['comps.archived'=>1]]);
		}

        $query
			->andFilterWhere(QueryHelper::querySearchString('concat(IFNULL(domains.name,""),"\\\\",comps.name)', $this->name))
            ->andFilterWhere(QueryHelper::querySearchString('raw_version', $this->raw_version))
			->andFilterWhere(QueryHelper::querySearchString('comps.ip', $this->ip))
			->andFilterWhere(QueryHelper::querySearchString('comps.mac', $this->mac))
            ->andFilterWhere(QueryHelper::querySearchString('arms.num', $this->arm_id))
			->andFilterWhere(QueryHelper::querySearchString('comment', $this->comment))
			->andFilterWhere(QueryHelper::querySearchString('getplacepath({{places}}.id)', $this->places_id))
			->andFilterWhere(['or',
				QueryHelper::querySearchString('os', $this->os),
				QueryHelper::querySearchString('raw_soft', $this->os),
				QueryHelper::querySearchString('raw_hw', $this->os),
			])
			->andFilterWhere(QueryHelper::querySearchString('raw_hw', $this->raw_hw))
			->andFilterWhere(QueryHelper::querySearchNumberOrDate('comps.updated_at',$this->updated_at));
	
		$totalQuery=clone $query;
	
		return new ActiveDataProvider([
			'query' => $query->groupBy('comps.id'),
			'totalCount' => $totalQuery->count('distinct(comps.id)'),
			'pagination' => ['pageSize' => \Yii::$app->request->get('per-page',100),],
			'sort'=> $sort,
		]);    }
}
