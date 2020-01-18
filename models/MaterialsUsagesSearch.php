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

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'materials_id', 'count', 'arms_id', 'techs_id'], 'integer'],
            [['date', 'comment','sname'], 'safe'],
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

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
	        'pagination' => ['pageSize' => 100,],
	        'sort'=> ['defaultOrder' => ['date'=>SORT_DESC]]
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
            'count' => $this->count,
            'date' => $this->date,
            'arms_id' => $this->arms_id,
            'techs_id' => $this->techs_id,
        ]);

        $query->andFilterWhere([
        	'like',
	        'CONCAT('.
	        'getplacepath(places.id), '.
	        '"(", ifnull(users.Ename,""), ") \ ", '.
	        'materials_types.name, ":", '.
	        'materials.model, " ", '.
	        'materials_usages.count, '.
	        'materials_types.units, " -> ", '.
	        'ifnull(arms.num,""), " " ,'.
	        'ifnull(techs.num,"") , " ",'.
	        'materials_usages.comment , '.
	        '" //", materials_usages.date)',
	        $this->sname
        ]);

        return $dataProvider;
    }
}
