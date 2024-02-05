<?php

namespace app\models;

use app\helpers\QueryHelper;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

/**
 * ContractsSearch represents the model behind the search form of `app\models\Contracts`.
 */
class ContractsSearch extends Contracts
{
	
	/**
	 * @var mixed|null
	 */
    public $fullname;

	/**
	 * @var mixed|null
	 */
	public $total;
	
	public $deliveryStatus;
	
	public function rules()
    {
        return [
            [['id', 'parent', 'state_id'], 'integer'],
            [['fullname', 'comment','total','deliveryStatus'], 'safe'],
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
        $query = Contracts::find()->with([
			'currency',
			'partners',
	        'techs',
	        'licItems',
	        'services',
			'successor',
			'childs',
			'users',
	        'state',
        ]);

        $this->load($params);
        //if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
        //    return $dataProvider;
        //}
	
		/*
		 * В общем тут надо исповедаться, т.к. далее идет изврат, который не каждому будет по душе. Чтобы одновременно
		 * использовать join для поиска, и при этом не ломать пагинацию, в которую изза джойна попадают дополнительные
		 * строки, мы делаем так. Сначала делаем поиск по имени (которое включает в себя заджойненные таблицы) без
		 * пагинации. Этим поиском мы находим все контракты с нужным именем/пользователем/контрагентом. А потом мы
		 * ищем уже контракты у которых ID из найденного ранее набора.
		 *
		 * называется Subquery
		 * SELECT * FROM sometable WHERE Location IN (SELECT DISTINCT Location FROM sometable LIMIT 2);
		 */
		
		
		//поисковый запрос в тексте повторяющем "шаблон вывода списка документов"
		// дата - наименование - контрагент - комментарий
		$nameExpression=new Expression("concat(".
			"ifnull(`contracts`.`date`,'нет даты'),' - ',".
			"ifnull(`contracts`.`pay_id`,''),".
			"`contracts`.`name`,' - ',".
			"ifnull(`partners`.`uname`,'".static::$noPartnerSuffix."'),' (', ifnull(`partners`.`bname`,'') , ')',".
			"ifnull(`users`.`Ename`,''),".
			"ifnull(`contracts`.`comment`,'')".
			")");
	
	
		$nameSubQuery=Contracts::find()
			->select('DISTINCT(contracts.id)')
			->joinWith(['partners','users'])
			->where(QueryHelper::querySearchString($nameExpression,$this->fullname))
			->createCommand()
			->rawSql;
		
		

	    $query
		    ->andFilterWhere(['contracts.state_id'=>$this->state_id]);
	    
	    if (strlen($this->deliveryStatus)) {
			if ($this->deliveryStatus)
				$query->andWhere('ifnull(techs_delivery,0) + ifnull(materials_delivery,0) + ifnull(lics_delivery,0)>0');
			if (!$this->deliveryStatus)
				$query->andWhere('ifnull(techs_delivery,0) + ifnull(materials_delivery,0) + ifnull(lics_delivery,0)=0');
		}
	    
	    if ($this->fullname)
	    	$query->andWhere('contracts.id in ('.$nameSubQuery.')');
	    
	    $query
		    //->andFilterWhere(\app\helpers\QueryHelper::querySearchString($nameExpression,$this->fullname))
		    ->andFilterWhere(QueryHelper::querySearchNumberOrDate('total',$this->total))
		    ->orderBy(['date'=>SORT_DESC,'name'=>SORT_DESC]);

	    return new ActiveDataProvider([
		    'query' => $query,
	    ]);
    }
}
