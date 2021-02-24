<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\NetIps;

/**
 * NetIpsSearch represents the model behind the search form of `app\models\NetIps`.
 */
class NetIpsSearch extends NetIps
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'addr', 'mask'], 'integer'],
            [['text_addr'], 'safe'],
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
        $query = NetIps::find();

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
            'addr' => $this->addr,
            'mask' => $this->mask,
        ]);

        $query->andFilterWhere(['like', 'text_addr', $this->text_addr]);

        return $dataProvider;
    }
}
