<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\StringHelper;

/**
 * SchedulesSearch represents the model behind the search form of `\app\models\Schedules`.
 */
class SchedulesAclSearch extends Schedules
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
    public function search($params,$columns=null)
    {
		$query = Schedules::find()
			->with([
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
		
		$filter = Schedules::find()
			->select('DISTINCT(schedules.id)')
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
		
		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'sort'=> ['defaultOrder' => ['id'=>SORT_DESC]]
		]);
		
        if (!$this->validate()) {
			return $dataProvider;
        }
	
		if (!$this->archived??false) {
			$filter->andWhere([
				'exists',
				(new \yii\db\Query())
					->from('schedules_entries sp1')
					->where('sp1.schedule_id = schedules.id')
					->andWhere(['sp1.is_work' => 1])				//период рабочий
					->andWhere(['or',
						['sp1.date'=>null],							//начала нет
						['<=', 'sp1.date', date('Y-m-d')]	//или оно раньше чем сейчас
					])
					->andWhere(['or',
						['sp1.date_end'=>null],							//конца нет
						['>=', 'sp1.date_end', date('Y-m-d')]	//или он позже чем сейчас
					])
			]);
			
			$filter->andWhere([
				'not exists',
				(new \yii\db\Query())
					->from('schedules_entries sp2')
					->where('sp2.schedule_id = schedules.id')
					->andWhere(['sp2.is_work' => 0])				//период нерабочий
					->andWhere(['or',
						['sp2.date'=>null],							//начала нет
						['<=', 'sp2.date', date('Y-m-d')]	//или оно раньше чем сейчас
					])
					->andWhere(['or',
						['sp2.date_end'=>null],							//конца нет
						['>=', 'sp2.date_end', date('Y-m-d')]	//или он позже чем сейчас
					])
			]);
		}
		
		$filter->andFilterWhere(['or like', 'CONCAT(IFNULL(schedules.name,""),IFNULL(schedules.description,""),IFNULL(schedules.history,""))', StringHelper::explode($this->name,'|',true,true)]);
		
		$filter->andFilterWhere(['or like', 'CONCAT(partners.bname," ",partners.uname)', StringHelper::explode($this->acePartners,'|',true,true)]);
		
		$filter->andFilterWhere(['or like', 'org_struct.name', StringHelper::explode($this->aceDepartments,'|',true,true)]);
		
		$filter->andFilterWhere(['or like', 'access_types.name', StringHelper::explode($this->accessTypes,'|',true,true)]);
		
		$filter->andFilterWhere(['or',
			['or like', 'comps_subjects.name', StringHelper::explode($this->objects,'|',true,true)],
			['or like', 'users_subjects.Ename', StringHelper::explode($this->objects,'|',true,true)],
			['or like', 'aces.ips', StringHelper::explode($this->objects,'|',true,true)],
			['or like', 'aces.comment', StringHelper::explode($this->objects,'|',true,true)],
		]);
		
		$filter->andFilterWhere(['or',
			['or like', 'comps_resources.name', StringHelper::explode($this->resources,'|',true,true)],
			['or like', 'services_resources.name', StringHelper::explode($this->resources,'|',true,true)],
			['or like', 'techs_resources.num', StringHelper::explode($this->resources,'|',true,true)],
			['or like', 'ips_resources.text_addr', StringHelper::explode($this->resources,'|',true,true)],
			['or like', 'acls.comment', StringHelper::explode($this->resources,'|',true,true)],
		]);
		
		if ($filter->where) {
			//фильтруем запрос данных по ID из фильтра, который мы только что получили при помощи разных WHERE
			$query->where('schedules.id in ('.$filter->createCommand()->rawSql.')');
		}
		
		//$totalQuery=clone $query;
		return $dataProvider;
    }
}
