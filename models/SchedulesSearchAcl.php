<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\StringHelper;

/**
 * SchedulesSearch represents the model behind the search form of `\app\models\Schedules`.
 */
class SchedulesSearchAcl extends Schedules
{
	
	public $objects;
	public $resources;
	public $aclPartners;
	public $accessTypes;
	public $acePartners;
	public $aceDepartments;
	
	public $archived;
	
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
			['archived','boolean'],
            [['name', 'comment', 'created_at','objects','resources','aclPartners','accessTypes','acePartners','aceDepartments'], 'safe'],
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
        $query = Schedules::find()
		->joinWith([
			'acls.aces.comps',
			'acls.aces.users.org',
			'acls.aces.users.orgStruct',
			'acls.aces.netIps',
			'acls.aces.networks',
			'acls.aces.accessTypes',
			'acls.aces.services',
			'acls.comp',
			'acls.tech',
			'acls.service.orgInets',
			'acls.service.orgPhones',
			'acls.ip',
			'acls.network',
			'periods'
		])
		->where('acls.id')
		->andWhere(['schedules.override_id'=>null]);

        // add conditions that should always apply here

        $this->load($params);

        if (!$this->validate()) {
			//$totalQuery=clone $query;
            return new ActiveDataProvider([
				'query' => $query,
				//'totalCount' => $totalQuery->count('distinct(schedules.id)'),
				'pagination' => false,
				'sort'=> ['defaultOrder' => ['id'=>SORT_DESC]]
			]);
        }
	
		if (!$this->archived??false) {
			$query->andWhere([
				'exists',
				(new \yii\db\Query())
					->from('schedules_entries sp1')
					->where('sp1.schedule_id = schedules.id')
					->andWhere(['sp1.is_work' => 1])
					->andWhere(['<=', 'sp1.date', date('Y-m-d')])
					->andWhere(['>=', 'sp1.date_end', date('Y-m-d')])
			]);
			
			$query->andWhere([
				'not exists',
				(new \yii\db\Query())
					->from('schedules_entries sp2')
					->where('sp2.schedule_id = schedules.id')
					->andWhere(['sp2.is_work' => 0])
					->andWhere(['<=', 'sp2.date', date('Y-m-d')])
					->andWhere(['>=', 'sp2.date_end',date('Y-m-d')])
			]);
		}
	
		$query->andFilterWhere(['or like', 'CONCAT(IFNULL(schedules.name,""),IFNULL(schedules.description,""),IFNULL(schedules.history,""))', StringHelper::explode($this->name,'|',true,true)]);
	
		$query->andFilterWhere(['or like', 'CONCAT(partners.bname," ",partners.uname)', StringHelper::explode($this->acePartners,'|',true,true)]);
		
		$query->andFilterWhere(['or like', 'org_struct.name', StringHelper::explode($this->aceDepartments,'|',true,true)]);

		$query->andFilterWhere(['or like', 'access_types.name', StringHelper::explode($this->accessTypes,'|',true,true)]);
	
		$query->andFilterWhere(['or',
			['or like', 'comps_subjects.name', StringHelper::explode($this->objects,'|',true,true)],
			['or like', 'users_subjects.Ename', StringHelper::explode($this->objects,'|',true,true)],
			['or like', 'aces.ips', StringHelper::explode($this->objects,'|',true,true)],
			['or like', 'aces.comment', StringHelper::explode($this->objects,'|',true,true)],
		]);
	
		$query->andFilterWhere(['or',
			['or like', 'comps_resources.name', StringHelper::explode($this->resources,'|',true,true)],
			['or like', 'services_resources.name', StringHelper::explode($this->resources,'|',true,true)],
			['or like', 'techs_resources.num', StringHelper::explode($this->resources,'|',true,true)],
			['or like', 'ips_resources.text_addr', StringHelper::explode($this->resources,'|',true,true)],
			['or like', 'acls.comment', StringHelper::explode($this->resources,'|',true,true)],
		]);
	
		//$totalQuery=clone $query;
		return new ActiveDataProvider([
			'query' => $query,
			//'totalCount' => $totalQuery->count('distinct(schedules.id)'),
			'pagination' => false,
			'sort'=> ['defaultOrder' => ['id'=>SORT_DESC]]
		]);
    }
}
