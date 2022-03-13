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
			//->joinWith('techs')
			->joinWith([
				'comps.arm.place',
				'techs.place',
				'support',
				'responsible',
				'supportSchedule',
				'providingSchedule',
				'orgPhones',
				'orgInets',
			])
			->join('LEFT JOIN','segments','segments.id=getServiceSegment(services.id)');
	
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
        
        $query
			->andFilterWhere(['or like', 'services.name', \yii\helpers\StringHelper::explode($this->name,'|',true,true)])
            ->andFilterWhere(['or like', 'description', \yii\helpers\StringHelper::explode($this->description,'|',true,true)])
			->andFilterWhere(['or like', 'segments.name', \yii\helpers\StringHelper::explode($this->segment,'|',true,true)])
			->andFilterWhere(['or like', 'providing_schedule.name', \yii\helpers\StringHelper::explode($this->providingSchedule,'|',true,true)])
			->andFilterWhere(['or like', 'support_schedule.name', \yii\helpers\StringHelper::explode($this->supportSchedule,'|',true,true)])
	        ->andFilterWhere([
	        	'or',
		        ['or like', 'getplacepath(places.id)', \yii\helpers\StringHelper::explode($this->sites,'|',true,true)],
		        ['or like', 'getplacepath(places_techs.id)', \yii\helpers\StringHelper::explode($this->sites,'|',true,true)]
	        ])
            ->andFilterWhere(['like', 'notebook', \yii\helpers\StringHelper::explode($this->notebook,'|',true,true)])
			->andFilterWhere([
				'or',
				['or like', 'responsible.Ename', \yii\helpers\StringHelper::explode($this->responsible,'|',true,true)],
				['or like', 'support.Ename', \yii\helpers\StringHelper::explode($this->responsible,'|',true,true)]
			]);
	
		if (is_array($this->responsible_ids)) {
			$query->andWhere([
				'or',
				['responsible.id'=> $this->responsible_ids],
				['support.id'=> $this->responsible_ids]
			]);
		
		}
	
		return $dataProvider;
    }
}
