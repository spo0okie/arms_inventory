<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\StringHelper;

/**
 * SoftSearch represents the model behind the search form of `\app\models\Soft`.
 */
class SoftSearch extends Soft
{
	public $softList_ids;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'manufacturers_id','softList_ids'], 'integer'],
            [['descr', 'comment', 'items', 'created_at'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
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
    public function search(array $params)
    {
        $query = Soft::find()
			->joinWith(['manufacturer','softLists',])
			->with(['comps','licGroups','licItems']);
		
		$filter = Soft::find()
			->select('DISTINCT(soft.id)')
			->joinWith(['manufacturer','softLists','comps','licGroups','licItems']);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
			//'pagination' => false,
			'sort'=> [
				'defaultOrder' => [
					//'manufacturers_id'=>SORT_ASC,
					'descr'=>SORT_ASC,
				],
				'attributes'=>[
					'descr' => [
						'asc'=>['CONCAT(manufacturers.name,soft.descr)'=>SORT_ASC],
						'desc'=>['CONCAT(manufacturers.name,soft.descr)'=>SORT_DESC],
					],
					'manufacturers_id'=>[
						'asc'=>['manufacturers.name'=>SORT_ASC],
						'desc'=>['manufacturers.name'=>SORT_DESC],
					],
					'comment'
				]
			],

		]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $filter->andFilterWhere([
            'id' => $this->id,
            'manufacturers_id' => $this->manufacturers_id,
			//'created_at' => $this->created_at,
			'soft_in_lists.list_id' => $this->softLists_ids,
        ]);
		
		$filter
			->andFilterWhere(['or like', 'CONCAT(manufacturers.name,soft.descr)', StringHelper::explode($this->descr,'|',true,true)])
            ->andFilterWhere(['or like', 'soft.comment', StringHelper::explode($this->comment,'|',true,true)])
            ->andFilterWhere(['or like', 'soft.items', StringHelper::explode($this->items,'|',true,true)]);
		
		if($filter->where) {
			$filterSubQuery=$filter
				->createCommand()
				->rawSql;
			
			//фильтруем запрос данных по этим ID
			$query
				->where('soft.id in ('.$filterSubQuery.')');
		}

        return $dataProvider;
    }
}
