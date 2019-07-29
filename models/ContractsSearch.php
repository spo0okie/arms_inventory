<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Contracts;

/**
 * ContractsSearch represents the model behind the search form of `app\models\Contracts`.
 */
class ContractsSearch extends Contracts
{
    /**
     * {@inheritdoc}
     */

    public $fullname;
    public function rules()
    {
        return [
            [['id', 'parent'], 'integer'],
            [['fullname', 'comment'], 'safe'],
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
        $query = Contracts::find()->joinWith([
	        'partners',
	        'arms',
	        'techs',
	        'licItems',
	        //'childs',
	        'orgPhones',
	        'orgInets',
        ]);

        $this->load($params);
        //if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
        //    return $dataProvider;
        //}

	    $query
		    ->filterWhere(['like', new \yii\db\Expression("concat(`contracts`.`date`,' - ',`contracts`.`name`,' - ',`partners`.`uname`,' (', `partners`.`bname` , ')' )"), $this->fullname])
		    ->orderBy(['date'=>SORT_DESC,'name'=>SORT_DESC]);

	    $dataProvider = new ActiveDataProvider([
		    'query' => $query,
		    'pagination' => ['pageSize' => 100,],
	    ]);

        return $dataProvider;
    }
}
