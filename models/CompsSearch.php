<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Comps;

/**
 * CompsSearch represents the model behind the search form of `app\models\Comps`.
 */
class CompsSearch extends Comps
{

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'domain_id'], 'integer'],
            [['name', 'os', 'raw_hw', 'raw_soft', 'raw_version', 'comment', 'updated_at', 'arm_id','ip'], 'safe'],
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
        $query = Comps::find()
	        ->joinWith(['arm']);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
	        'pagination' => ['pageSize' => 100,],
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        /*$query->andFilterWhere([
            //'id' => $this->id,
            //'domain_id' => $this->domain_id,
            //'arm_id' => $this->arm_id,
            //'updated_at' => $this->updated_at,
        ]);*/

        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'raw_version', $this->raw_version])
            ->andFilterWhere(['like', 'ip', $this->ip])
            ->andFilterWhere(['like', 'arms.num', $this->arm_id])
            ->andFilterWhere(['or',
				['like', 'os', $this->os],
				['like', 'raw_soft', $this->os],
			])
            ->andFilterWhere(['like', 'comment', $this->comment]);

        return $dataProvider;
    }
}
