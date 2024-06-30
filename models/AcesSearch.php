<?php

namespace app\models;

use app\helpers\QueryHelper;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * AcesSearch represents the model behind the search form of `app\models\Aces`.
 */
class AcesSearch extends Aces
{
	public $ids;
	public $subjects;
	public $resource;
	public $access_types;
	public $services_subject_ids;
	public $services_resource_ids;
	
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [[
            	'ips',
				'comment',
				'access_types',
				'resource',
				'subjects',
				'name',
				'services_subject_ids',
				'services_resource_ids'
			], 'safe'],
			[['ids'],'each','rule'=>['integer']]
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
        $query = Aces::find()
		->joinWith([
			'users',
			'comps',
			'networks',
			'services',
			'netIps',
			'acl.service',
			'acl.comp',
			'acl.tech',
			'acl.ip',
			'acl.network',
			'accessTypes'
		]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
	
		//если ИД указаны, то ограничиваем
		if (isset($this->ids) && is_array($this->ids)) {
			if (count($this->ids))
				$query->andFilterWhere(['aces.id'=>$this->ids]);
			else //если они пустые, то блокируем дальнейший поиск
				$query->where('0=1');
		}

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
			'services_subjects.id' => $this->services_subject_ids,
			'services_resources.id' => $this->services_resource_ids,
            'updated_at' => $this->updated_at,
        ]);

        $query
			->andFilterWhere(['or',
				QueryHelper::querySearchString('users_subjects.Ename', $this->subjects),
				QueryHelper::querySearchString('comps_subjects.name', $this->subjects),
				QueryHelper::querySearchString('services_subjects.name', $this->subjects),
				QueryHelper::querySearchString('networks_subjects.text_addr', $this->subjects),
				QueryHelper::querySearchString('ips_subjects.text_addr', $this->subjects),
			])
			->andFilterWhere(['or',
				QueryHelper::querySearchString('techs_resources.num', $this->resource),
				QueryHelper::querySearchString('comps_resources.name', $this->resource),
				QueryHelper::querySearchString('services_resources.name', $this->resource),
				QueryHelper::querySearchString('networks_resources.text_addr', $this->resource),
				QueryHelper::querySearchString('ips_resources.text_addr', $this->resource),
			])
			->andFilterWhere(QueryHelper::querySearchString('aces.name', $this->name))
			->andFilterWhere(QueryHelper::querySearchString('access_types.name', $this->access_types));

        return $dataProvider;
    }
}
