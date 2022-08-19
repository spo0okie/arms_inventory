<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Schedules;

/**
 * SchedulesSearch represents the model behind the search form of `\app\models\Schedules`.
 */
class SchedulesSearchAcl extends Schedules
{
	
	public $objects;
	public $resources;
	
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['name', 'comment', 'created_at','objects','resources'], 'safe'],
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
			'acls.aces.users',
			'acls.aces.netIps',
			'acls.comp',
			'acls.tech',
			'acls.service',
			'acls.ip',
		])
		->where('acls.id');

        // add conditions that should always apply here

        $this->load($params);

        if (!$this->validate()) {
			$totalQuery=clone $query;
            return new ActiveDataProvider([
				'query' => $query,
				'totalCount' => $totalQuery->count('distinct(schedules.id)'),
				'pagination' => ['pageSize' => 1000,],
			]);
        }
        

        $query->andFilterWhere(['or like', 'CONCAT(schedules.name,schedules.description,schedules.history)', \yii\helpers\StringHelper::explode($this->name,'|',true,true)]);
	
		$query->andFilterWhere(['or',
			['or like', 'comps_objects.name', \yii\helpers\StringHelper::explode($this->objects,'|',true,true)],
			['or like', 'users_objects.Ename', \yii\helpers\StringHelper::explode($this->objects,'|',true,true)],
			['or like', 'aces.ips', \yii\helpers\StringHelper::explode($this->objects,'|',true,true)],
			['or like', 'aces.comment', \yii\helpers\StringHelper::explode($this->objects,'|',true,true)],
		]);

		$query->andFilterWhere(['or',
			['or like', 'comps_resources.name', \yii\helpers\StringHelper::explode($this->resources,'|',true,true)],
			['or like', 'services_resources.name', \yii\helpers\StringHelper::explode($this->resources,'|',true,true)],
			['or like', 'techs_resources.num', \yii\helpers\StringHelper::explode($this->resources,'|',true,true)],
			['or like', 'ips_resources.text_addr', \yii\helpers\StringHelper::explode($this->resources,'|',true,true)],
			['or like', 'acls.comment', \yii\helpers\StringHelper::explode($this->resources,'|',true,true)],
		]);
	
		$totalQuery=clone $query;
		return new ActiveDataProvider([
			'query' => $query,
			'totalCount' => $totalQuery->count('distinct(schedules.id)'),
			'pagination' => ['pageSize' => 1000,],
		]);
    }
}
