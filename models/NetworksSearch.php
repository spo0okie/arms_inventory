<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\helpers\StringHelper;

/**
 * NetworksSearch represents the model behind the search form of `app\models\Networks`.
 */
class NetworksSearch extends Networks
{
	public $domain_id;
	public $domain;
	public $vlan;
	public $segment;
	
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id',  'addr', 'mask', 'router', 'dhcp'], 'integer'],
            [['name','vlan_id','vlan', 'domain_id','domain', 'comment', 'segments_id','segment'], 'safe'],
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
     * @param array $params
     * @return ActiveDataProvider
     */
    public function search(array $params)
    {
        $query = Networks::find()
			//->select(['*',''])
			->joinWith([
				'segment',
				'netVlan.netDomain',
			]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
			'pagination' => false,
			'sort'=> [
				//'defaultOrder' => ['domains_id'=>SORT_ASC],
				'attributes'=>[
					'name'=>[
						'asc'=>['addr'=>SORT_ASC],
						'desc'=>['addr'=>SORT_DESC],
					],
					'domain'=>[
						'asc'=>['net_domains.name'=>SORT_ASC],
						'desc'=>['net_domains.name'=>SORT_DESC],
					],
					'vlan'=>[
						'asc'=>['net_vlans.vlan'=>SORT_ASC],
						'desc'=>['net_vlans.vlan'=>SORT_DESC],
					],
					'segment'=>[
						'asc'=>['segments.name'=>SORT_ASC],
						'desc'=>['segments.name'=>SORT_DESC],
					],
					'comment'
				]
			],
		]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
		if (!$this->archived) {
			$query->andWhere(['not',['IFNULL(networks.archived,0)'=>1]]);
		}
		
        $query
			->andFilterWhere(['or like', 'concat(networks.text_addr,"/",networks.mask,"(",IFNULL(networks.name,""))', StringHelper::explode($this->name,'|',true,true)])
			->andFilterWhere(['or like', 'concat(net_vlans.name," (",net_vlans.vlan)', StringHelper::explode($this->vlan,'|',true,true)])
			->andFilterWhere(['or like', 'net_domains.name', StringHelper::explode($this->domain,'|',true,true)])
			->andFilterWhere(['or like', 'segments.name', StringHelper::explode($this->segment,'|',true,true)])
			->andFilterWhere(['networks.segments_id'=>$this->segments_id])
            ->andFilterWhere(['or like', 'networks.comment', StringHelper::explode($this->comment,'|',true,true)]);

        return $dataProvider;
    }
}
