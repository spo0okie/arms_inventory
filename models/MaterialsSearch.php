<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Materials;

/**
 * MaterialsSearch represents the model behind the search form of `app\models\Materials`.
 */
class MaterialsSearch extends Materials
{
	public $rest;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'parent_id', 'rest', 'type_id', 'places_id'], 'integer'],
            [['date', 'model', 'it_staff_id', 'comment', 'history'], 'safe'],
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
        $query = Materials::find()
	        ->select([
	        	'`materials`.*',
		        'sum(`moved`.`count`) as `movedCount`',
		        'sum(`used`.`count`) as `usedCount`',
	        ])
	        ->leftjoin('materials as moved','`moved`.`parent_id`=`materials`.`id`')
	        ->leftJoin('materials_usages as used','`used`.`materials_id`=`materials`.`id`')
            ->joinWith([
            	'itStaff',
	            'type',
	            //'usages'
            ]);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
	        'pagination' => [
		        'pageSize'=>100
	        ]
        ]);

        $this->load($params);

	    if (is_null($this->rest)||!strlen($this->rest)) $this->rest=1;
	    
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'date' => $this->date,
            'type_id' => $this->type_id,
            'places_id' => $this->places_id,
        ]);

        $query->andFilterWhere(['like', 'concat( getplacepath(materials.places_id) , "(" , users.Ename , ") \ " , materials_types.name , ": ", materials.model )', explode('|',$this->model)])
	    ->andFilterWhere(['like', 'comment', $this->comment])
        ->groupBy('materials.id')
        //->having(['>=','(`materials`.`count` - ifnull(`usedCount`,0) - ifnull(`movedCount`,0))',$this->rest]);
        //вот это вызывало ошибку неизвестный столбец в хэвинг условии
        //The SQL standard requires that HAVING must reference only columns in the GROUP BY clause or columns used in aggregate functions. However, MySQL supports an extension to this behavior, and permits HAVING to refer to columns in the SELECT list and columns in outer subqueries as well.
        //если по русски, то чтобы фильтровать через хэвинг, надо указывать столбцы из группировки или аггрегирования
	    ->having(['>=','(`materials`.`count` - ifnull(sum(`moved`.`count`),0) - ifnull(sum(`used`.`count`),0))',$this->rest]);

        return $dataProvider;
    }
}
