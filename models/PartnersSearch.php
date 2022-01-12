<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Partners;

/**
 * PartnersSearch represents the model behind the search form of `app\models\Partners`.
 */
class PartnersSearch extends Partners
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['inn', 'kpp', 'ogrn', 'uname', 'bname', 'comment'], 'safe'],
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
    public function search($params)
    {
        $query = Partners::find();

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
        ]);

        $query
			->andFilterWhere(['or like', 'inn', \yii\helpers\StringHelper::explode($this->inn,'|',true,true)])
            ->andFilterWhere(['or like', 'kpp', \yii\helpers\StringHelper::explode($this->kpp,'|',true,true)])
            ->andFilterWhere(['or like', 'uname', \yii\helpers\StringHelper::explode($this->uname,'|',true,true)])
            ->andFilterWhere(['or like', 'bname', \yii\helpers\StringHelper::explode($this->bname,'|',true,true)])
			->andFilterWhere(['or like', 'comment', \yii\helpers\StringHelper::explode($this->comment,'|',true,true)])
			->andFilterWhere(['or like', 'cabinet_url', \yii\helpers\StringHelper::explode($this->cabinet_url,'|',true,true)])
			->andFilterWhere(['or like', 'support_tel', \yii\helpers\StringHelper::explode($this->support_tel,'|',true,true)]);

        return $dataProvider;
    }
}
