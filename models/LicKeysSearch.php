<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * LicKeysSearch represents the model behind the search form of `app\models\LicKeys`.
 */
class LicKeysSearch extends LicKeys
{
	public $lic_item;
	
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'lic_items_id'], 'integer'],
            [['key_text', 'comment','lic_item'], 'safe'],
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
        $query = LicKeys::find()
	        ->joinWith(['licItem.licGroup']);

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

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'lic_items_id' => $this->lic_items_id,
        ]);

        $query->andFilterWhere(['like', 'key_text', $this->key_text])
	        ->andFilterWhere(['like', 'CONCAT(`lic_groups`.`descr`," / ",`lic_items`.`descr`)', $this->lic_item])
            ->andFilterWhere(['like', '`lic_keys`.`comment`', $this->comment]);

        return $dataProvider;
    }
}
