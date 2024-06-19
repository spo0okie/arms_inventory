<?php

namespace app\models;

use app\helpers\QueryHelper;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * MaintenanceJobsSearch represents the model behind the search form of `app\models\MaintenanceJobs`.
 */
class MaintenanceJobsSearch extends MaintenanceJobs
{
	
	public $objects;
	public $schedule;
	
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'schedules_id', 'services_id'], 'integer'],
            [['name', 'description', 'links', 'updated_at', 'updated_by','schedule','objects'], 'safe'],
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
    	//Запрос для данных (БЕЗ JOIN чтобы не ломалась пагинация)
		$query = MaintenanceJobs::find()
			->with([
				'schedule',
				'comps',
				'techs',
				'services'
			]);
        
        //запрос для фильтра (с JOIN чтобы нормально можно было фильтровать по связанным объектам)
		$filter=MaintenanceJobs::find()
			->select('DISTINCT(maintenance_jobs.id)')
			->joinWith([
				'schedule',
				'comps',
				'techs',
				'services'
			]);


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        
        // наполняем фильтр всякими WHERE
		$filter->andFilterWhere([
            'schedules_id' => $this->schedules_id,
        ]);
	
		$filter->andFilterWhere(['like', 'maintenance_jobs.name', $this->name])
            ->andFilterWhere(['like', 'maintenance_jobs.description', $this->description])
            ->andFilterWhere(['like', 'maintenance_jobs.links', $this->links])
            ->andFilterWhere(QueryHelper::querySearchString(['AND/OR',
				'IFNULL(comps.name,"")',
				'IFNULL(techs.num,"")',
				'IFNULL(techs.hostname,"")',
				'IFNULL(services.name,"")'
			], $this->objects))
            ->andFilterWhere(QueryHelper::querySearchString(['AND/OR',
				'IFNULL(schedules.name,"")',
				'IFNULL(schedules.description,"")',
			],$this->schedules_id));
	
		//если фильтруем, то делаем двухходовку в виде SUB-QUERY
		if ($filter->where) {
			//выбираем ID отфильтрованных записей
			$filterSubQuery=$filter
				->createCommand()
				->rawSql;
			
			//фильтруем запрос данных по этим ID
			$query
				->where('maintenance_jobs.id in ('.$filterSubQuery.')');
		}
		
        return $dataProvider;
    }
}
