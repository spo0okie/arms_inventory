<?php

namespace app\models;

use app\helpers\QueryHelper;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * ServicesSearch represents the model behind the search form of `app\models\Services`.
 */
class ServicesSearch extends Services
{
	public $ids;
	public $sites;
	public $comps;
	public $techs;
	public $tags;
	public $compsAndTechs;
	public $maintenanceReqs;
	public $maintenanceJobs;
	public $segment;
	public $responsible;
	public $responsible_ids;
	public $supportSchedule;
	public $providingSchedule;
	public $directlySupported; //поддержка объявлена явно для этого сервиса

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
	        [['is_end_user', 'responsible_id', 'providing_schedule_id', 'support_schedule_id'], 'integer'],
			[['responsible_ids'], 'each', 'rule'=>['integer']],
			['id','validateIntegerOrArrayOfInteger'],
            [[
            	'name',
				'description',
				'segment',
				'sites',
				'responsible',
				'providingSchedule',
				'supportSchedule',
				'directlySupported',
				'comps',
				'techs',
				'compsAndTechs',
				'maintenanceJobs',
				'maintenanceReqs',
				'weight',
				'tags',
				'updated_at'
			], 'safe'],
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
        $query = Services::find()
			->joinWith([
			//->with([
				'acls',
				'segment',
				'orgPhones',
				//'children', - берем из кэша (cacheAllItems)
				'depends',
				'place',
				'comps',
				'arms.state',
				'techs.state',
				'arms.place',
				'techs.place',
				'armPlaces',
				'techPlaces',
				'responsible',
				'infrastructureResponsible',
				'supportSchedule',
				'providingSchedule',
				'orgPhones.place',
				'orgInets.place',
				'contracts',
				'support',
				'infrastructureSupport',
				'maintenanceReqs',
				'tags'
			])
			->join('LEFT JOIN','segments','segments.id=getServiceSegment(services.id)');
		
		if (!$this->parent_id) {
			$query->andWhere(['services.parent_id'=>null]);
		}

		if (!$this->archived) {
			$query->andWhere(['services.archived'=>0]);
		}
	

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return new ActiveDataProvider([
				'query' => $query,
				//'totalCount' => $query->count('distinct(services.id)'),
				'pagination' => false,
				'sort'=> ['defaultOrder' => ['name'=>SORT_ASC]]
			]);
        }
        
        if ($this->directlySupported) {
        	$query->andWhere([
        		'or',
				['not',['services.responsible_id'=>null]],
				['not',['users_in_services.id'=>null]],
			]);
		}

        // grid filtering conditions
        $query->andFilterWhere([
			//'services.id' => $this->id,
			'services.id' => $this->ids,
			'services.segment_id' => $this->segment_id,
			'is_end_user' => $this->is_end_user,
	        'responsible_id' => $this->responsible_id,
	        'providing_schedule_id' => $this->providing_schedule_id,
	        'support_schedule_id' => $this->support_schedule_id,
        ]);
        
        $query
			->andFilterWhere(['or',
				QueryHelper::querySearchString('services.name', $this->name),
				QueryHelper::querySearchString('services.search_text',$this->name),
				QueryHelper::querySearchString( 'services.description', $this->name),
			])
			->andFilterWhere(QueryHelper::querySearchNumberOrDate('services.weight', $this->weight))
			->andFilterWhere(QueryHelper::querySearchString('segments.name', $this->segment))
			->andFilterWhere(QueryHelper::querySearchString('comps.name', $this->comps))
			->andFilterWhere(QueryHelper::querySearchString(['AND/OR','techs.num','techs.hostname'],$this->techs))
			->andFilterWhere(QueryHelper::querySearchString(['AND/OR','comps.name','techs.num','techs.hostname'], $this->compsAndTechs))
			->andFilterWhere(QueryHelper::querySearchString('providing_schedule.name', $this->providingSchedule))
			->andFilterWhere(QueryHelper::querySearchString('support_schedule.name', $this->supportSchedule))
			->andFilterWhere(QueryHelper::querySearchString('tags.name',$this->tags))
			->andFilterWhere(QueryHelper::querySearchNumberOrDate('services.updated_at',$this->updated_at))
	        ->andFilterWhere([
	        	'or',
					QueryHelper::querySearchString('getplacepath(places_in_svc_arms.id)', $this->sites),
					QueryHelper::querySearchString('getplacepath(places_in_svc_techs.id)', $this->sites)
	        ])
            ->andFilterWhere(QueryHelper::querySearchString( 'notebook', $this->notebook))
			->andFilterWhere(QueryHelper::querySearchString(['or',
				'responsible.Ename',
				'support.Ename',
				'infrastructure_responsible.Ename',
				'infrastructure_support.Ename',
			], $this->responsible));
	
		if (is_array($this->responsible_ids)) {
			$query->andWhere([
				'or',
				['responsible.id'=> $this->responsible_ids],
				['support.id'=> $this->responsible_ids]
			]);
		
		}
		/**/
		return new ActiveDataProvider([
			'query' => $query,
			//'totalCount' => (clone $query)->count('distinct(services.id)'),
			'pagination' => false,
			'sort'=> ['defaultOrder' => ['name'=>SORT_ASC]]
		]);
    }
}
