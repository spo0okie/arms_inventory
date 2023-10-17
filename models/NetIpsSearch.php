<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\StringHelper;

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
    public function search(array $params)
    {
        $searchQuery = NetIps::find()
		->joinWith(['network.netVlan','network.segment','techs','comps','users']);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $searchQuery,
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
	
	
		$searchQuery
			->andFilterWhere(['or like', 'concat(net_ips.text_addr,"(",IFNULL(net_ips.name,""))', StringHelper::explode($this->text_addr,'|',true,true)])
			->andFilterWhere(['or like', 'concat(networks.text_addr,"/",networks.mask,"(",IFNULL(networks.name,""))', StringHelper::explode($this->network,'|',true,true)])
			->andFilterWhere(['or like', 'concat(net_vlans.name," (",net_vlans.vlan)', StringHelper::explode($this->vlan,'|',true,true)])
			->andFilterWhere(['or like', 'net_ips.comment', StringHelper::explode($this->comment,'|',true,true)])
			->andFilterWhere([
				'OR',
					['or like', 'ip_comps.name', StringHelper::explode($this->attached,'|',true,true)],
					['or like', 'ip_techs.num', StringHelper::explode($this->attached,'|',true,true)],
				]);
	
	
		//делаем with (без Join) объектов для отфильтрованных IPs (это борьба с пагинацией которая несовместима с join)
        $dataQuery=NetIps::find()
			->with(['network.netVlan','network.segment','techs.state','comps','users']);
        
        //если фильтруем, то делаем двухходовку
        if ($searchQuery->where) {
        	//выбираем ID отфильтрованных IPs
			$filterSubQuery=$searchQuery
				->select('DISTINCT(net_ips.id)')
				->createCommand()
				->rawSql;
			$dataQuery
				->where('net_ips.id in ('.$filterSubQuery.')');
		}
		
		$dataProvider->query=$dataQuery;
        return $dataProvider;
    }
}
