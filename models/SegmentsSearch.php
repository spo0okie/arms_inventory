<?php

namespace app\models;

use app\helpers\QueryHelper;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * SegmentsSearch represents the model behind the search form of `app\models\Segments`.
 */
class SegmentsSearch extends Segments
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            //[['id', 'archived'], 'integer'],
            [['name', 'description',], 'safe'],
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
        $query = Segments::find();

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
	
		if (!$this->archived) {
			$query->andWhere(['IFNULL(segments.archived,0)'=>0]);
		}

        $query
			->andFilterWhere(QueryHelper::querySearchString('segments.name', $this->name))
			->andFilterWhere(QueryHelper::querySearchString('segments.description', $this->description));

        return $dataProvider;
    }
}
