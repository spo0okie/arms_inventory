<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Materials;
use yii\data\ArrayDataProvider;

/**
 * MaterialsSearch represents the model behind the search form of `app\models\Materials`.
 */
class MaterialsSearch extends Materials
{
	private static $modelSearch='concat( materials_types.name , ": ", materials.model )';
	public $rest;
	public $place;
	public $type;
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'parent_id', 'rest', 'type_id', 'places_id'], 'integer'],
            [['date', 'model', 'it_staff_id', 'comment', 'history','place','type'], 'safe'],
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
	
		$sort=[
			'defaultOrder' => ['place'=>SORT_ASC,'type'=>SORT_ASC],
			'attributes'=>[
				'place'=>[
					'asc'=>['getplacepath(materials.places_id)'=>SORT_ASC],
					'desc'=>['getplacepath(materials.places_id)'=>SORT_DESC],
				],
				'type'=>[
					'asc'=>['materials_types.name'=>SORT_ASC],
					'desc'=>['materials_types.name'=>SORT_DESC],
				],
				'model'=>[
					'asc'=>[static::$modelSearch=>SORT_ASC],
					'desc'=>[static::$modelSearch=>SORT_DESC],
				],
				'comment'=>[
					'asc'=>['materials.comment'=>SORT_ASC],
					'desc'=>['materials.comment'=>SORT_DESC],
				],
				'date'=>[
					'asc'=>['materials.date'=>SORT_ASC],
					'desc'=>['materials.date'=>SORT_DESC],
				],
			]
		];

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
	        'pagination' => [
		        'pageSize'=>100
	        ],
			'sort'=>$sort
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
            'materials.type_id' => $this->type_id,
            'materials.places_id' => $this->places_id,
        ]);
		
        $query->andFilterWhere(['or like', static::$modelSearch, \yii\helpers\StringHelper::explode($this->model,'|',true,true)])
			->andFilterWhere(['or like', 'getplacepath(materials.places_id)', \yii\helpers\StringHelper::explode($this->place,'|',true,true)])
			->andFilterWhere(['or like', 'materials.comment', \yii\helpers\StringHelper::explode($this->comment,'|',true,true)])
			->andFilterWhere(['or like', 'materials.date', \yii\helpers\StringHelper::explode($this->date,'|',true,true)])
        ->groupBy('materials.id')
        //->having(['>=','(`materials`.`count` - ifnull(`usedCount`,0) - ifnull(`movedCount`,0))',$this->rest]);
        //вот это вызывало ошибку неизвестный столбец в хэвинг условии
        //The SQL standard requires that HAVING must reference only columns in the GROUP BY clause or columns used in aggregate functions. However, MySQL supports an extension to this behavior, and permits HAVING to refer to columns in the SELECT list and columns in outer subqueries as well.
        //если по русски, то чтобы фильтровать через хэвинг, надо указывать столбцы из группировки или аггрегирования
	    ->having(['>=','(`materials`.`count` - ifnull(sum(`moved`.`count`),0) - ifnull(sum(`used`.`count`),0))',$this->rest]);

        return $dataProvider;
    }
	
	/**
	 * @param $groups array
	 * @param $model Materials
	 */
    public function pushToGroup(&$groups,$model)
	{
		foreach ($groups as $key=>$group) {
			if (
				$group['place_id']==$model->places_id
				&&
				$group['type_id']==$model->type_id
			) {
				$groups[$key]['models'][]=$model;
				return;
			}
		}
		
		$groups[]=[
			'place_id'=>$model->places_id,
			'type_id'=>$model->type_id,
			'place'=>$model->place->fullName,
			'type'=>$model->type->name,
			'model'=>$model->type->name,
			'models'=>[$model]
		];
	}
 
	/**
	 * Creates data provider instance with search query applied
	 *
	 * @param array $params
	 *
	 * @return ArrayDataProvider
	 */
	public function searchGroups($params)
	{
		$data=$this->search($params);
		$groups=[];
		foreach ($data->models as $model)
			$this->pushToGroup($groups,$model);
		
		return new ArrayDataProvider([
			'allModels'=>$groups,
			'pagination'=>false,
			'sort' => [
				'attributes' => ['place', 'model'],
				'defaultOrder' => ['place'=>SORT_ASC,'model'=>SORT_ASC],
			],
		]);
	}
}
