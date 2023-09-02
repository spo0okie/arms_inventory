<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\LicTypes;

/**
 * LicTypesSearch represents the model behind the search form of `\app\models\LicTypes`.
 */
class LicTypesSearch extends LicTypes
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['name', 'descr', 'comment', 'created_at', 'links'], 'safe'],
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
        $query = LicTypes::find();

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
            //'created_at' => $this->created_at,
        ]);

        $query->andFilterWhere(['or like', 'name', 	\yii\helpers\StringHelper::explode($this->name,'|',true,true)])
            ->andFilterWhere(['or like', 'descr', 	\yii\helpers\StringHelper::explode($this->descr,'|',true,true)])
            ->andFilterWhere(['or like', 'comment',	\yii\helpers\StringHelper::explode($this->comment,'|',true,true)])
            ->andFilterWhere(['or like', 'links', 	\yii\helpers\StringHelper::explode($this->links,'|',true,true)]);

        return $dataProvider;
    }
}
