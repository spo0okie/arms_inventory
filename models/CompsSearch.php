<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use \app\helpers\QueryHelper;

/**
 * CompsSearch represents the model behind the search form of `app\models\Comps`.
 */
class CompsSearch extends Comps
{
	public $places_id;
	public $services_ids;
	public $linkedSoft_ids;
	public $ids;
	public $vm_uuid;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
			[['ids'],'each','rule'=>['integer']],
            [['id', 'domain_id','soft_ids','linkedSoft_ids','softHits_ids'], 'integer'],
            [[
				'name',
				'os',
				'raw_hw',
				'raw_soft',
				'raw_version',
				'comment',
				'updated_at',
				'arm_id',
				'ip',
				'mac',
				'places_id',
				'archived',
				'services_ids',
				'ignore_hw',
				'vm_uuid'
			], 'safe'],
			['mac', 'filter', 'filter' => function ($value) {
				$macs=explode("\n",$value);
				foreach ($macs as $i=>$mac) {
					$macs[$i]=preg_replace('/[^0-9a-f]/', '', mb_strtolower($mac));
				}
				return implode("\n",$macs);
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
				'arm.state',
				'platform',
				'domain',
				'soft',
				'softHits',
				'netIps.network.segment',
				'services.maintenanceReqs',
				'maintenanceReqs'
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
					'asc'=>['CONCAT(ifnull(techs.num,""),ifnull(platforms.name,""))'=>SORT_ASC],
					'desc'=>['CONCAT(ifnull(techs.num,""),ifnull(platforms.name,""))'=>SORT_DESC],
				],
				'services_ids'=>[
					'asc'=>['services.name'=>SORT_ASC],
					'desc'=>['services.name'=>SORT_DESC],
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
				'pagination' => ['pageSize' => Yii::$app->request->get('per-page',100),],
				'sort'=> $sort,
			]);
        }

	
		if (!$this->archived) {
			$query->andWhere(['not',['comps.archived'=>1]]);
		}

        $query
			->andFilterWhere(['comps.id'=>$this->ids])
			->andFilterWhere(QueryHelper::querySearchString('concat(IFNULL(domains.name,""),"\\\\",comps.name)', $this->name))
            ->andFilterWhere(QueryHelper::querySearchString('raw_version', $this->raw_version))
			->andFilterWhere(QueryHelper::querySearchString('comps.ip', $this->ip))
			->andFilterWhere(QueryHelper::querySearchString('comps.mac', $this->mac))
            ->andFilterWhere(QueryHelper::querySearchString([
            	'AND/OR',
				'IFNULL(techs.num,"")','IFNULL(platforms.name,"")', 'IFNULL(platforms.search_text,"")'],
				$this->arm_id
			))
			->andFilterWhere(QueryHelper::querySearchString('comment', $this->comment))
			->andFilterWhere(QueryHelper::querySearchString('comps.external_links', $this->vm_uuid))
			/*->andFilterWhere(['or',
				QueryHelper::querySearchString('services.name', $this->services_ids),
				QueryHelper::querySearchString('services.search_text',$this->services_ids),
				QueryHelper::querySearchString( 'services.description', $this->services_ids),
			])*/
			->andFilterWhere(QueryHelper::querySearchString(
				[
					'AND/OR',
					'IFNULL(services.name,"")', 'IFNULL(services.search_text,"")', 'IFNULL(services.description,"")'
				],
				$this->services_ids
			))
			->andFilterWhere(QueryHelper::querySearchString('getplacepath({{places}}.id)', $this->places_id))
			->andFilterWhere(['or',
				QueryHelper::querySearchString('os', $this->os),
				QueryHelper::querySearchString('raw_soft', $this->os),
				QueryHelper::querySearchString('raw_hw', $this->os),
			])
			->andFilterWhere(QueryHelper::querySearchString('raw_hw', $this->raw_hw))
			->andFilterWhere(QueryHelper::querySearchNumberOrDate('comps.updated_at',$this->updated_at))
			->andFilterWhere(['soft.id'=>$this->soft_ids])
			->andFilterWhere(['comps.ignore_hw'=>$this->ignore_hw])
			->andFilterWhere(['installed_soft.id'=>$this->softHits_ids])
			->andFilterWhere(['or',
				['installed_soft.id'=>$this->linkedSoft_ids],
				['soft.id'=>$this->linkedSoft_ids]
			])
		;
	
		$totalQuery=clone $query;
	
		return new ActiveDataProvider([
			'query' => $query->groupBy('comps.id'),
			'totalCount' => $totalQuery->count('distinct(comps.id)'),
			'pagination' => ['pageSize' => Yii::$app->request->get('per-page',100),],
			'sort'=> $sort,
		]);    }
}
