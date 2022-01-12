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
	
	public $network;
	public $attached;
	public $vlan;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'addr', 'mask'], 'integer'],
            [['text_addr','network','vlan','attached','comment'], 'safe'],
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
        $query = NetIps::find()
		->joinWith(['network.netVlan','network.segment','techs','comps']);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
			'pagination' => ['pageSize' => 100,],
			'sort'=> [
				//'defaultOrder' => ['domains_id'=>SORT_ASC],
				'attributes'=>[
					'text_addr'=>[
						'asc'=>['addr'=>SORT_ASC],
						'desc'=>['addr'=>SORT_DESC],
					],
					'network'=>[
						'asc'=>['networks.addr'=>SORT_ASC],
						'desc'=>['networks.addr'=>SORT_DESC],
					],
					'vlan'=>[
						'asc'=>['net_vlans.vlan'=>SORT_ASC],
						'desc'=>['net_vlans.vlan'=>SORT_DESC],
					],
					'comment'
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
			->andFilterWhere(['or like', 'concat(net_ips.text_addr,"(",IFNULL(net_ips.name,""))', \yii\helpers\StringHelper::explode($this->text_addr,'|',true,true)])
			->andFilterWhere(['or like', 'concat(networks.text_addr,"/",networks.mask,"(",IFNULL(networks.name,""))', \yii\helpers\StringHelper::explode($this->network,'|',true,true)])
			->andFilterWhere(['or like', 'concat(net_vlans.name," (",net_vlans.vlan)', \yii\helpers\StringHelper::explode($this->vlan,'|',true,true)])
			->andFilterWhere(['or like', 'net_ips.comment', \yii\helpers\StringHelper::explode($this->comment,'|',true,true)])
			->andFilterWhere([
				'OR',
					['or like', 'ip_comps.name', \yii\helpers\StringHelper::explode($this->attached,'|',true,true)],
					['or like', 'ip_techs.num', \yii\helpers\StringHelper::explode($this->attached,'|',true,true)],
				]);

        return $dataProvider;
    }
}
