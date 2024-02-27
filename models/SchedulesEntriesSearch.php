<?php

namespace app\models;

use yii\data\ActiveDataProvider;
use yii\helpers\StringHelper;

/**
 * SchedulesEntriesSearch represents the model behind the search form of `\app\models\SchedulesEntries`.
 */
class SchedulesEntriesSearch extends SchedulesEntries
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'schedule_id'], 'integer'],
            [['date', 'schedule', 'comment', 'created_at'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return [];
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
        $query = SchedulesEntries::find()->where(['or',
			['not',['in', 'date', ['1','2','3','4','5','6','7','def']]],
			['date'=>null]
		]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
			'sort' => [
				'defaultOrder' => [
					'date' => SORT_DESC
				]
			]
        ]);

        $this->load($params);
        
        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'schedule_id' => $this->schedule_id,
        ]);

        $query->andFilterWhere(['or',
				['like', 'date', $this->date],
				['like', 'date_end', $this->date]
			])
            ->andFilterWhere(['or like', 'schedule', StringHelper::explode($this->schedule,'|',true,true)])
            ->andFilterWhere(['or like', 'comment', StringHelper::explode($this->comment,'|',true,true)]);

        return $dataProvider;
    }
	
	/*public function beforeValidate()
	{
		//корректируем сценарии перед валидацией
		$this->scenario=self::SCENARIO_DEFAULT;
	}*/
}
