<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Materials;

/**
 * MaterialsSearch represents the model behind the search form of `app\models\Materials`.
 */
class MaterialsSearch extends Materials
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'parent_id', 'count', 'type_id', 'places_id'], 'integer'],
            [['date', 'model', 'it_staff_id', 'comment', 'history'], 'safe'],
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
        $query = Materials::find()
            ->joinWith(['itStaff','materialType']);

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

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'date' => $this->date,
            'count' => $this->count,
            'type_id' => $this->type_id,
            'places_id' => $this->places_id,
        ]);

        $query->andFilterWhere(['like', 'concat( getplacepath(places_id) , "(" , users.Ename , ") \ " , materials_types.name , ": ", model )', $this->model])
            ->andFilterWhere(['like', 'comment', $this->comment]);

        return $dataProvider;
    }
}
