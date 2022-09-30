<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\MaterialsUsages;

/**
 * MaterialsUsagesSearch represents the model behind the search form of `app\models\MaterialsUsages`.
 */
class MaterialsUsagesSearch extends MaterialsUsages
{

	public $sname;
	public $place;
	public $material;
	public $to;
	
	
	private static $placeCode='getplacepath(materials.places_id)';
	private static $materialCode='CONCAT(materials_types.name, ":", materials.model)';
	private static $toCode='CONCAT(ifnull(arms.num,""), " " ,ifnull(techs.num,"") , " ",materials_usages.comment)';

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'materials_id', 'arms_id', 'techs_id'], 'integer'],
            [['date', 'comment','to','place','material'], 'safe'],
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
        $query = MaterialsUsages::find()
        ->joinWith(['material','material.type','material.place','material.itStaff','arm','tech']);

        // add conditions that should always apply here
	
		$sort=[
			'defaultOrder' => ['date'=>SORT_DESC],
			'attributes'=>[
				'place'=>[
					'asc'=>[static::$placeCode=>SORT_ASC],
					'desc'=>[static::$placeCode=>SORT_DESC],
				],
				'material'=>[
					'asc'=>[static::$materialCode=>SORT_ASC],
					'desc'=>[static::$materialCode=>SORT_DESC],
				],
				'to'=>[
					'asc'=>[static::$toCode=>SORT_ASC],
					'desc'=>[static::$toCode=>SORT_DESC],
				],
				'date'=>[
					'asc'=>['materials_usages.date'=>SORT_ASC],
					'desc'=>['materials_usages.date'=>SORT_DESC],
				],
				'count'=>[
					'asc'=>['materials_usages.count'=>SORT_ASC],
					'desc'=>['materials_usages.count'=>SORT_DESC],
				],
			]
		];
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
	        'pagination' => ['pageSize' => 100,],
	        'sort'=> $sort
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'materials_id' => $this->materials_id,
            'arms_id' => $this->arms_id,
            'techs_id' => $this->techs_id,
        ]);

        $query
			->andFilterWhere([
				'or like','getplacepath(places.id)',\yii\helpers\StringHelper::explode($this->place,'|',true,true)
			])->andFilterWhere([
				'or like',
				'CONCAT(materials_types.name, ":", materials.model)',\yii\helpers\StringHelper::explode($this->material,'|',true,true)
			])->andFilterWhere([
				'or like',
				'CONCAT(ifnull(arms.num,""), " " ,ifnull(techs.num,"") , " ",materials_usages.comment)',\yii\helpers\StringHelper::explode($this->to,'|',true,true)
			])->andFilterWhere([
				'or like',
				'materials_usages.date',\yii\helpers\StringHelper::explode($this->date,'|',true,true)
			]);

        return $dataProvider;
    }
}
