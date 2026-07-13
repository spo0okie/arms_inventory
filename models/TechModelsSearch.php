<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\TechModels;

/**
 * TechModelsSearch represents the model behind the search form of `app\models\TechModels`.
 */
class TechModelsSearch extends TechModels
{

	public $type;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'type_id'], 'integer'],
            [['name', 'comment', 'type'], 'safe'],
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
        //type.marker — жадная загрузка цветового маркера категории (issue #141)
        $query = TechModels::find()->joinWith(['type.marker','manufacturer']);

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


        $query->andFilterWhere(['or like', 'concat(`manufacturers`.`name`," ",`tech_models`.`name`)', \app\helpers\StringHelper::explode($this->name,'|',true,true)])
	        ->andFilterWhere(['or like', '`tech_types`.`name`', \app\helpers\StringHelper::explode($this->type,'|',true,true)])
            ->andFilterWhere(['or like', 'comment', \app\helpers\StringHelper::explode($this->comment,'|',true,true)]);

        $query->orderBy([
	        '`tech_types`.`name`'=>SORT_ASC,
	        'name'=>SORT_ASC,
        ]);
        
        return $dataProvider;
    }
}
