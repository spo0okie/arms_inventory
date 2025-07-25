<?php

namespace app\models;

use app\helpers\QueryHelper;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * UsersSearch represents the model behind the search form of `app\models\Users`.
 */
class UsersSearch extends Users
{
	public $org_name;
	public $orgStruct_name;
	public $shortName;
	public $archived=true;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['Persg', 'Uvolen', 'nosync','org_id'], 'integer'],
            [['shortName', 'Doljnost', 'Ename', 'Login', 'Email', 'Phone', 'Mobile', 'work_phone',  'employee_id', 'orgStruct_name','org_name','Orgeh'], 'safe'],
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
	public function search($params,$columns=null)
	{
		
		[$query,$filter]=(new Users())->prepareSearch($columns);
		
		$sort=[
			//'defaultOrder' => ['num'=>'E'],
			'attributes'=>[
				'employee_id',
				'Ename',
				'shortName'=>[
					'asc'=>['users.Ename'=>SORT_ASC],
					'desc'=>['users.Ename'=>SORT_DESC],
				],
				'Doljnost',
				'orgStruct_name'=>[
					'asc'=>['org_struct.name'=>SORT_ASC],
					'desc'=>['org_struct.name'=>SORT_DESC],
				],
				'org_name'=>[
					'asc'=>['partners.bname'=>SORT_ASC],
					'desc'=>['partners.bname'=>SORT_DESC],
				],
				'Login',
				'Email',
				'Phone',
				'Mobile'=>[
					'asc'=>['concat(users.Phone,users.private_phone)'=>SORT_ASC],
					'desc'=>['concat(users.Phone,users.private_phone)'=>SORT_DESC],
				],
			]
		];
		
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
        
        if (!$this->archived) {
			$filter
				->andFilterWhere(['users.Uvolen'=>0]);
		}
		
		$filter
			->andFilterWhere(['users.Orgeh'=>$this->Orgeh])
			->andFilterWhere(['users.org_id'=>$this->org_id])
	        ->andFilterWhere(QueryHelper::querySearchString('employee_id',$this->employee_id))
			->andFilterWhere(QueryHelper::querySearchString('Doljnost',$this->Doljnost))
			->andFilterWhere(QueryHelper::querySearchString('Ename',$this->Ename))
			->andFilterWhere(QueryHelper::querySearchString('Ename',$this->shortName))
            ->andFilterWhere(QueryHelper::querySearchString('Login',$this->Login))
            ->andFilterWhere(QueryHelper::querySearchString('Email',$this->Email))
			->andFilterWhere(QueryHelper::querySearchString(['OR','users.Phone','techs.comment'], $this->Phone))
			->andFilterWhere(QueryHelper::querySearchString(['OR','users.Mobile','users.private_phone'], $this->Mobile))
			->andFilterWhere(QueryHelper::querySearchString('partners.bname',$this->org_name))
			->andFilterWhere(QueryHelper::querySearchString('org_struct.name',$this->orgStruct_name));
		
		if ($filter->where) {
			//фильтруем запрос данных по ID из фильтра, который мы только что получили при помощи разных WHERE
			$query->where(static::tableName().'.id in ('.$filter->createCommand()->rawSql.')');
		}
		
		$totalQuery=clone $query;
	
		return new ActiveDataProvider([
			'query' => $query->groupBy('users.id'),
			'totalCount' => $totalQuery->count('distinct(users.id)'),
			'sort'=> $sort,
        ]);
    }
}
