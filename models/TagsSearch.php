<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * TagsSearch представляет модель для поиска тегов
 */
class TagsSearch extends Tags
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'usage_count'], 'integer'],
            [['name', 'slug', 'color', 'description', 'created_at', 'updated_at', 'updated_by'], 'safe'],
            [['archived'], 'boolean'],
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
     * Создает data provider для модели Tags с примененными фильтрами
     *
     * @param array $params Параметры поиска
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Tags::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    'usage_count' => SORT_DESC,
                    'name' => SORT_ASC,
                ],
            ],
            'pagination' => [
                'pageSize' => 50,
            ],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Фильтрация по точным значениям
        $query->andFilterWhere([
            'id' => $this->id,
            'usage_count' => $this->usage_count,
            'archived' => $this->archived,
        ]);

        // Фильтрация по частичному совпадению
        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'slug', $this->slug])
            ->andFilterWhere(['like', 'color', $this->color])
            ->andFilterWhere(['like', 'description', $this->description])
            ->andFilterWhere(['like', 'updated_by', $this->updated_by]);

        // Фильтрация по датам
        if (!empty($this->created_at)) {
            $query->andFilterWhere(['>=', 'created_at', $this->created_at]);
        }
        
        if (!empty($this->updated_at)) {
            $query->andFilterWhere(['>=', 'updated_at', $this->updated_at]);
        }

        return $dataProvider;
    }
}