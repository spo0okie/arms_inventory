<?php

namespace app\models;

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
			//->joinWith('techs')
			->joinWith([
				'comps.arm.place',
				'techs.place',
				'segment',
				'support',
				'responsible',
				'supportSchedule',
				'providingSchedule',
			]);
	
		if ($this->parent_id===false) {
			$query->andWhere(['services.parent_id'=>null]);
		}

		if ($this->archived===false) {
			$query->andWhere(['services.archived'=>0]);
		}
	
	
		$dataProvider = new ActiveDataProvider([
            'query' => $query,
	        'totalCount' => $query->count('distinct(services.id)'),
	        'pagination' => ['pageSize' => 500,],
	        'sort'=> ['defaultOrder' => ['name'=>SORT_ASC]]
        ]);
	
        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
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
        
        $query->andFilterWhere(['like', 'services.name', $this->name])
            ->andFilterWhere(['like', 'description', $this->description])
			->andFilterWhere(['like', 'segments.name', $this->segment])
			->andFilterWhere(['like', 'providing_schedule.name', $this->providingSchedule])
			->andFilterWhere(['like', 'support_schedule.name', $this->supportSchedule])
	        ->andFilterWhere([
	        	'or',
		        ['like', 'responsible.Ename', $this->responsible],
		        ['like', 'support.Ename', $this->responsible]
	        ])
	        ->andFilterWhere([
	        	'or',
		        ['like', 'getplacepath(places.id)', $this->sites],
		        ['like', 'getplacepath(places_techs.id)', $this->sites]
	        ])
            ->andFilterWhere(['like', 'notebook', $this->notebook]);
		
      
        return $dataProvider;
    }
}
