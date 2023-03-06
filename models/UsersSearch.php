<?php

namespace app\models;

use app\helpers\QueryHelper;
use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Users;

/**
 * UsersSearch represents the model behind the search form of `app\models\Users`.
 */
class UsersSearch extends Users
{
	public $orgStruct_name;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['Persg', 'Uvolen', 'nosync'], 'integer'],
            [['id', 'Orgeh', 'Doljnost', 'Ename', 'Login', 'Email', 'Phone', 'Mobile', 'work_phone', 'Bday', 'manager_id', 'employee_id', 'orgStruct_name'], 'safe'],
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
        $query = Users::find()->joinWith([
        	'orgStruct',
			'techs'
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
            'Persg' => $this->Persg,
            'Uvolen' => $this->Uvolen,
            'nosync' => $this->nosync,
        ]);

        $query
			->andFilterWhere(['or like', 'id', 			\yii\helpers\StringHelper::explode($this->id,'|',true,true)])
	        ->andFilterWhere(['or like', 'employee_id', \yii\helpers\StringHelper::explode($this->employee_id,'|',true,true)])
	        ->andFilterWhere(['or like', 'Orgeh', 		\yii\helpers\StringHelper::explode($this->Orgeh,'|',true,true)])
            ->andFilterWhere(['or like', 'Doljnost', 	\yii\helpers\StringHelper::explode($this->Doljnost,'|',true,true)])
            ->andFilterWhere(['or like', 'Ename', 		\yii\helpers\StringHelper::explode($this->Ename,'|',true,true)])
            ->andFilterWhere(['or like', 'Login',		\yii\helpers\StringHelper::explode($this->Login,'|',true,true)])
            ->andFilterWhere(['or like', 'Email',		\yii\helpers\StringHelper::explode($this->Email,'|',true,true)])
			->andFilterWhere(QueryHelper::querySearchString(['OR','users.Phone','techs.comment'], $this->Phone))
			->andFilterWhere(['or like', 'Mobile',		\yii\helpers\StringHelper::explode($this->Mobile,'|',true,true)])
            ->andFilterWhere(['or like', 'org_struct.name', \yii\helpers\StringHelper::explode($this->orgStruct_name,'|',true,true)])
            ->andFilterWhere(['or like', 'work_phone',	\yii\helpers\StringHelper::explode($this->work_phone,'|',true,true)])
            ->andFilterWhere(['or like', 'Bday', 		\yii\helpers\StringHelper::explode($this->Bday,'|',true,true)])
            ->andFilterWhere(['or like', 'manager_id', \yii\helpers\StringHelper::explode($this->manager_id,'|',true,true)]);

        return $dataProvider;
    }
}
