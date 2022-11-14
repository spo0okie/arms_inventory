<?php

namespace app\models;

use app\helpers\QueryHelper;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Services;

/**
 * ServicesSearch represents the model behind the search form of `app\models\Services`.
 */
class ServicesSearch extends Services
{
	public $sites;
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
	        [['id', 'is_end_user', 'responsible_id', 'providing_schedule_id', 'support_schedule_id'], 'integer'],
			[['responsible_ids'], 'each', 'rule'=>['integer']],
            [['name', 'description', 'segment', 'sites','responsible', 'providingSchedule', 'supportSchedule','directlySupported'], 'safe'],
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
			->join('LEFT JOIN','segments','segments.id=getServiceSegment(services.id)')
			->joinWith([
			//->with([
				'support',
				'comps',
				'arms',
				'segment',
				'orgPhones',
				'place',
				'armPlaces',
				'techPlaces',
				'inetsPlaces',
				'phonesPlaces',
				'responsible',
				'supportSchedule',
				'providingSchedule',
				'orgPhones',
				'orgInets',
				'support'
			],true);
		
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
            'id' => $this->id,
            'is_end_user' => $this->is_end_user,
	        'responsible_id' => $this->responsible_id,
	        'providing_schedule_id' => $this->providing_schedule_id,
	        'support_schedule_id' => $this->support_schedule_id,
        ]);
        
        $query
			->andFilterWhere(['or',
				QueryHelper::querySearchString('services.name', $this->name),
				QueryHelper::querySearchString('services.search_text',$this->name)
			])
            ->andFilterWhere(QueryHelper::querySearchString( 'description', $this->description))
			->andFilterWhere(QueryHelper::querySearchString('segments.name', $this->segment))
			->andFilterWhere(QueryHelper::querySearchString('providing_schedule.name', $this->providingSchedule))
			->andFilterWhere(QueryHelper::querySearchString('support_schedule.name', $this->supportSchedule))
	        ->andFilterWhere([
	        	'or',
					QueryHelper::querySearchString('getplacepath(places_in_svc_arms.id)', $this->sites),
					QueryHelper::querySearchString('getplacepath(places_in_svc_techs.id)', $this->sites)
	        ])
            ->andFilterWhere(QueryHelper::querySearchString( 'notebook', $this->notebook,'|',true,true))
			->andFilterWhere(QueryHelper::querySearchString(['or','responsible.Ename','support.Ename'], $this->responsible));
	
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
