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
	
	/**
	 * @var mixed|null
	 */
    public $fullname;

	/**
	 * @var mixed|null
	 */
	public $total;
	
	public function rules()
    {
        return [
            [['id', 'parent', 'state_id'], 'integer'],
            [['fullname', 'comment','total'], 'safe'],
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
			'currency',
			'partners',
	        'arms',
	        'techs',
	        'licItems',
	        'orgPhones',
	        'orgInets',
        ]);

        $this->load($params);
        //if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
        //    return $dataProvider;
        //}
		
		//параметр для поиска по имени (или ищем по одному значению или по нескольким)
		$nameSearch=(count(explode('|',$this->fullname))>1)?explode('|',$this->fullname):$this->fullname;
		//поисковый запрос в тексте повторяющем "шаблон вывода списка документов"
		// дата - наименование - контрагент - комментарий
		$nameExpression=new \yii\db\Expression("concat(".
			"`contracts`.`date`,' - ',".
			"`contracts`.`name`,' - ',".
			"ifnull(`partners`.`uname`,'".static::$noPartnerSuffix."'),' (', ifnull(`partners`.`bname`,'') , ')',".
			"ifnull(`contracts`.`comment`,'')".
		")");

	    $query
		    ->andFilterWhere(['contracts.state_id'=>$this->state_id]);
	    $query
		    ->andFilterWhere(['or like', $nameExpression, $nameSearch])
		    ->andFilterWhere(['total'=>$this->total])
		    ->orderBy(['date'=>SORT_DESC,'name'=>SORT_DESC]);

	    $dataProvider = new ActiveDataProvider([
		    'query' => $query,
		    'pagination' => ['pageSize' => 100,],
	    ]);

        return $dataProvider;
    }
}
