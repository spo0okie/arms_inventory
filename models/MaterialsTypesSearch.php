<?php

namespace app\models;

use app\helpers\QueryHelper;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * MaterialsTypesSearch represents the model behind the search form of `app\models\MaterialsTypes`.
 */
class MaterialsTypesSearch extends MaterialsTypes
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'scans_id'], 'integer'],
            [['code', 'name', 'units', 'comment', 'updated_at', 'updated_by'], 'safe'],
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
     * @return ActiveDataProvider
     */
    public function search($params)
    {
		$query = MaterialsTypes::find()
			->with([
				'materials.usages',
				'materials.children'
			]);

		$filter = MaterialsTypes::find()
			->select('DISTINCT(materials_types.id)')
			->joinWith([
				'materials.usages'
			]);
	
	
		// add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
			'sort' => [
				'defaultOrder'=>['name'=>SORT_ASC]
			],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        
        $filter->andFilterWhere(QueryHelper::querySearchString( 'code', $this->code))
            ->andFilterWhere(QueryHelper::querySearchString('name', $this->name))
            ->andFilterWhere(QueryHelper::querySearchString('units', $this->units))
            ->andFilterWhere(QueryHelper::querySearchString('comment', $this->comment))
            ;
	
		if ($filter->where) {
			//выбираем ID отфильтрованных записей
			$filterSubQuery=$filter
				->createCommand()
				->rawSql;
		
			//фильтруем запрос данных по этим ID
			$query
				->where('materials_types.id in ('.$filterSubQuery.')');
		}
        return $dataProvider;
    }
}
