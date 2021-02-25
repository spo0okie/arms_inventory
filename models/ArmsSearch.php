<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Arms;

/**
 * ArmsSearch represents the model behind the search form of `\app\models\Arms`.
 */
class ArmsSearch extends Arms
{
	public $comp_hw;
	public $comp_ip;
	public $type_id;
	public $user_position;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['num', 'inv_num', 'model_id', 'sn', 'user_id', 'places_id','departments_id', 'comp_ip', 'comp_id','model_id','type_id','model','user_position','comp_hw'], 'safe'],
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
	    $query = new \yii\db\Query();

        $query = Arms::find()
	        ->joinWith(['user','techModel','comp.netIps','place','contracts','licItems','licGroups','licKeys','department']);
			/*->join('LEFT JOIN','networks','(comps_ip.addr >= networks.addr) and (comps_ip.addr < networks.addr+power(2,(32-networks.mask)))')
			->join('LEFT JOIN','net_vlans','net_vlans.id=networks.vlan_id')
			->join('LEFT JOIN','segments','segments.id=net_vlans.segment_id'); /**/

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

        //$query->select(['getplacepath({{places}}.id) AS path']);

        $query->andFilterWhere(['like', 'num', $this->num])
	        ->andFilterWhere(['like', 'inv_num', $this->inv_num])
            ->andFilterWhere(['like', 'sn', $this->sn])
	        ->andFilterWhere(['like', 'users.Ename', $this->user_id])
	        ->andFilterWhere(['like', 'users.Doljnost', $this->user_position])
	        ->andFilterWhere(['like', 'comps.ip', $this->comp_ip])
	        ->andFilterWhere(['like', 'comps.name', $this->comp_id])
	        ->andFilterWhere(['like', 'comps.raw_hw', $this->comp_hw])
	        ->andFilterWhere(['like', 'departments.name', $this->departments_id])
	        ->andFilterWhere(['like', 'arms_models.name', $this->model])
	        ->andFilterWhere(['like', 'getplacepath({{places}}.id)', $this->places_id])
	        ->andFilterWhere(['arms.model_id'=>$this->model_id])
	        ->andFilterWhere(['arms_models.type_id'=>$this->type_id])
		    ->andFilterWhere(['like', 'comment', $this->comment]);

        return $dataProvider;
    }
}
