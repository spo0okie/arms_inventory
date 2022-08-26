<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Partners;

/**
 * PartnersSearch represents the model behind the search form of `app\models\Partners`.
 */
class PartnersSearch extends Partners
{
	
	public $sname;
	public $longName;
	public $inn_kpp;
	
	
	
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'integer'],
            [['inn', 'kpp', 'ogrn', 'uname', 'bname', 'comment','sname','inn_kpp'], 'safe'],
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
        $query = Partners::find();

        $sort = [
			'attributes'=>[
				'sname'=>[
					'asc'=>['CONCAT(uname,bname)'=>SORT_ASC],
					'desc'=>['CONCAT(uname,bname)'=>SORT_DESC],
				],
				'inn_kpp'=>[
					'asc'=>['CONCAT(inn,"/",kpp)'=>SORT_ASC],
					'desc'=>['CONCAT(inn,"/",kpp)'=>SORT_DESC],
				],
			],
		];

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
			'sort' => $sort,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
        ]);

        $query
			->andFilterWhere(['or like', 'CONCAT(inn,"/",kpp)', \yii\helpers\StringHelper::explode($this->inn_kpp,'|',true,true)])
			->andFilterWhere(['or like', 'CONCAT(uname,bname)', \yii\helpers\StringHelper::explode($this->sname,'|',true,true)])
			->andFilterWhere(['or like', 'comment', \yii\helpers\StringHelper::explode($this->comment,'|',true,true)])
			->andFilterWhere(['or like', 'cabinet_url', \yii\helpers\StringHelper::explode($this->cabinet_url,'|',true,true)])
			->andFilterWhere(['or like', 'support_tel', \yii\helpers\StringHelper::explode($this->support_tel,'|',true,true)]);

        return $dataProvider;
    }
}
