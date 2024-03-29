<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\LicGroups;

/**
 * LicGroupsSearch represents the model behind the search form of `app\models\LicGroups`.
 */
class LicGroupsSearch extends LicGroups
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'soft_ids'], 'integer'],
            [['descr', 'comment', 'created_at'], 'safe'],
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
        $query = LicGroups::find()
		->joinWith([
			'soft'
		]);

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
            //'id' => $this->id,
            //'soft_ids' => $this->soft_ids,
            //'created_at' => $this->created_at,
        ]);

        $query->andFilterWhere(['or like', 'lic_groups.descr', \yii\helpers\StringHelper::explode($this->descr,'|',true,true)])
			->andFilterWhere(['or like', 'lic_groups.comment', \yii\helpers\StringHelper::explode($this->comment,'|',true,true)])
			->andFilterWhere(['soft.id'=>$this->soft_ids])
			->orderBy('descr');

        return $dataProvider;
    }
}
