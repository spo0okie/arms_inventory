<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Ports;

/**
 * PortsSearch represents the model behind the search form of `app\models\Ports`.
 */
class PortsSearch extends Ports
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['name', 'comment', 'techs_id', 'link_techs_id', 'link_arms_id', 'link_ports_id'], 'safe'],
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
        $query = Ports::find()
			->joinWith(['tech','linkTech','linkPort']);

        // add conditions that should always apply here
	
		$dataProvider = new ActiveDataProvider([
			'query' => $query,
			'pagination' => ['pageSize' => 100,],
			'sort'=> [
				//'defaultOrder' => ['domains_id'=>SORT_ASC],
				'attributes'=>[
					'name'=>[
						'asc'=>['ports.name'=>SORT_ASC],
						'desc'=>['ports.name'=>SORT_DESC],
					],
					'techs_id'=>[
						'asc'=>['techs.num'=>SORT_ASC],
						'desc'=>['techs.num'=>SORT_DESC],
					],
					'comment'=>[
						'asc'=>['ports.comment'=>SORT_ASC],
						'desc'=>['ports.comment'=>SORT_DESC],
					],
				]
			]
		]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query
			->andFilterWhere(['or like', 'ports.name', \yii\helpers\StringHelper::explode($this->name,'|',true,true)])
			->andFilterWhere(['or like', 'techs.num', \yii\helpers\StringHelper::explode($this->techs_id,'|',true,true)])
			->andFilterWhere(['or',
				['or like', 'port_linked_techs.num', \yii\helpers\StringHelper::explode($this->link_techs_id,'|',true,true)],
				['or like', 'port_linked_ports.name', \yii\helpers\StringHelper::explode($this->link_techs_id,'|',true,true)],
			])
            ->andFilterWhere(['or like', 'ports.comment', \yii\helpers\StringHelper::explode($this->comment,'|',true,true)]);

        return $dataProvider;
    }
}
